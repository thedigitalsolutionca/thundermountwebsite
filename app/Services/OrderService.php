<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Money;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use Throwable;

final class OrderService
{
    public const STATUSES = ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'];

    public function findByNumber(string $number): ?array
    {
        $order = Database::first('SELECT * FROM orders WHERE order_number = ? LIMIT 1', [$number]);
        if (!$order) {
            return null;
        }
        $order['items'] = $this->items((int) $order['id']);
        $order['shipping_address_data'] = json_decode((string) $order['shipping_address'], true) ?: [];
        $order['billing_address_data'] = json_decode((string) $order['billing_address'], true) ?: [];
        $order['notes'] = Database::select(
            'SELECT * FROM order_notes WHERE order_id = ? ORDER BY created_at DESC',
            [$order['id']]
        );
        $order['history'] = Database::select(
            'SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC, id DESC',
            [$order['id']]
        );
        return $order;
    }

    public function findByPayPalOrder(string $paypalOrderId): ?array
    {
        $order = Database::first('SELECT * FROM orders WHERE paypal_order_id = ? LIMIT 1', [$paypalOrderId]);
        return $order ? $this->findByNumber($order['order_number']) : null;
    }

    public function items(int $orderId): array
    {
        return Database::select('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    public function forUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        return Database::select(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            [$userId]
        );
    }

