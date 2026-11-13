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
        $rangeError = '';
        try {
            $range = \App\Core\DateRange::resolve((string) \App\Core\Request::query('period', 'month'), (string) \App\Core\Request::query('from', ''), (string) \App\Core\Request::query('to', ''));
        } catch (\InvalidArgumentException $e) {
            $rangeError = $e->getMessage();
            $range = \App\Core\DateRange::resolve('month');
        }
        $paid = "payment_status IN ('paid','partially_refunded')";
        $bounds = [$range['start'], $range['end']];
        $revenue = (float) Database::value("SELECT COALESCE(SUM(total),0) FROM orders WHERE {$paid} AND paid_at >= ? AND paid_at < ?", $bounds, 0);
        $orderCount = (int) Database::value('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ?', $bounds);
        $paidCount = (int) Database::value("SELECT COUNT(*) FROM orders WHERE {$paid} AND paid_at >= ? AND paid_at < ?", $bounds);
        $openOrders = (int) Database::value("SELECT COUNT(*) FROM orders WHERE status IN ('paid','processing')");
        $awaitingPayment = (int) Database::value("SELECT COUNT(*) FROM orders WHERE status = 'pending' AND payment_status = 'unpaid'");
        $customers = (int) Database::value("SELECT COUNT(*) FROM users WHERE role IN ('customer','dealer') AND created_at >= ? AND created_at < ?", $bounds);
        $pendingDealers = (int) Database::value("SELECT COUNT(*) FROM dealers WHERE status = 'pending'");
        $newMessages = (int) Database::value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
        $pendingReviews = (int) Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");

        $recentOrders = (new OrderService())->adminList(['from' => $range['from'], 'to' => $range['to']], 8, 0);
        $lowStock = (new InventoryService())->lowStock(8);

        $topProducts = Database::select(
            "SELECT oi.product_id, oi.name, oi.sku, SUM(oi.qty) AS units, SUM(oi.line_total) AS revenue
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.{$paid} AND o.paid_at >= ? AND o.paid_at < ?
             GROUP BY oi.product_id, oi.name, oi.sku ORDER BY units DESC LIMIT 5", $bounds
        );

        $daily = Database::select("SELECT DATE(paid_at) AS day, SUM(total) AS total FROM orders WHERE {$paid} AND paid_at >= ? AND paid_at < ? GROUP BY DATE(paid_at)", $bounds);
        $totals = array_column($daily, 'total', 'day');
        $salesSeries = [];
        $step = $range['days'] > 31 ? 7 : 1;
        $date = new \DateTimeImmutable($range['from']);
        $end = new \DateTimeImmutable($range['end']);
        while ($date < $end) {
            $first = $date;
            $total = 0;
            for ($i = 0; $i < $step && $date < $end; $i++) {
                $total += (float) ($totals[$date->format('Y-m-d')] ?? 0);
                $date = $date->modify('+1 day');
            }
            $salesSeries[] = ['date' => $first->format('Y-m-d'), 'label' => $first->format('M j') . ($step > 1 ? '–' . $date->modify('-1 day')->format('M j') : ''), 'total' => $total];
        }
        $max = max(1, max(array_column($salesSeries, 'total')));

        return View::render('admin/dashboard', [
            'pageTitle' => 'Dashboard',
            'range' => $range, 'rangeError' => $rangeError,
            'revenue' => $revenue, 'orderCount' => $orderCount, 'paidCount' => $paidCount,
            'failedEmails' => (int) Database::value("SELECT COUNT(*) FROM email_log WHERE status = 'failed'"),
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
