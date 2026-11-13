<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\DateRange;
use App\Core\Request;
use InvalidArgumentException;

/** Allowlisted, bounded cleanup. Never drops tables or erases payment/idempotency records. */
final class AdminDataService
{
    private const TYPES = [
        'messages' => ['contact_messages', 'Contact messages', 'subject', "1 = 1", 'Permanently removes the enquiry, sender details and message.'],
        'emails' => ['email_log', 'Email logs', 'subject', "t.status IN ('failed','sent')", 'Failed or sent email records only. Queued/in-progress mail is protected. Deleted bodies cannot be resent.'],
        'orders' => ['orders', 'Cancelled, never-paid orders', 'order_number', "t.status = 'cancelled' AND t.payment_status IN ('unpaid','failed') AND t.paid_at IS NULL AND t.shipped_at IS NULL AND t.completed_at IS NULL AND COALESCE(t.paypal_order_id,'') = '' AND COALESCE(t.paypal_capture_id,'') = '' AND COALESCE(t.paypal_payer_id,'') = '' AND t.refunded_total = 0 AND NOT EXISTS (SELECT 1 FROM inventory_movements m WHERE m.order_id = t.id) AND NOT EXISTS (SELECT 1 FROM discount_redemptions r WHERE r.order_id = t.id)", 'Only cancelled orders with no payment references, paid/shipped/completed timestamps, stock movements, refunds or redemptions. Related items, notes and status history are removed. Paid, refunded and PayPal-linked orders are retained.'],
        'subscribers' => ['newsletter_subscribers', 'Newsletter subscribers', 'email', '1 = 1', 'Removes newsletter subscription records. Export a backup first if needed.'],
        'waitlist' => ['product_waitlist', 'Product waitlist', 'email', '1 = 1', 'Removes availability notification registrations.'],
        'reviews' => ['reviews', 'Product reviews', 'title', '1 = 1', 'Removes selected reviews from the website and moderation queue.'],
        'carts' => ['carts', 'Abandoned carts', 'token', "t.updated_at < ?", 'Only carts not updated for at least 30 days. Active carts are protected. Cart items are removed with the cart.'],
        'customers' => ['users', 'Inactive customers without orders', 'email', "t.role = 'customer' AND t.status <> 'active' AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = t.id OR o.email = t.email) AND NOT EXISTS (SELECT 1 FROM dealers d WHERE d.user_id = t.id OR d.email = t.email) AND NOT EXISTS (SELECT 1 FROM carts c WHERE c.user_id = t.id)", 'Only inactive customer accounts without orders, dealer records or carts. Saved addresses are removed. Administrators and dealers are protected.'],
        'login_logs' => ['login_attempts', 'Old sign-in logs', 'email', 't.created_at < ?', 'Only logs older than 30 days. Recent security/rate-limit records are protected.'],
        'activity_logs' => ['admin_activity', 'Old admin activity', 'action', 't.created_at < ?', 'Only logs older than 90 days. A new audit entry records every cleanup.'],
    ];

    public function types(): array
    {
        return array_map(static fn ($definition) => ['label' => $definition[1], 'help' => $definition[4]], self::TYPES);
    }

    private function definition(string $type, string $before): array
    {
        if (!isset(self::TYPES[$type])) throw new InvalidArgumentException('Choose a supported data type.');
        $date = DateRange::date($before);
        if ($date > new \DateTimeImmutable('tomorrow')) throw new InvalidArgumentException('The cutoff cannot be later than tomorrow.');
        [$table, $label, $title, $predicate] = self::TYPES[$type];
        $params = [];
        if (in_array($type, ['carts', 'login_logs', 'activity_logs'], true)) $params[] = date('Y-m-d H:i:s', time() - ($type === 'activity_logs' ? 90 : 30) * 86400);
        $predicate .= ' AND t.created_at < ?';
        $params[] = $before . ' 00:00:00';
        return [$table, $title, $predicate, $params];
    }

    public function preview(string $type, string $before, int $page = 1, int $record = 0): array
    {
        [$table, $title, $where, $params] = $this->definition($type, $before);
        if ($record > 0) { $where .= ' AND t.id = ?'; $params[] = $record; }
        $count = (int) Database::value("SELECT COUNT(*) FROM {$table} t WHERE {$where}", $params);
        $page = max(1, min($page, max(1, (int) ceil($count / 50))));
        $offset = ($page - 1) * 50;
        $rows = Database::select("SELECT t.id, t.{$title} AS label, t.created_at FROM {$table} t WHERE {$where} ORDER BY t.id DESC LIMIT 50 OFFSET {$offset}", $params);
        return ['rows' => $rows, 'count' => $count, 'page' => $page, 'pages' => max(1, (int) ceil($count / 50))];
    }

    public function delete(string $type, string $before, array $ids, int $adminId): int
    {
        [$table, $title, $where, $params] = $this->definition($type, $before);
        if ($ids === [] || count($ids) > 50) throw new InvalidArgumentException('Select between 1 and 50 records from the preview.');
        foreach ($ids as $id) if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) throw new InvalidArgumentException('Invalid record selection.');
        $ids = array_unique(array_map('intval', $ids));
        sort($ids);
        return Database::transaction(function () use ($type, $table, $where, $params, $ids, $adminId): int {
            // Recheck each eligibility rule under a row lock before deleting anything.
            foreach ($ids as $id) {
                if (!Database::lockFirst("SELECT t.id FROM {$table} t WHERE t.id = ? AND ({$where})", array_merge([$id], $params))) {
                    throw new InvalidArgumentException('A selected record is protected, no longer eligible, or already removed. Refresh the preview. Nothing was deleted.');
                }
            }
            foreach ($ids as $id) {
                if ($type === 'orders') {
                    foreach (['order_items', 'order_notes', 'order_status_history'] as $child) Database::execute("DELETE FROM {$child} WHERE order_id = ?", [$id]);
                }
                if ($type === 'customers') Database::execute('DELETE FROM addresses WHERE user_id = ?', [$id]);
                if ($type === 'carts') Database::execute('DELETE FROM cart_items WHERE cart_id = ?', [$id]);
                Database::execute("DELETE FROM {$table} WHERE id = ?", [$id]);
                self::audit($adminId, 'record.delete', $type, (string) $id, 'Permanent cleanup');
            }
            self::audit($adminId, 'data.delete', $type, implode(',', $ids), count($ids) . ' records permanently deleted');
            return count($ids);
        });
    }

    public static function audit(int $adminId, string $action, string $type, string $id, string $meta = ''): void
    {
        Database::insert('admin_activity', ['user_id' => $adminId, 'action' => $action, 'entity_type' => $type, 'entity_id' => mb_substr($id, 0, 60), 'meta' => mb_substr($meta, 0, 255), 'ip' => Request::ip(), 'created_at' => now()]);
    }
}