    public function countForUser(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$userId]);
    }

    public function forDealer(int $dealerId, int $limit = 20, int $offset = 0): array
    {
        return Database::select(
            'SELECT * FROM orders WHERE dealer_id = ? ORDER BY created_at DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            [$dealerId]
        );
    }

    public function countForDealer(int $dealerId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM orders WHERE dealer_id = ?', [$dealerId]);
    }

    /**
     * Guest order lookup requires both order number and email.
     */
    public function findForGuest(string $number, string $email): ?array
    {
        $order = Database::first(
            'SELECT * FROM orders WHERE order_number = ? AND LOWER(email) = ? LIMIT 1',
            [trim($number), mb_strtolower(trim($email))]
        );
        return $order ? $this->findByNumber($order['order_number']) : null;
    }

    public function adminList(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = $this->adminWhere($filters);
        $rows = Database::select(
            "SELECT * FROM orders WHERE {$where} ORDER BY created_at DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
        return $rows;
    }

    public function adminCount(array $filters = []): int
    {
        [$where, $params] = $this->adminWhere($filters);
        return (int) Database::value("SELECT COUNT(*) FROM orders WHERE {$where}", $params);
    }

    private function adminWhere(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['payment_status'])) {
            $where[] = 'payment_status = ?';
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . trim((string) $filters['q']) . '%';
            $where[] = '(order_number LIKE ? OR email LIKE ? OR customer_note LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        return [implode(' AND ', $where), $params];
    }

    /**
     * Finalise a PayPal capture. Idempotent.
     *
     * @return array{ok:bool,order:?array,message:string}
     */
    public function finalizePayPalCapture(string $orderNumber, array $capture): array
    {
        $order = Database::first('SELECT * FROM orders WHERE order_number = ? LIMIT 1', [$orderNumber]);
        if (!$order) {
            return ['ok' => false, 'order' => null, 'message' => 'Order not found.'];
        }
        if ($order['payment_status'] === 'paid') {
            return ['ok' => true, 'order' => $this->findByNumber($orderNumber), 'message' => 'Payment already recorded.'];
        }

        $captureId = (string) ($capture['id'] ?? '');
        $capturedAmount = null;
        $currency = 'USD';
        if (isset($capture['amount']['value'])) {
            $capturedAmount = Money::toCents($capture['amount']['value']);
            $currency = (string) ($capture['amount']['currency_code'] ?? 'USD');
        } elseif (isset($capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'])) {
            $entry = $capture['purchase_units'][0]['payments']['captures'][0];
            $captureId = (string) ($entry['id'] ?? $captureId);
            $capturedAmount = Money::toCents($entry['amount']['value']);
            $currency = (string) ($entry['amount']['currency_code'] ?? 'USD');
        }

        $expected = Money::toCents($order['total']);
        if ($capturedAmount !== null && $capturedAmount !== $expected) {
            Logger::error('PayPal capture amount mismatch', [
                'order' => $orderNumber,
                'expected' => $expected,
                'captured' => $capturedAmount,
                'currency' => $currency,
            ]);
            return ['ok' => false, 'order' => null, 'message' => 'The captured amount did not match the order total. Please contact support.'];
        }
        if ($currency !== (string) $order['currency']) {
            return ['ok' => false, 'order' => null, 'message' => 'Currency mismatch on the payment.'];
        }

        try {
            Database::transaction(function () use ($order, $captureId, $capture): void {
                $locked = Database::lockFirst('SELECT * FROM orders WHERE id = ?', [(int) $order['id']]);
                if (!$locked || $locked['payment_status'] === 'paid') {
                    return;
                }
                $payerId = (string) ($capture['payer']['payer_id'] ?? '');
                Database::execute(
                    'UPDATE orders SET payment_status = ?, status = ?, paypal_capture_id = ?, paypal_payer_id = ?, paid_at = ?, updated_at = ? WHERE id = ?',
                    ['paid', 'paid', $captureId, $payerId, now(), now(), $order['id']]
                );
                Database::insert('order_status_history', [
                    'order_id' => $order['id'],
                    'from_status' => 'pending',
                    'to_status' => 'paid',
                    'note' => 'PayPal payment captured',
                    'created_at' => now(),
                ]);
                $this->decrementStockForOrder((int) $order['id']);
                $this->recordDiscount((int) $order['id'], $order);
            });
        } catch (Throwable $e) {
            Logger::error('Failed to finalize order', ['order' => $orderNumber, 'error' => $e->getMessage()]);
            return ['ok' => false, 'order' => null, 'message' => 'We captured the payment but could not update the order. Our team has been notified.'];
        }

        $order = $this->findByNumber($orderNumber);
        if ($order) {
            $this->sendOrderEmails($order);
            if (Session::get('pending_order_number') === $orderNumber) {
                cart()->clear();
                Session::forget('pending_order_number');
            }
            Session::set('last_order_number', $orderNumber);
        }
        return ['ok' => true, 'order' => $order, 'message' => 'Payment received.'];
    }

    public function markPaidManually(int $orderId, ?int $adminId, string $note = ''): array
    {
        $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return ['ok' => false, 'message' => 'Order not found.'];
        }
        if ($order['payment_status'] === 'paid') {
            return ['ok' => false, 'message' => 'This order is already marked paid.'];
        }
        Database::transaction(function () use ($order, $adminId, $note): void {
            Database::execute(
                'UPDATE orders SET payment_status = ?, status = ?, paid_at = ?, updated_at = ? WHERE id = ?',
                ['paid', 'paid', now(), now(), $order['id']]
            );
            Database::insert('order_status_history', [
                'order_id' => $order['id'],
                'from_status' => $order['status'],
                'to_status' => 'paid',
                'note' => $note !== '' ? $note : 'Payment recorded by admin',
                'admin_id' => $adminId,
                'created_at' => now(),
            ]);
            $this->decrementStockForOrder((int) $order['id']);
            $this->recordDiscount((int) $order['id'], $order);
        });
        $fresh = $this->findByNumber($order['order_number']);
        if ($fresh) {
            $this->sendOrderEmails($fresh);
        }
        return ['ok' => true, 'message' => 'Payment recorded and customer notified.'];
    }

    public function decrementStockForOrder(int $orderId, string $reason = 'order_paid'): void
    {
        $already = (int) Database::value('SELECT COUNT(*) FROM inventory_movements WHERE order_id = ? AND delta < 0', [$orderId]);
        if ($already > 0) {
            return;
        }
        $items = $this->items($orderId);
        foreach ($items as $item) {
            if (empty($item['product_id'])) {
                continue;
            }
            $product = Database::lockFirst('SELECT * FROM products WHERE id = ?', [(int) $item['product_id']]);
            if (!$product || (int) $product['manage_stock'] !== 1) {
                continue;
            }
            $qty = (int) $item['qty'];
            $newQty = max(0, (int) $product['stock_qty'] - $qty);
            Database::execute('UPDATE products SET stock_qty = ?, updated_at = ? WHERE id = ?', [$newQty, now(), $product['id']]);
            Database::insert('inventory_movements', [
                'product_id' => $product['id'],
                'delta' => -$qty,
                'stock_after' => $newQty,
                'reason' => $reason,
                'order_id' => $orderId,
                'admin_id' => null,
                'note' => 'Stock decremented when payment was captured',
                'created_at' => now(),
            ]);
            if ($newQty <= (int) $product['low_stock_threshold']) {
                Logger::info('Low stock after order', ['product' => $product['sku'], 'remaining' => $newQty]);
            }
        }
    }

    private function recordDiscount(int $orderId, array $order): void
    {
        if (empty($order['discount_code'])) {
            return;
        }
        $existing = Database::value('SELECT COUNT(*) FROM discount_redemptions WHERE order_id = ?', [$orderId]);
        if ((int) $existing > 0) {
            return;
        }
        $discount = Database::first('SELECT * FROM discounts WHERE UPPER(code) = ? LIMIT 1', [strtoupper($order['discount_code'])]);
        if (!$discount) {
            return;
        }
        (new DiscountService())->recordRedemption(
            $discount,
            $orderId,
            $order['user_id'] !== null ? (int) $order['user_id'] : null,
            (string) $order['email'],
            Money::toCents($order['discount_total'])
        );
    }

    public function transition(int $orderId, string $toStatus, ?int $adminId, string $note = '', bool $restock = false): array
    {
        $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return ['ok' => false, 'message' => 'Order not found.'];
        }
        $toStatus = strtolower($toStatus);
        if (!in_array($toStatus, self::STATUSES, true)) {
            return ['ok' => false, 'message' => 'Invalid order status.'];
        }
        if ($order['status'] === $toStatus) {
            return ['ok' => false, 'message' => 'The order is already ' . status_label($toStatus) . '.'];
        }
        if (!$this->canTransition($order['status'], $toStatus)) {
            return ['ok' => false, 'message' => 'That status change is not allowed from ' . status_label($order['status']) . '.'];
        }

        Database::transaction(function () use ($order, $toStatus, $adminId, $note, $restock): void {
            $data = ['status' => $toStatus, 'updated_at' => now()];
            if ($toStatus === 'shipped') {
                $data['shipped_at'] = now();
            } elseif ($toStatus === 'completed') {
                $data['completed_at'] = now();
            } elseif ($toStatus === 'cancelled') {
                $data['cancelled_at'] = now();
            }
            Database::update('orders', $data, 'id = ?', [$order['id']]);
            Database::insert('order_status_history', [
                'order_id' => $order['id'],
                'from_status' => $order['status'],
                'to_status' => $toStatus,
                'note' => $note,
                'admin_id' => $adminId,
                'created_at' => now(),
            ]);
            if ($restock && in_array($toStatus, ['cancelled', 'refunded'], true)) {
                $this->restockOrder((int) $order['id'], $adminId);
            }
        });

        $fresh = $this->findByNumber($order['order_number']);
        if ($fresh && $toStatus === 'shipped') {
            $this->sendShippedEmail($fresh);
        }
        if ($fresh && $toStatus === 'cancelled') {
            $this->sendStatusEmail($fresh, 'Order cancelled', 'Your order ' . $fresh['order_number'] . ' has been cancelled.');
        }
        return ['ok' => true, 'message' => 'Order marked ' . status_label($toStatus) . '.'];
    }

    public function canTransition(string $from, string $to): bool
    {
        $map = [
            'pending' => ['cancelled'],
            'paid' => ['processing', 'cancelled', 'refunded'],
            'processing' => ['shipped', 'cancelled', 'refunded'],
            'shipped' => ['completed', 'refunded'],
            'completed' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];
        return in_array($to, $map[$from] ?? [], true);
    }

    public function restockOrder(int $orderId, ?int $adminId): void
    {
        $already = (int) Database::value(
            "SELECT COUNT(*) FROM inventory_movements WHERE order_id = ? AND reason = 'restock'",
            [$orderId]
        );
        if ($already > 0) {
            return;
        }
        foreach ($this->items($orderId) as $item) {
            if (empty($item['product_id'])) {
                continue;
            }
            $product = Database::first('SELECT * FROM products WHERE id = ?', [(int) $item['product_id']]);
            if (!$product || (int) $product['manage_stock'] !== 1) {
                continue;
            }
            $newQty = (int) $product['stock_qty'] + (int) $item['qty'];
            Database::execute(
                'UPDATE products SET stock_qty = ?, updated_at = ? WHERE id = ?',
                [$newQty, now(), $product['id']]
            );
            Database::insert('inventory_movements', [
                'product_id' => $product['id'],
                'delta' => (int) $item['qty'],
                'stock_after' => $newQty,
                'reason' => 'restock',
                'order_id' => $orderId,
                'admin_id' => $adminId,
                'note' => 'Stock restored after cancellation/refund',
                'created_at' => now(),
            ]);
        }
    }

    public function addNote(int $orderId, ?int $adminId, string $note, bool $customerVisible): void
    {
        Database::insert('order_notes', [
            'order_id' => $orderId,
            'admin_id' => $adminId,
            'note' => $note,
            'is_customer_visible' => $customerVisible ? 1 : 0,
            'created_at' => now(),
        ]);
    }

    public function setTracking(int $orderId, string $carrier, string $number): void
    {
        Database::execute(
            'UPDATE orders SET tracking_carrier = ?, tracking_number = ?, updated_at = ? WHERE id = ?',
            [substr($carrier, 0, 80), substr($number, 0, 80), now(), $orderId]
        );
    }

    public function sendOrderEmails(array $order): void
    {
        try {
            $body = Mailer::template('orders/confirmed', ['order' => $order]);
            Mailer::send(
                (string) $order['email'],
                'Order ' . $order['order_number'] . ' confirmed — Thunder Mounts',
                $body
            );
            $adminEmail = (string) Settings::get('store.notification_email', 'thunder@thundermounts.com');
            if ($adminEmail !== '') {
                Mailer::send(
                    $adminEmail,
                    'New order ' . $order['order_number'] . ' — ' . Money::format((float) $order['total']),
                    Mailer::template('orders/admin-notification', ['order' => $order])
                );
            }
        } catch (Throwable $e) {
            Logger::error('Order email failed', ['order' => $order['order_number'], 'error' => $e->getMessage()]);
        }
    }

    public function sendShippedEmail(array $order): void
    {
        try {
            Mailer::send(
                (string) $order['email'],
                'Order ' . $order['order_number'] . ' has shipped — Thunder Mounts',
                Mailer::template('orders/shipped', ['order' => $order])
            );
        } catch (Throwable $e) {
            Logger::error('Shipping email failed', ['error' => $e->getMessage()]);
        }
    }

    public function sendStatusEmail(array $order, string $subject, string $message): void
    {
        try {
            Mailer::send(
                (string) $order['email'],
                $subject . ' — ' . $order['order_number'],
                Mailer::template('orders/status', ['order' => $order, 'message' => $message])
            );
        } catch (Throwable $e) {
            Logger::error('Status email failed', ['error' => $e->getMessage()]);
        }
    }

    public function recent(int $limit = 8): array
    {
        return Database::select('SELECT * FROM orders ORDER BY created_at DESC LIMIT ' . max(1, $limit));
    }

    public function statusCounts(): array
    {
        $rows = Database::select('SELECT status, COUNT(*) AS total FROM orders GROUP BY status');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['total'];
        }
        return $out;
    }

    public function revenueSince(string $date): int
    {
        return Money::toCents(Database::value(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status IN ('paid','partially_refunded') AND created_at >= ?",
            [$date . ' 00:00:00'],
            0
        ));
    }

    public function monthlyRevenue(int $months = 12): array
    {
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-$i months"));
            $end = date('Y-m-t', strtotime($start));
            $total = Database::value(
                "SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status IN ('paid','partially_refunded') AND created_at BETWEEN ? AND ?",
                [$start . ' 00:00:00', $end . ' 23:59:59'],
                0
            );
            $out[] = ['label' => date('M Y', strtotime($start)), 'short' => date('M', strtotime($start)), 'value' => Money::toCents($total)];
        }
        return $out;
    }

    public function topProducts(int $limit = 5): array
    {
        return Database::select(
            "SELECT oi.name, oi.sku, SUM(oi.qty) AS qty, SUM(oi.line_total) AS revenue
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.payment_status IN ('paid','partially_refunded')
             GROUP BY oi.name, oi.sku ORDER BY qty DESC LIMIT " . max(1, $limit)
        );
    }

    public function addressText(?array $address): string
    {
        if (!$address) {
            return '—';
        }
        $lines = [];
        $name = trim(($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? ''));
        if (!empty($address['company'])) {
            $name = trim($name . ' · ' . $address['company']);
        }
        $lines[] = $name;
        if (!empty($address['address1'])) {
            $lines[] = $address['address1'];
        }
        if (!empty($address['address2'])) {
            $lines[] = $address['address2'];
        }
        $lines[] = trim(($address['city'] ?? '') . ', ' . ($address['state'] ?? '') . ' ' . ($address['postcode'] ?? ''));
        if (!empty($address['country']) && $address['country'] !== 'US') {
            $lines[] = $address['country'];
        }
        if (!empty($address['phone'])) {
            $lines[] = $address['phone'];
        }
        return implode("\n", array_filter($lines));
    }

    public function renderInvoice(array $order, bool $packing = false): string
    {
        return View::render('admin/orders/print', [
            'order' => $order,
            'packing' => $packing,
            'storeName' => (string) Settings::get('store.name', 'Thunder Mounts'),
            'storeLegal' => (string) Settings::get('store.legal_name', 'Thunder Mount Garage Systems LLC'),
            'storeAddress' => (string) Settings::get('store.address', ''),
            'storePhone' => (string) Settings::get('store.phone', ''),
            'storeEmail' => (string) Settings::get('store.email', ''),
        ]);
    }
}
