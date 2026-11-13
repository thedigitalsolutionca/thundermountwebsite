<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateRange
{
    /** Inclusive calendar dates, exclusive upper timestamp, in the configured store timezone. */
    public static function resolve(string $period, string $from = '', string $to = ''): array
    {
        $today = new DateTimeImmutable('today');
        $start = $today;
        $end = $today;
        if ($period === 'week') $start = $today->modify('monday this week');
        elseif ($period === 'month') $start = $today->modify('first day of this month');
        elseif ($period === 'custom') {
            $start = self::date($from);
            $end = self::date($to);
            if ($start > $end || $end > $today || $start->diff($end)->days > 365) {
                throw new InvalidArgumentException('Choose a valid range of up to 366 days, ending today or earlier.');
            }
        } elseif ($period !== 'today') throw new InvalidArgumentException('Choose Today, This week, This month, or Custom.');
        return ['period' => $period, 'from' => $start->format('Y-m-d'), 'to' => $end->format('Y-m-d'), 'start' => $start->format('Y-m-d 00:00:00'), 'end' => $end->modify('+1 day')->format('Y-m-d 00:00:00'), 'days' => (int) $start->diff($end)->days + 1, 'label' => $start->format('M j, Y') . ' – ' . $end->format('M j, Y')];
    }

    public static function date(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Enter valid start and end dates.');
        return $date;
    }
}
