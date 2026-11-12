<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\OrderService;

final class DashboardController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $today = date('Y-m-d 00:00:00');
        $monthStart = date('Y-m-01 00:00:00');
        $paid = "payment_status IN ('paid','partially_refunded')";

        $revenueToday = (float) Database::value("SELECT COALESCE(SUM(total),0) FROM orders WHERE {$paid} AND paid_at >= ?", [$today], 0);
        $revenueMonth = (float) Database::value("SELECT COALESCE(SUM(total),0) FROM orders WHERE {$paid} AND paid_at >= ?", [$monthStart], 0);
        $ordersToday = (int) Database::value('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$today]);
        $openOrders = (int) Database::value("SELECT COUNT(*) FROM orders WHERE status IN ('paid','processing')");
        $awaitingPayment = (int) Database::value("SELECT COUNT(*) FROM orders WHERE status = 'pending' AND payment_status = 'unpaid'");
        $customers = (int) Database::value("SELECT COUNT(*) FROM users WHERE role IN ('customer','dealer')");
        $pendingDealers = (int) Database::value("SELECT COUNT(*) FROM dealers WHERE status = 'pending'");
        $newMessages = (int) Database::value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
        $pendingReviews = (int) Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");

        $recentOrders = (new OrderService())->adminList([], 8, 0);
        $lowStock = (new InventoryService())->lowStock(8);

        $topProducts = Database::select(
            "SELECT oi.product_id, oi.name, oi.sku, SUM(oi.qty) AS units, SUM(oi.line_total) AS revenue
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.{$paid}
             GROUP BY oi.product_id, oi.name, oi.sku ORDER BY units DESC LIMIT 5"
        );

        $salesSeries = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $salesSeries[] = [
                'date' => $day,
                'label' => date('M j', strtotime($day)),
                'total' => (float) Database::value(
                    "SELECT COALESCE(SUM(total),0) FROM orders WHERE {$paid} AND DATE(paid_at) = ?",
                    [$day],
                    0
                ),
            ];
        }
        $max = max(1, max(array_column($salesSeries, 'total')));

        return View::render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'revenueToday' => $revenueToday,
            'revenueMonth' => $revenueMonth,
            'ordersToday' => $ordersToday,
            'openOrders' => $openOrders,
            'awaitingPayment' => $awaitingPayment,
            'customers' => $customers,
            'pendingDealers' => $pendingDealers,
            'newMessages' => $newMessages,
            'pendingReviews' => $pendingReviews,
            'recentOrders' => $recentOrders,
            'lowStock' => $lowStock,
            'topProducts' => $topProducts,
            'salesSeries' => $salesSeries,
            'salesMax' => $max,
            'readiness' => CheckoutService::instance()->readiness(),
        ], 'layouts/admin');
    }
}
