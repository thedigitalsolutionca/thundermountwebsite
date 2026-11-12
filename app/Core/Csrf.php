<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token = null): bool
    {
        $token ??= (string) ($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        return $token !== '' && !empty($_SESSION['_csrf']) && hash_equals((string) $_SESSION['_csrf'], $token);
    }

    public static function require(): void
    {
        if (Request::isPost() && !self::verify()) {
            if (Request::wantsJson()) {
                Response::json(['error' => 'Your session expired. Refresh the page and try again.'], 419);
            }
            Session::flash('error', 'Your session expired. Please refresh the page and try again.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
