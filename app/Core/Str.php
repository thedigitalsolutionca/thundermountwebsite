<?php

declare(strict_types=1);

namespace App\Core;

final class Str
{
    public static function slug(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'item-' . bin2hex(random_bytes(3));
    }

    public static function random(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function orderNumber(): string
    {
        return 'TM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public static function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return '***';
        }
        [$local, $domain] = explode('@', $email, 2);
        return substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 1)) . '@' . $domain;
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $initials !== '' ? $initials : 'TM';
    }

    public static function excerpt(string $html, int $length = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
    }

    public static function nl2br(string $value): string
    {
        return nl2br($value, false);
    }
}
