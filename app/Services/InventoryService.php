<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class InventoryService
{
    /**
     * Adjust stock and always write an audit row.
     */
    public function adjust(int $productId, int $delta, string $reason, ?int $userId = null, ?int $orderId = null): array
    {
        $product = Database::first('SELECT * FROM products WHERE id = ?', [$productId]);
        if (!$product) {
            return ['ok' => false, 'message' => 'Product not found.', 'stock' => 0];
        }
        $current = (int) $product['stock_qty'];
        $next = $current + $delta;
        if ($next < 0) {
            return ['ok' => false, 'message' => 'Stock cannot go below zero. Current stock is ' . $current . '.', 'stock' => $current];
        }

        Database::transaction(function () use ($product, $productId, $delta, $reason, $userId, $orderId, $current, $next): void {
            Database::execute('UPDATE products SET stock_qty = ?, manage_stock = 1, updated_at = ? WHERE id = ?', [$next, now(), $productId]);
            Database::insert('inventory_movements', [
                'product_id' => $productId,
                'order_id' => $orderId,
                'admin_id' => $userId,
                'delta' => $delta,
                'stock_after' => $next,
                'reason' => mb_substr($reason, 0, 190),
                'created_at' => now(),
            ]);
            $status = (string) $product['stock_status'];
            if ($status === 'out_of_stock' && $next > 0) {
                Database::execute("UPDATE products SET stock_status = 'in_stock' WHERE id = ?", [$productId]);
            } elseif ($status === 'in_stock' && $next <= 0 && (int) $product['backorder_allowed'] === 0) {
                Database::execute("UPDATE products SET stock_status = 'out_of_stock' WHERE id = ?", [$productId]);
            }
        });

        return ['ok' => true, 'message' => 'Stock updated from ' . $current . ' to ' . $next . '.', 'stock' => $next];
    }

    public function lowStock(int $limit = 20): array
    {
        return Database::select(
            'SELECT id, name, sku, stock_qty, low_stock_threshold, stock_status
             FROM products
             WHERE is_active = 1 AND manage_stock = 1 AND stock_qty <= low_stock_threshold
             ORDER BY stock_qty ASC, name ASC LIMIT ' . max(1, $limit)
        );
    }

    public function movements(int $productId, int $limit = 50): array
    {
        return Database::select(
            'SELECT m.*, u.first_name, u.last_name FROM inventory_movements m
             LEFT JOIN users u ON u.id = m.admin_id
             WHERE m.product_id = ? ORDER BY m.created_at DESC, m.id DESC LIMIT ' . max(1, $limit),
            [$productId]
        );
    }
}
