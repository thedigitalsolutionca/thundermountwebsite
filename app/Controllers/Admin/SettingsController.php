<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Services\PayPalService;

final class SettingsController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $tab = (string) Request::query('tab', 'store');
        $allowedTabs = ['store', 'dealer', 'seo', 'checkout', 'paypal', 'mail', 'preferences', 'chat', 'users'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'store';
        }
        return View::render('admin/settings', [
            'pageTitle' => 'Settings',
            'tab' => $tab,
            'settings' => Settings::all(),
            'admins' => $tab === 'users' ? Database::select("SELECT * FROM users WHERE role = 'admin' ORDER BY id") : [],
            'dealerCounts' => $tab === 'dealer' ? (new \App\Services\DealerService())->counts() : [],
            'paypalConfigured' => trim((string) Settings::get('encrypted.paypal.client_id', '')) !== '' && trim((string) Settings::get('encrypted.paypal.client_secret', '')) !== '',
            'webhookConfigured' => trim((string) Settings::get('encrypted.paypal.webhook_id', '')) !== '',
            'mailConfigured' => trim((string) Settings::get('encrypted.mail_host', '')) !== '',
        ], 'layouts/admin');
    }

    private const GROUPS = [
        'dealer' => [
            'text' => [],
            'bool' => [
                'dealer.enabled',
                'dealer.pages_enabled',
                'dealer.applications_enabled',
                'dealer.pricing_enabled',
                'dealer.auto_approve',
            ],
            'email' => [],
        ],
        'chat' => [
            'text' => ['chat.openai_model', 'chat.gemini_model', 'chat.groq_model'],
            'bool' => ['chat.enabled'],
            'email' => [],
            'choice' => ['chat.provider' => ['website', 'openai', 'gemini', 'groq']],
            'secret' => ['encrypted.chat.openai_key', 'encrypted.chat.gemini_key', 'encrypted.chat.groq_key'],
        ],
        'store' => [
            'text' => ['store.name', 'store.legal_name', 'store.tagline', 'store.phone', 'store.office_phone', 'store.email', 'store.notification_email', 'store.address', 'store.hours', 'store.currency', 'store.announcement', 'store.announcement_link'],
            'bool' => ['store.announcement_enabled', 'store.newsletter_enabled'],
            'email' => ['store.email', 'store.notification_email'],
        ],
        'seo' => [
            'text' => ['seo.title', 'seo.description', 'seo.og_image', 'seo.organization_type'],
            'bool' => [],
            'email' => [],
        ],
        'checkout' => [
            'text' => ['checkout.manual_payment_label', 'checkout.manual_payment_instructions', 'checkout.terms_note'],
            'bool' => ['checkout.manual_payment_enabled'],
            'email' => [],
        ],
        'paypal' => [
            'text' => ['paypal.brand_name'],
            'bool' => ['paypal.enabled'],
            'email' => [],
            'secret' => ['encrypted.paypal.client_id', 'encrypted.paypal.client_secret', 'encrypted.paypal.webhook_id'],
            'choice' => ['paypal.mode' => ['sandbox', 'live']],
        ],
        'mail' => [
            'text' => ['mail.port', 'mail.from_address', 'mail.timeout'],
            'bool' => [],
            'email' => ['mail.from_address'],
            'secret' => ['encrypted.mail_host', 'encrypted.mail_username', 'encrypted.mail_password', 'encrypted.mail_resend_key'],
            'choice' => ['mail.transport' => ['mail', 'smtp', 'resend'], 'mail.encryption' => ['tls', 'ssl', 'none']],
        ],
        'preferences' => [
            'text' => ['inventory.low_stock_threshold'],
            'bool' => ['reviews.auto_approve', 'dealer.auto_approve'],
            'email' => [],
        ],
    ];

    public function save(): never
    {
        Csrf::require();
        $group = (string) Request::input('group', '');
        if (!isset(self::GROUPS[$group])) {
            flash('error', 'Unknown settings group.');
            Response::redirect(url('/admin/settings'));
        }
        $input = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
        $errors = $this->applyGroup($group, $input);
        if ($errors !== []) {
            Session::set('_errors', $errors);
            Response::redirect(url('/admin/settings?tab=' . $group));
        }
        flash('success', ucfirst($group) . ' settings saved.');
        Response::redirect(url('/admin/settings?tab=' . $group));
    }

    /**
     * Validate and persist one settings tab. Returns field errors keyed by field name.
     *
     * @return array<string,string>
     */
    private function applyGroup(string $group, array $input): array
    {
        $definition = self::GROUPS[$group] ?? null;
        if ($definition === null) {
            return ['group' => 'Unknown settings group.'];
        }
        $errors = [];
        if ($group === 'mail') {
            foreach (['mail.port', 'mail.from_address', 'mail.timeout', 'mail.transport', 'mail.encryption', 'encrypted.mail_host', 'encrypted.mail_username', 'encrypted.mail_password', 'encrypted.mail_resend_key'] as $key) {
                if (isset($input[$key]) && !is_string($input[$key])) return [$key => 'Enter a text value.'];
            }
            $input['mail.timeout'] = $input['mail.timeout'] ?? '15';
            if (!ctype_digit($input['mail.timeout']) || (int) $input['mail.timeout'] < 5 || (int) $input['mail.timeout'] > 60) $errors['mail.timeout'] = 'Use a timeout between 5 and 60 seconds.';
            foreach (['encrypted.mail_host', 'encrypted.mail_username', 'encrypted.mail_password', 'encrypted.mail_resend_key'] as $key) {
                $value = trim($input[$key] ?? '');
                if (strlen($value) > 1000 || preg_match('/[\r\n]/', $value)) $errors[$key] = 'Use a value of up to 1000 characters without line breaks.';
                elseif ($value !== '' && \App\Core\Crypt::encrypt($value) === null) $errors[$key] = 'Configure APP_KEY and OpenSSL before saving credentials.';
            }
            $candidate = [
                'transport' => $input['mail.transport'] ?? '',
                'from_address' => trim($input['mail.from_address'] ?? ''),
                'reply_to' => setting('store.email', 'thunder@thundermounts.com'),
                'host' => trim($input['encrypted.mail_host'] ?? '') ?: (string) (setting('encrypted.mail_host', '') ?: \App\Core\Config::get('mail.host', '')),
                'port' => $input['mail.port'] ?? '', 'encryption' => $input['mail.encryption'] ?? '',
                'username' => trim($input['encrypted.mail_username'] ?? '') ?: (string) (setting('encrypted.mail_username', '') ?: \App\Core\Config::get('mail.username', '')),
                'password' => trim($input['encrypted.mail_password'] ?? '') ?: (string) (setting('encrypted.mail_password', '') ?: \App\Core\Config::get('mail.password', '')),
                'api_key' => trim($input['encrypted.mail_resend_key'] ?? '') ?: (string) setting('encrypted.mail_resend_key', ''),
            ];
            if (!in_array($candidate['transport'], ['smtp', 'mail', 'resend'], true)) $errors['mail.transport'] = 'Choose a supported transport.';
            $configErrors = Mailer::validateConfiguration($candidate);
            if ($configErrors !== []) $errors['mail.configuration'] = implode(' ', $configErrors);
            if ($errors !== []) return $errors;
        }
        if ($group === 'chat') {
            if (!is_string($input['chat.provider'] ?? null) || !in_array($input['chat.provider'], ['website', 'openai', 'gemini', 'groq'], true)) {
                $errors['chat.provider'] = 'Choose a supported assistant provider.';
            }
            foreach (\App\Services\ChatService::MODELS as $provider => $defaultModel) {
                $model = $input['chat.' . $provider . '_model'] ?? $defaultModel;
                if (!is_string($model) || !preg_match('/^[a-zA-Z0-9._:\/-]{1,100}$/D', $model)) {
                    $errors['chat.' . $provider . '_model'] = 'Enter a model ID using letters, numbers, dots, colons, slashes or hyphens (up to 100 characters).';
                }
                $input['chat.' . $provider . '_model'] = $model;
                $secretKey = 'encrypted.chat.' . $provider . '_key';
                $secret = $input[$secretKey] ?? '';
                if (!is_string($secret) || strlen($secret) > 1000 || preg_match('/[\r\n]/', $secret)) {
                    $errors[$secretKey] = 'Enter a valid API key (no line breaks, up to 1000 characters).';
                } elseif ($secret !== '' && \App\Core\Crypt::encrypt($secret) === null) {
                    $errors[$secretKey] = 'Configure a secure APP_KEY and OpenSSL before saving API keys.';
                }
            }
            if ($errors !== []) return $errors;
        }

        foreach ($definition['text'] as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value !== '' && in_array($key, $definition['email'], true) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$key] = 'Enter a valid email address.';
                continue;
            }
            if ($key === 'mail.port' && $value !== '' && (!ctype_digit($value) || (int) $value < 1 || (int) $value > 65535)) {
                $errors[$key] = 'Enter a valid port number.';
                continue;
            }
            if ($key === 'inventory.low_stock_threshold' && !ctype_digit($value)) {
                $errors[$key] = 'Enter a whole number.';
                continue;
            }
            Settings::set($key, $value, $group);
        }
        foreach ($definition['bool'] as $key) {
            Settings::set($key, !empty($input[$key]) ? '1' : '0', $group);
        }
        foreach ($definition['choice'] ?? [] as $key => $options) {
            $value = (string) ($input[$key] ?? '');
            if (in_array($value, $options, true)) {
                Settings::set($key, $value, $group);
            }
        }
        foreach ($definition['secret'] ?? [] as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value !== '') {
                Settings::set($key, $value, $group);
            }
        }
        if ($group === 'chat') {
            foreach (array_keys(\App\Services\ChatService::MODELS) as $provider) {
                if (!empty($_POST['chat_clear_' . $provider])) Settings::set('encrypted.chat.' . $provider . '_key', '', $group);
            }
        }
        if ($group === 'chat' && !empty($_POST['chat_clear_legacy_grok'])) Settings::set('encrypted.chat.grok_key', '', 'chat');
        if ($group === 'paypal' && !empty($_POST['paypal_clear_credentials'])) {
            Settings::set('encrypted.paypal.client_id', '', $group);
            Settings::set('encrypted.paypal.client_secret', '', $group);
            Settings::set('encrypted.paypal.webhook_id', '', $group);
        }

        return $errors;
    }

    public function diagnoseEmail(): never
    {
        Csrf::require();
        $input = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
        $errors = $this->applyGroup('mail', $input);
        if ($errors !== []) { Session::set('_errors', $errors); Response::redirect(url('/admin/settings?tab=mail')); }
        $result = Mailer::diagnose();
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/settings?tab=mail'));
    }

    public function testEmail(): never
    {
        Csrf::require();
        // The test button lives inside the mail form, so save what was typed first.
        $input = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
        $errors = $this->applyGroup('mail', $input);
        if ($errors !== []) {
            Session::set('_errors', $errors);
            Response::redirect(url('/admin/settings?tab=mail'));
        }

        $admin = Auth::user();
        $body = '<p>This is a test email from the Thunder Mounts admin.</p><p>If you received it, transactional email is configured correctly.</p>';
        $sent = Mailer::send((string) $admin['email'], 'Thunder Mounts test email', $body);
        if ($sent) {
            flash('success', 'Test email accepted for ' . $admin['email'] . '. Check the inbox/spam folder; provider acceptance is not delivery confirmation.');
        } else {
            $detail = (string) Database::value("SELECT error FROM email_log ORDER BY id DESC LIMIT 1", [], '');
            $message = 'The test email could not be sent.';
            if ($detail !== '') {
                $message .= ' ' . mb_substr($detail, 0, 900);
            }
            flash('error', $message);
        }
        Response::redirect(url('/admin/settings?tab=mail'));
    }

    public function testPayPal(): never
    {
        Csrf::require();
        $result = (new PayPalService())->testConnection();
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/settings?tab=paypal'));
    }

    public function createAdmin(): never
    {
        Csrf::require();
        $data = Request::all();
        $errors = [];
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen((string) ($data['password'] ?? '')) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        if ((int) Database::value('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0) {
            $errors['email'] = 'An account already exists for this email.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($data);
            Response::redirect(url('/admin/settings?tab=users'));
        }
        Database::insert('users', [
            'email' => $email,
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        clear_input();
        flash('success', 'Admin account created for ' . $email . '.');
        Response::redirect(url('/admin/settings?tab=users'));
    }
}
