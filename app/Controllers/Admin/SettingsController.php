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
        $allowedTabs = ['store', 'seo', 'checkout', 'paypal', 'mail', 'preferences', 'chat', 'users'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'store';
        }
        return View::render('admin/settings', [
            'pageTitle' => 'Settings',
            'tab' => $tab,
            'settings' => Settings::all(),
            'admins' => $tab === 'users' ? Database::select("SELECT * FROM users WHERE role = 'admin' ORDER BY id") : [],
            'paypalConfigured' => trim((string) Settings::get('encrypted.paypal.client_id', '')) !== '' && trim((string) Settings::get('encrypted.paypal.client_secret', '')) !== '',
            'webhookConfigured' => trim((string) Settings::get('encrypted.paypal.webhook_id', '')) !== '',
            'mailConfigured' => trim((string) Settings::get('encrypted.mail_host', '')) !== '',
        ], 'layouts/admin');
    }

    private const GROUPS = [
        'chat' => [
            'text' => ['chat.openai_model', 'chat.gemini_model', 'chat.grok_model'],
            'bool' => ['chat.enabled'],
            'email' => [],
            'choice' => ['chat.provider' => ['website', 'openai', 'gemini', 'grok']],
            'secret' => ['encrypted.chat.openai_key', 'encrypted.chat.gemini_key', 'encrypted.chat.grok_key'],
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
            'text' => ['mail.port', 'mail.from_address'],
            'bool' => [],
            'email' => ['mail.from_address'],
            'secret' => ['encrypted.mail_host', 'encrypted.mail_username', 'encrypted.mail_password'],
            'choice' => ['mail.transport' => ['mail', 'smtp'], 'mail.encryption' => ['tls', 'ssl', 'none']],
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
        if ($group === 'chat') {
            if (!is_string($input['chat.provider'] ?? null) || !in_array($input['chat.provider'], ['website', 'openai', 'gemini', 'grok'], true)) {
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
        if ($group === 'paypal' && !empty($_POST['paypal_clear_credentials'])) {
            Settings::set('encrypted.paypal.client_id', '', $group);
            Settings::set('encrypted.paypal.client_secret', '', $group);
            Settings::set('encrypted.paypal.webhook_id', '', $group);
        }

        return $errors;
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
            flash('success', 'Test email sent to ' . $admin['email'] . '.');
        } else {
            $detail = (string) Database::value("SELECT error FROM email_log ORDER BY id DESC LIMIT 1", [], '');
            $message = 'The test email could not be sent.';
            if ($detail !== '') {
                $message .= ' ' . mb_substr($detail, 0, 260);
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
