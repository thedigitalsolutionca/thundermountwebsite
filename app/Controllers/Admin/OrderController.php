<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\OrderService;
use App\Services\PayPalService;
use Throwable;

final class OrderController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $filters = [
            'status' => trim((string) Request::query('status', '')),
            'payment_status' => trim((string) Request::query('payment_status', '')),
            'q' => trim((string) Request::query('q', '')),
            'from' => trim((string) Request::query('from', '')),
            'to' => trim((string) Request::query('to', '')),
        ];
        $service = new OrderService();
        $perPage = 25;
        $total = $service->adminCount($filters);
        $paginator = new Paginator($total, $perPage, max(1, Request::int('page', 1)));
        return View::render('admin/orders/index', [
            'pageTitle' => 'Orders',
            'orders' => $service->adminList($filters, $perPage, $paginator->offset()),
            'paginator' => $paginator,
            'filters' => $filters,
            'counts' => $service->statusCounts(),
        ], 'layouts/admin');
    }

    public function show(string $number): string
    {
        $service = new OrderService();
        $order = $service->findByNumber($number);
        if (!$order) {
            flash('error', 'Order not found.');
            Response::redirect(url('/admin/orders'));
        }
        $allowed = [];
        foreach (OrderService::STATUSES as $status) {
            if ($service->canTransition((string) $order['status'], $status)) {
                $allowed[] = $status;
            }
        }
        return View::render('admin/orders/show', [
            'pageTitle' => 'Order ' . $order['order_number'],
            'order' => $order,
            'allowed' => $allowed,
            'history' => \App\Core\Database::select('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC, id ASC', [$order['id']]),
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): never
    {
        Csrf::require();
        $status = (string) Request::input('status', '');
        $note = trim((string) Request::input('note', ''));
        $restock = Request::bool('restock');
        $result = (new OrderService())->transition((int) $id, $status, Auth::id(), $note, $restock);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/orders/' . urlencode((string) Request::input('order_number', ''))));
    }

    public function tracking(string $id): never
    {
        Csrf::require();
        $number = (string) Request::input('order_number', '');
        $service = new OrderService();
        $service->setTracking((int) $id, trim((string) Request::input('carrier', '')), trim((string) Request::input('tracking_number', '')));
        $order = $service->findByNumber($number);
        if ($order && $order['status'] === 'processing') {
            $service->transition((int) $id, 'shipped', Auth::id(), 'Tracking added by admin.');
        }
        flash('success', 'Tracking details saved.');
        Response::redirect(url('/admin/orders/' . urlencode($number)));
    }

    public function note(string $id): never
    {
        Csrf::require();
        $note = trim((string) Request::input('note', ''));
        if ($note !== '') {
            (new OrderService())->addNote((int) $id, Auth::id(), $note, Request::bool('customer_visible'));
            flash('success', 'Note added.');
        }
        Response::redirect(url('/admin/orders/' . urlencode((string) Request::input('order_number', ''))));
    }

    public function markPaid(string $id): never
    {
        Csrf::require();
        $result = (new OrderService())->markPaidManually((int) $id, Auth::id(), trim((string) Request::input('note', 'Marked paid by admin.')));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/orders/' . urlencode((string) Request::input('order_number', ''))));
    }

    public function refund(string $id): never
    {
        Csrf::require();
        $number = (string) Request::input('order_number', '');
        $service = new OrderService();
        $order = $service->findByNumber($number);
        if (!$order) {
            flash('error', 'Order not found.');
            Response::redirect(url('/admin/orders'));
        }
        if ($order['payment_method'] !== 'paypal' || !$order['paypal_capture_id']) {
            flash('error', 'This order has no PayPal capture to refund. Refund it in PayPal, then record the refund here.');
            Response::redirect(url('/admin/orders/' . urlencode($number)));
        }
        try {
            $paypal = new PayPalService();
            $result = $paypal->refund((string) $order['paypal_capture_id'], null, 'Refund for ' . $order['order_number']);
            if (!in_array($result['status'] ?? '', ['COMPLETED', 'PENDING'], true)) {
                flash('error', 'PayPal did not accept the refund. Check the PayPal dashboard.');
                Response::redirect(url('/admin/orders/' . urlencode($number)));
            }
        } catch (Throwable $e) {
            Logger::error('Admin refund failed', ['order' => $number, 'error' => $e->getMessage()]);
            flash('error', 'The refund could not be sent to PayPal: ' . $e->getMessage());
            Response::redirect(url('/admin/orders/' . urlencode($number)));
        }
        $transition = $service->transition((int) $order['id'], 'refunded', Auth::id(), 'Refunded via PayPal by admin.', Request::bool('restock'));
        \App\Core\Database::execute("UPDATE orders SET payment_status = 'refunded', refunded_total = total, updated_at = ? WHERE id = ?", [now(), $order['id']]);
        flash($transition['ok'] ? 'success' : 'error', $transition['ok'] ? 'Order refunded through PayPal.' : $transition['message']);
        Response::redirect(url('/admin/orders/' . urlencode($number)));
    }

    public function invoice(string $number): never
    {
        $order = (new OrderService())->findByNumber($number);
        if (!$order) {
            Response::html('Order not found', 404);
        }
        Response::html((new OrderService())->renderInvoice($order, Request::bool('packing')));
    }

    public function export(): never
    {
        $filters = [
            'status' => trim((string) Request::query('status', '')),
            'payment_status' => trim((string) Request::query('payment_status', '')),
            'q' => trim((string) Request::query('q', '')),
            'from' => trim((string) Request::query('from', '')),
            'to' => trim((string) Request::query('to', '')),
        ];
        $orders = (new OrderService())->adminList($filters, 5000, 0);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Order', 'Date', 'Email', 'Status', 'Payment status', 'Subtotal', 'Discount', 'Shipping', 'Tax', 'Total', 'Tracking']);
        foreach ($orders as $order) {
            fputcsv($handle, [
                $order['order_number'],
                $order['created_at'],
                $order['email'],
                $order['status'],
                $order['payment_status'],
                $order['subtotal'],
                $order['discount_total'],
                $order['shipping_total'],
                $order['tax_total'],
                $order['total'],
                $order['tracking_number'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
}
