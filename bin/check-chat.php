<?php

declare(strict_types=1);

/** Local/test-only regression checks. No network calls; all settings changes roll back. */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Admin\SettingsController;
use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Core\Settings;
use App\Services\ChatService;

if (PHP_SAPI !== 'cli' || !in_array(Config::get('app.env'), ['local', 'test', 'testing'], true)) {
    fwrite(STDERR, "Run only against a local/test database with APP_ENV=local or test.\n");
    exit(1);
}
function check(bool $ok, string $label): void
{
    if (!$ok) throw new RuntimeException($label);
    echo 'PASS ', $label, PHP_EOL;
}

$db = Database::pdo();
$db->beginTransaction();
try {
    Settings::set('chat.provider', 'website', 'chat');
    $service = new ChatService();
    foreach ([
        'What powder coating services do you offer?' => '/powder-coating',
        'How do I choose the right mount size?' => '/size-guide',
        'Who invented Thunder Mounts?' => '/about/inventor',
        'Where are the mounting instructions?' => '/resources/instructions',
        'What is your phone number?' => '/contact',
    ] as $question => $source) {
        $answer = $service->answer($question);
        check(str_ends_with($answer['sources'][0]['url'], $source), 'Retrieval: ' . $source);
        check($answer['mode'] === 'website', 'Website mode without AI calls');
    }
    check(str_contains($service->answer('What is the weather today?')['answer'], 'could not find'), 'Out-of-scope fallback');

    foreach (ChatService::MODELS as $provider => $model) {
        Settings::set('chat.provider', $provider, 'chat');
        Settings::set('chat.' . $provider . '_model', $model, 'chat');
        Settings::set('encrypted.chat.' . $provider . '_key', 'test-only-not-a-real-key', 'chat');
        $stored = Database::value('SELECT value FROM settings WHERE `key` = ?', ['encrypted.chat.' . $provider . '_key']);
        check(str_starts_with($stored, 'v1:') && !str_contains($stored, 'test-only'), 'Encrypted key: ' . $provider);
        $mock = new ChatService(static function ($endpoint, $headers, $payload) use ($provider, $model) {
            $expectedHost = ['gemini' => 'generativelanguage.googleapis.com', 'groq' => 'api.groq.com', 'openai' => 'api.openai.com'][$provider];
            check(parse_url($endpoint, PHP_URL_HOST) === $expectedHost, 'Fixed endpoint: ' . $provider);
            check(!str_contains(json_encode($payload), 'test-only'), 'No credentials in prompt');
            if ($provider === 'gemini') {
                check(isset($payload['systemInstruction'], $payload['contents']), 'Gemini request structure');
                return ['candidates' => [['content' => ['parts' => [['text' => 'Grounded test answer.']]]]]];
            }
            check($payload['model'] === $model && $payload['messages'][0]['role'] === 'system', 'Chat-completions request structure');
            return ['choices' => [['message' => ['content' => 'Grounded test answer.']]]];
        });
        $answer = $mock->answer('Tell me about powder coating');
        check($answer['mode'] === 'ai' && $answer['answer'] === 'Grounded test answer.', 'Response parsing: ' . $provider);
        $failed = (new ChatService(static function () { throw new RuntimeException('Provider failed'); }))->answer('Tell me about powder coating');
        check($failed['mode'] === 'website' && isset($failed['notice']), 'Provider failure fallback: ' . $provider);
        $malformed = (new ChatService(static fn () => []))->answer('Tell me about powder coating');
        check($malformed['mode'] === 'website', 'Malformed response fallback: ' . $provider);
    }

    Settings::set('encrypted.chat.groq_key', '', 'chat');
    $missingKey = (new ChatService(static function () { throw new RuntimeException('Must not make a request without a key'); }))->answer('Tell me about powder coating');
    check($missingKey['mode'] === 'website' && !isset($missingKey['notice']), 'Missing key uses website mode without a provider request');
    Settings::set('chat.enabled', '0', 'chat');
    check(trim(\App\Core\View::partial('storefront/partials/chatbot')) === '', 'Disabled widget renders no markup');
    Settings::set('chat.enabled', '1', 'chat');

    $reflection = new ReflectionClass(SettingsController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $apply = $reflection->getMethod('applyGroup');
    $input = ['chat.provider' => 'website', 'chat.enabled' => '1'];
    foreach (ChatService::MODELS as $provider => $model) $input['chat.' . $provider . '_model'] = $model;
    check($apply->invoke($controller, 'chat', $input) === [], 'Save settings with blank keys');
    check(Settings::get('encrypted.chat.openai_key') === 'test-only-not-a-real-key', 'Blank key preserves stored secret');
    $invalid = $input;
    $invalid['chat.provider'] = 'unsupported';
    $invalid['chat.openai_model'] = 'bad model';
    $invalid['encrypted.chat.gemini_key'] = "invalid\r\nheader";
    check(count($apply->invoke($controller, 'chat', $invalid)) === 3, 'Reject provider, model and header injection');
    check(Settings::get('chat.provider') === 'website', 'Invalid submission does not persist');
    $_POST['chat_clear_openai'] = '1';
    $apply->invoke($controller, 'chat', $input);
    check(Settings::get('encrypted.chat.openai_key', '') === '', 'Explicit key removal');
    unset($_POST['chat_clear_openai']);

    // Render settings as an existing local admin; secrets must never populate HTML.
    $adminId = (int) Database::value("SELECT id FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
    if ($adminId > 0) {
        Session::set('user_id', $adminId);
        $_GET['tab'] = 'chat';
        $html = (new SettingsController())->index();
        check(str_contains($html, 'Save assistant settings'), 'Admin assistant tab renders');
        check(!str_contains($html, 'test-only-not-a-real-key'), 'Admin HTML never exposes saved keys');
    }
    echo "Chat checks complete. No live provider requests were made.\n";
} finally {
    $db->rollBack();
    Settings::forget();
}
