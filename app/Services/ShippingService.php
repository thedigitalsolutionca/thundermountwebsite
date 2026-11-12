<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;

final class ShippingService
{
    /**
     * @return array<int,array{id:int,name:string,cents:int,description:string}>
     */
    public function options(array $address, int $subtotalCents, float $weightLb = 0.0): array
    {
        $country = strtoupper(trim((string) ($address['country'] ?? 'US'))) ?: 'US';
        $state = strtoupper(trim((string) ($address['state'] ?? '')));

        $zones = Database::select('SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY is_default ASC, id ASC');
        $zone = null;
        foreach ($zones as $candidate) {
            $countries = array_filter(array_map('trim', explode(',', strtoupper((string) $candidate['countries']))));
            if ($countries !== [] && !in_array($country, $countries, true)) {
                continue;
            }
            $statesRaw = trim((string) ($candidate['states'] ?? ''));
            if ($statesRaw !== '' && $statesRaw !== '*') {
                $states = array_filter(array_map('trim', explode(',', strtoupper($statesRaw))));
                if (!in_array($state, $states, true)) {
                    continue;
                }
            }
            $zone = $candidate;
            break;
        }
        if (!$zone) {
            foreach ($zones as $candidate) {
                if ((int) $candidate['is_default'] === 1) {
                    $zone = $candidate;
                    break;
                }
            }
        }
        if (!$zone) {
            return [];
        }

        $methods = Database::select(
            'SELECT * FROM shipping_methods WHERE zone_id = ? AND is_active = 1 ORDER BY sort_order, id',
            [$zone['id']]
        );

        $options = [];
        foreach ($methods as $method) {
            if ($method['max_weight'] !== null && $weightLb > (float) $method['max_weight']) {
                continue;
            }
            if ($weightLb < (float) $method['min_weight']) {
                continue;
            }
            $cents = $this->costFor($method, $subtotalCents, $weightLb);
            if ($cents === null) {
                continue;
            }
            $eta = '';
            if ($method['est_days_min'] !== null && $method['est_days_max'] !== null) {
                $eta = $method['est_days_min'] . '–' . $method['est_days_max'] . ' business days';
            } elseif ($method['est_days_min'] !== null) {
                $eta = $method['est_days_min'] . '+ business days';
            }
            $options[] = [
                'id' => (int) $method['id'],
                'name' => (string) $method['name'],
                'cents' => $cents,
                'description' => $eta,
                'zone' => $zone['name'],
            ];
        }
        return $options;
    }

    public function costFor(array $method, int $subtotalCents, float $weightLb): ?int
    {
        if ($method['type'] === 'free') {
            return 0;
        }
        if ($method['free_over'] !== null && $method['free_over'] !== '' && $subtotalCents >= Money::toCents($method['free_over'])) {
            return 0;
        }
        if ($method['type'] === 'weight') {
            $units = max(1, (int) ceil($weightLb));
            return Money::toCents($method['rate']) + ($units * Money::toCents($method['per_unit']));
        }
        return Money::toCents($method['rate']);
    }

    public function findOption(int $methodId, array $address, int $subtotalCents, float $weightLb): ?array
    {
        foreach ($this->options($address, $subtotalCents, $weightLb) as $option) {
            if ($option['id'] === $methodId) {
                return $option;
            }
        }
        return null;
    }

    public function configured(): bool
    {
        $count = Database::value(
            'SELECT COUNT(*) FROM shipping_methods m INNER JOIN shipping_zones z ON z.id = m.zone_id
             WHERE m.is_active = 1 AND z.is_active = 1'
        );
        return (int) $count > 0;
    }

    public function totalWeight(array $items): float
    {
        $weight = 0.0;
        foreach ($items as $item) {
            $productWeight = (float) ($item['product']['weight'] ?? 0);
            $weight += $productWeight * (int) $item['qty'];
        }
        return round($weight, 2);
    }

    public function zones(): array
    {
        return Database::select('SELECT * FROM shipping_zones ORDER BY is_default, name');
    }

    public function methods(): array
    {
        return Database::select(
            'SELECT m.*, z.name AS zone_name FROM shipping_methods m
             INNER JOIN shipping_zones z ON z.id = m.zone_id ORDER BY z.name, m.sort_order, m.id'
        );
    }
}
