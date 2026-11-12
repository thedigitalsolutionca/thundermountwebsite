<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . $path, true, $status);
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with($referer, self::origin())) {
            self::redirect($referer);
        }
        self::redirect($fallback);
    }

    public static function origin(): string
    {
        $scheme = Request::isSecure() ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function xml(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=utf-8');
        echo $content;
        exit;
    }

    public static function text(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
        exit;
    }

    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https://www.paypal.com; img-src 'self' data: https://www.paypalobjects.com https://*.paypal.com; script-src 'self' 'unsafe-inline' https://www.paypal.com https://www.paypalobjects.com https://applepay.cdn-apple.com https://pay.google.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self' https://www.paypal.com https://api-m.paypal.com https://api-m.sandbox.paypal.com https://*.paypal.com https://applepay.cdn-apple.com; frame-src https://www.paypal.com https://*.paypal.com https://applepay.cdn-apple.com https://pay.google.com; object-src 'none'");
        if (Request::isSecure()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
