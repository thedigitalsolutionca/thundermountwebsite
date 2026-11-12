<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

final class TaxService
{
    public function enabled(): bool
    {
        return Settings::bool('tax.enabled', true);
    }

    public function configured(): bool
    {
        if (!$this->enabled()) {
            return true; // Deliberately disabled by the merchant.
        }
        return (int) Database::value('SELECT COUNT(*) FROM tax_rates WHERE is_active = 1') > 0;
    }

    public function rateFor(array $address): ?array
    {
        $country = strtoupper(trim((string) ($address['country'] ?? 'US'))) ?: 'US';
        $state = strtoupper(trim((string) ($address['state'] ?? '')));
        if ($state === '') {
            return null;
        }

        $row = Database::first(
            'SELECT * FROM tax_rates WHERE is_active = 1 AND UPPER(country) = ? AND UPPER(state) = ? ORDER BY id LIMIT 1',
            [$country, $state]
        );
        if ($row) {
            return $row;
        }
        return null;
    }

    /**
     * @return array{cents:int,rate:float,name:string,state:string}
     */
    public function calculate(int $taxableSubtotalCents, int $shippingCents, array $address): array
    {
        $zero = ['cents' => 0, 'rate' => 0.0, 'name' => '', 'state' => ''];
        if (!$this->enabled()) {
            return $zero;
        }
        $rate = $this->rateFor($address);
        if (!$rate) {
            return $zero;
        }
        $base = $taxableSubtotalCents;
        if (Settings::bool('tax.shipping_taxable', false)) {
            $base += $shippingCents;
        }
        $cents = (int) round($base * ((float) $rate['rate'] / 100));
        return [
            'cents' => $cents,
            'rate' => (float) $rate['rate'],
            'name' => (string) $rate['name'],
            'state' => (string) $rate['state'],
        ];
    }

    public function rates(): array
    {
        return Database::select('SELECT * FROM tax_rates ORDER BY country, state, name');
    }
}
