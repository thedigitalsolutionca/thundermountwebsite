<?php

declare(strict_types=1);

namespace App\Core;

/**
 * All order arithmetic is performed in integer cents to avoid float drift.
 */
final class Money
{
    public static function toCents(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function format(int|string|float|null $amount, bool $withCode = false): string
    {
        $value = is_int($amount) ? $amount / 100 : (float) $amount;
        return '$' . number_format($value, 2, '.', ',') . ($withCode ? ' USD' : '');
    }

    public static function formatCents(int $cents): string
    {
        return '$' . number_format($cents / 100, 2, '.', ',');
    }

    public static function percent(int $subtotalCents, float $percent): int
    {
        return (int) round($subtotalCents * ($percent / 100));
    }
}
