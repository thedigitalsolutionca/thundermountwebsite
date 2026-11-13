<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Money;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Services\CartService;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $base = (string) Config::get('app.base_path', '');
        $path = '/' . ltrim($path, '/');
        return ($base === '' ? '' : $base) . $path;
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(string $path = '/'): string
    {
        $configured = (string) Config::get('app.url', '');
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . ltrim($path, '/');
        }
        return (Request::isSecure() ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $relative = '/assets/' . ltrim($path, '/');
        $file = dirname(__DIR__) . '/public' . $relative;
        $version = is_file($file) ? substr((string) filemtime($file), -6) : '1';
        return url($relative) . '?v=' . $version;
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string
    {
        if ($path === null || trim($path) === '') {
            return asset('img/tm-mount-1.jpg');
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return url('/' . ltrim($path, '/'));
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (!function_exists('money')) {
    function money(int|string|float|null $amount): string
    {
        return Money::format($amount);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}

if (!function_exists('old')) {
    /**
     * Old form input. Call with no arguments to get every remembered field.
     */
    function old(?string $key = null, mixed $default = ''): mixed
    {
        $input = $_SESSION['_old'] ?? [];
        if ($key === null) {
            return is_array($input) ? $input : [];
        }
        return $input[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /** @return array<string,string> */
    function errors(): array
    {
        return $_SESSION['_errors'] ?? [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        $errors = errors();
        return $errors[$field] ?? null;
    }
}

if (!function_exists('remember_input')) {
    function remember_input(array $input): void
    {
        unset($input['_token'], $input['password'], $input['password_confirmation'], $input['card']);
        $_SESSION['_old'] = $input;
    }
}

if (!function_exists('clear_input')) {
    function clear_input(): void
    {
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }
}

if (!function_exists('cart')) {
    function cart(): CartService
    {
        return CartService::instance();
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        return Request::path();
    }
}

if (!function_exists('is_active')) {
    function is_active(string $prefix, string $class = 'active'): string
    {
        $path = Request::path();
        if ($prefix === '/') {
            return $path === '/' ? $class : '';
        }
        return str_starts_with($path, $prefix) ? $class : '';
    }
}

if (!function_exists('fmt_date')) {
    function fmt_date(?string $date, string $format = 'M j, Y'): string
    {
        if (!$date) {
            return '—';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '—';
    }
}

if (!function_exists('fmt_datetime')) {
    function fmt_datetime(?string $date): string
    {
        return fmt_date($date, 'M j, Y \a\t g:i A');
    }
}

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('badge_class')) {
    function badge_class(string $status): string
    {
        return match ($status) {
            'paid', 'completed', 'approved', 'active', 'sent', 'in_stock' => 'badge-green',
            'processing', 'shipped', 'pending', 'preorder' => 'badge-amber',
            'cancelled', 'refunded', 'rejected', 'failed', 'disabled', 'out_of_stock' => 'badge-red',
            'quote', 'new', 'read' => 'badge-blue',
            default => 'badge-gray',
        };
    }
}

if (!function_exists('query_string')) {
    function query_string(array $overrides = [], array $remove = []): string
    {
        $params = array_merge($_GET, $overrides);
        foreach ($remove as $key) {
            unset($params[$key]);
        }
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $params === [] ? '' : '?' . http_build_query($params);
    }
}

if (!function_exists('page_url')) {
    function page_url(int $page, array $overrides = []): string
    {
        $params = array_merge($_GET, $overrides);
        $params['page'] = $page;
        return current_path() . '?' . http_build_query($params);
    }
}

if (!function_exists('json_ld')) {
    function json_ld(array $data): string
    {
        return '<script type="application/ld+json">' . json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        ) . '</script>';
    }
}

if (!function_exists('star_rating')) {
    function star_rating(float $rating): string
    {
        $rating = max(0, min(5, $rating));
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $out .= '<span class="' . ($i <= round($rating) ? 'star on' : 'star') . '" aria-hidden="true">★</span>';
        }
        return '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' out of 5 stars">' . $out . '</span>';
    }
}

if (!function_exists('selected')) {
    function selected(mixed $a, mixed $b): string
    {
        return (string) $a === (string) $b ? ' selected' : '';
    }
}

if (!function_exists('checked')) {
    function checked(mixed $value, bool $default = false): string
    {
        $is = $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
        return $is ? ' checked' : '';
    }
}

if (!function_exists('icon')) {
    function icon(string $name, string $class = ''): string
    {
        return \App\Core\Icons::svg($name, $class);
    }
}

if (!function_exists('dealer_program_enabled')) {
    function dealer_program_enabled(): bool
    {
        return \App\Services\DealerService::isProgramEnabled();
    }
}

if (!function_exists('dealer_pages_enabled')) {
    function dealer_pages_enabled(): bool
    {
        return \App\Services\DealerService::arePagesEnabled();
    }
}

if (!function_exists('dealer_applications_enabled')) {
    function dealer_applications_enabled(): bool
    {
        return \App\Services\DealerService::areApplicationsEnabled();
    }
}

if (!function_exists('dealer_pricing_enabled')) {
    function dealer_pricing_enabled(): bool
    {
        return \App\Services\DealerService::isPricingEnabled();
    }
}
