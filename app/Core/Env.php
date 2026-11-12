<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal, dependency-free .env reader.
 */
final class Env
{
    /** @var array<string,string>|null */
    private static ?array $values = null;

    public static function load(string $path): void
    {
        $values = [];
        if (is_file($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                    $quote = $value[0];
                    $value = trim($value, $quote);
                } else {
                    // Inline comments for unquoted values.
                    $hash = strpos($value, ' #');
                    if ($hash !== false) {
                        $value = rtrim(substr($value, 0, $hash));
                    }
                }
                $values[$key] = $value;
            }
        }
        self::$values = $values;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$values === null) {
            return $default;
        }
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $env = getenv($key);
        return $env === false ? $default : $env;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null || $value === '' ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
