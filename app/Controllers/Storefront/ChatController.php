<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Services\ChatService;
use Throwable;

final class ChatController
{
    public function message(): never
    {
        Csrf::require();
        header('Cache-Control: no-store');
        if (!Settings::bool('chat.enabled', true)) Response::json(['error' => 'The assistant is currently disabled. Please contact our team.'], 404);
        $question = Request::post('message', '');
        if (!is_string($question) || mb_strlen(trim($question)) < 2 || mb_strlen($question) > 600) {
            Response::json(['error' => 'Please enter a question between 2 and 600 characters.'], 422);
        }
        if (!$this->allowRequest()) {
            header('Retry-After: 60');
            Response::json(['error' => 'You have reached the chat limit. Please try again later or contact our team.'], 429);
        }
        try {
            Response::json((new ChatService())->answer(trim($question)));
        } catch (Throwable) {
            Response::json(['error' => 'The assistant is temporarily unavailable. Please use our Contact page for help.'], 503);
        }
    }

    /** Session burst limit plus a shared, locked per-IP hourly limit (no chat text stored). */
    private function allowRequest(): bool
    {
        $now = time();
        $recent = array_filter($_SESSION['_chat_requests'] ?? [], static fn ($at) => $at > $now - 60);
        if (count($recent) >= 12) return false;
        $hash = hash_hmac('sha256', Request::ip(), (string) Config::get('app.key', 'chat-rate-limit'));
        $file = fopen(TM_ROOT . '/storage/cache/chat-rate-' . $hash . '.json', 'c+');
        if ($file === false) return false;
        if (!flock($file, LOCK_EX)) { fclose($file); return false; }
        try {
            $state = json_decode(stream_get_contents($file) ?: '{}', true);
            if (!is_array($state) || ($state['until'] ?? 0) <= $now) $state = ['until' => $now + 3600, 'count' => 0];
            if (($state['count'] ?? 0) >= 60) return false;
            $state['count']++;
            rewind($file);
            ftruncate($file, 0);
            fwrite($file, json_encode($state));
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
        $recent[] = $now;
        $_SESSION['_chat_requests'] = array_values($recent);
        return true;
    }
}
