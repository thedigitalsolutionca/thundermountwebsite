<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = (int) Session::get('user_id', 0);
        if ($id <= 0) {
            return null;
        }
        $user = Database::first(
            'SELECT u.*, d.status AS dealer_status, d.company, d.tier_id
             FROM users u LEFT JOIN dealers d ON d.id = u.dealer_id
             WHERE u.id = ? AND u.status = ? LIMIT 1',
            [$id, 'active']
        );
        if (!$user) {
            Session::forget('user_id');
            return null;
        }
        self::$user = $user;
        return $user;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function isDealer(): bool
    {
        return (self::user()['role'] ?? '') === 'dealer' && (self::user()['dealer_status'] ?? '') === 'approved';
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        $_SESSION['_created'] = time();
        self::$user = null;
        Database::execute('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $user['id']]);
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::forget('user_id');
        Session::regenerate();
    }

    public static function requireLogin(string $redirect = '/account'): void
    {
        if (!self::check()) {
            Session::set('intended_url', Request::path());
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect(url('/login?next=' . urlencode(Request::path())));
        }
        if (self::isAdmin() && !str_starts_with(Request::path(), '/admin')) {
            // Admins may browse the storefront; no restriction.
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            if (self::check()) {
                Session::flash('error', 'You do not have access to the administration area.');
                Response::redirect(url('/account'));
            }
            Session::set('intended_url', Request::path());
            Response::redirect(url('/admin/login'));
        }
    }

    public static function requireGuest(string $redirect = '/account'): void
    {
        if (self::check()) {
            Response::redirect(url($redirect));
        }
    }

    public static function isDealerPending(): bool
    {
        $user = self::user();
        return ($user['role'] ?? '') === 'dealer' && ($user['dealer_status'] ?? '') !== 'approved';
    }
}
