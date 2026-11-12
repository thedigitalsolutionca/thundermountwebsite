<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Money;

final class DiscountService
{
    /**
     * @return array{ok:bool,error:?string,discount:?array,amount_cents:int}
     */
    public function validate(?string $code, int $subtotalCents, ?string $email = null, ?int $userId = null): array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return ['ok' => false, 'error' => 'Enter a discount code.', 'discount' => null, 'amount_cents' => 0];
        }

        $discount = Database::first('SELECT * FROM discounts WHERE UPPER(code) = ? LIMIT 1', [$code]);
        if (!$discount) {
            return ['ok' => false, 'error' => 'That discount code is not valid.', 'discount' => null, 'amount_cents' => 0];
        }
        if ((int) $discount['is_active'] !== 1) {
            return ['ok' => false, 'error' => 'That discount code is no longer available.', 'discount' => null, 'amount_cents' => 0];
        }

        $now = time();
        if (!empty($discount['starts_at']) && strtotime($discount['starts_at']) > $now) {
            return ['ok' => false, 'error' => 'That discount code is not active yet.', 'discount' => null, 'amount_cents' => 0];
        }
        if (!empty($discount['ends_at']) && strtotime($discount['ends_at']) < $now) {
            return ['ok' => false, 'error' => 'That discount code has expired.', 'discount' => null, 'amount_cents' => 0];
        }
        if ((int) $discount['dealer_only'] === 1 && !Auth::isDealer()) {
            return ['ok' => false, 'error' => 'That code is available to approved dealers only.', 'discount' => null, 'amount_cents' => 0];
        }
        if ($subtotalCents < Money::toCents($discount['min_subtotal'])) {
            return [
                'ok' => false,
                'error' => 'This code requires a subtotal of at least ' . Money::format((float) $discount['min_subtotal']) . '.',
                'discount' => null,
                'amount_cents' => 0,
            ];
        }
        if ($discount['usage_limit'] !== null && (int) $discount['used_count'] >= (int) $discount['usage_limit']) {
            return ['ok' => false, 'error' => 'That discount code has reached its usage limit.', 'discount' => null, 'amount_cents' => 0];
        }
        if ($discount['per_user_limit'] !== null && ($userId !== null || $email !== null)) {
            $used = (int) Database::value(
                'SELECT COUNT(*) FROM discount_redemptions dr
                 WHERE dr.discount_id = ? AND (dr.user_id = ? OR (dr.email <> \'\' AND dr.email = ?))',
                [$discount['id'], $userId ?? 0, $email ?? '']
            );
            if ($used >= (int) $discount['per_user_limit']) {
                return ['ok' => false, 'error' => 'You have already used this discount code.', 'discount' => null, 'amount_cents' => 0];
            }
        }

        $amount = $this->amountFor($discount, $subtotalCents);
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'This discount does not apply to your order.', 'discount' => null, 'amount_cents' => 0];
        }

        return ['ok' => true, 'error' => null, 'discount' => $discount, 'amount_cents' => $amount];
    }

    public function amountFor(array $discount, int $subtotalCents): int
    {
        if (($discount['type'] ?? 'percent') === 'fixed') {
            return min($subtotalCents, Money::toCents($discount['value']));
        }
        $percent = max(0.0, min(100.0, (float) $discount['value']));
        return min($subtotalCents, (int) round($subtotalCents * ($percent / 100)));
    }

    public function recordRedemption(array $discount, int $orderId, ?int $userId, string $email, int $amountCents): void
    {
        Database::insert('discount_redemptions', [
            'discount_id' => (int) $discount['id'],
            'order_id' => $orderId,
            'user_id' => $userId,
            'email' => $email,
            'amount' => Money::fromCents($amountCents),
            'created_at' => now(),
        ]);
        Database::execute('UPDATE discounts SET used_count = used_count + 1, updated_at = ? WHERE id = ?', [now(), $discount['id']]);
    }
}
