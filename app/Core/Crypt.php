<?php

declare(strict_types=1);

namespace App\Core;

/**
 * AES-256-GCM encryption for payment/SMTP credentials stored in the database.
 */
final class Crypt
{
    private static function key(): string
    {
        $key = (string) Config::get('app.key', '');
        if ($key === '') {
            return '';
        }
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            return $decoded !== false ? hash('sha256', $decoded, true) : '';
        }
        return hash('sha256', $key, true);
    }

    public static function encrypt(string $plaintext): ?string
    {
        $key = self::key();
        if ($key === '' || !function_exists('openssl_encrypt')) {
            return null;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return null;
        }
        return 'v1:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }
        if (!str_starts_with($payload, 'v1:')) {
            // Legacy/plain value (e.g. configured directly in the database).
            return $payload;
        }
        $key = self::key();
        if ($key === '') {
            return null;
        }
        $raw = base64_decode(substr($payload, 3), true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? null : $plain;
    }
}
