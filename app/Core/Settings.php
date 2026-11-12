<?php

declare(strict_types=1);

namespace App\Core;

final class Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::select('SELECT `key`, `value` FROM settings') as $row) {
                    self::$cache[$row['key']] = (string) $row['value'];
                }
            } catch (\Throwable) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        if (!array_key_exists($key, $all)) {
            return $default;
        }
        $value = $all[$key];
        if (str_starts_with($key, 'encrypted.')) {
            return Crypt::decrypt($value) ?? $default;
        }
        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null || $value === '' ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $isEncrypted = str_starts_with($key, 'encrypted.');
        $stored = $value === null ? '' : (string) $value;
        if ($isEncrypted) {
            $stored = $value === '' || $value === null ? '' : (Crypt::encrypt((string) $value) ?? '');
        }

        $existing = Database::value('SELECT COUNT(*) FROM settings WHERE `key` = ?', [$key]);
        if ((int) $existing > 0) {
            Database::execute(
                'UPDATE settings SET `value` = ?, group_name = ?, updated_at = ? WHERE `key` = ?',
                [$stored, $group, now(), $key]
            );
        } else {
            Database::insert('settings', [
                'key' => $key,
                'value' => $stored,
                'group_name' => $group,
                'updated_at' => now(),
            ]);
        }
        self::$cache = null;
    }

    public static function group(string $group): array
    {
        $rows = Database::select(
            'SELECT `key`, `value` FROM settings WHERE group_name = ? ORDER BY `key`',
            [$group]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    public static function forget(): void
    {
        self::$cache = null;
    }
}
