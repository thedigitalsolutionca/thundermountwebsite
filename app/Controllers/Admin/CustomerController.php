<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\DealerService;
use App\Services\OrderService;

final class CustomerController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $q = trim((string) Request::query('q', ''));
        $role = trim((string) Request::query('role', ''));
        $where = ["u.role IN ('customer','dealer','admin')"];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.company LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (in_array($role, ['customer', 'dealer', 'admin'], true)) {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }
        $whereSql = implode(' AND ', $where);
        $perPage = 25;
        $total = (int) Database::value("SELECT COUNT(*) FROM users u WHERE {$whereSql}", $params);
        $paginator = new Paginator($total, $perPage, max(1, Request::int('page', 1)));
        $customers = Database::select(
            "SELECT u.*,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.payment_status IN ('paid','partially_refunded')) AS total_spent
             FROM users u WHERE {$whereSql} ORDER BY u.created_at DESC
             LIMIT {$paginator->perPage} OFFSET {$paginator->offset()}",
            $params
        );
        return View::render('admin/customers/index', [
            'pageTitle' => 'Customers',
            'customers' => $customers,
            'paginator' => $paginator,
            'q' => $q,
            'role' => $role,
        ], 'layouts/admin');
    }

    public function show(string $id): string
    {
        $customer = Database::first('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$customer) {
            flash('error', 'Customer not found.');
            Response::redirect(url('/admin/customers'));
        }
        $orders = Database::select('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [$customer['id']]);
        $addresses = Database::select('SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC', [$customer['id']]);
        $dealer = (new DealerService())->forUser((int) $customer['id']);
        $spent = (float) Database::value(
            "SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = ? AND payment_status IN ('paid','partially_refunded')",
            [$customer['id']],
            0
        );
        return View::render('admin/customers/show', [
            'pageTitle' => $customer['email'],
            'customer' => $customer,
            'orders' => $orders,
            'addresses' => $addresses,
            'dealer' => $dealer,
            'spent' => $spent,
        ], 'layouts/admin');
    }

    public function toggle(string $id): never
    {
        Csrf::require();
        $customer = Database::first('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$customer) {
            flash('error', 'Customer not found.');
        } elseif ((int) $customer['id'] === Auth::id()) {
            flash('error', 'You cannot disable your own account.');
        } else {
            $next = $customer['status'] === 'active' ? 'disabled' : 'active';
            Database::execute('UPDATE users SET status = ?, updated_at = ? WHERE id = ?', [$next, now(), $customer['id']]);
            flash('success', 'Customer account ' . ($next === 'active' ? 'enabled' : 'disabled') . '.');
        }
        Response::redirect(url('/admin/customers/' . (int) $id));
    }

    public function dealers(): string
    {
        $status = trim((string) Request::query('status', ''));
        $where = ['1 = 1'];
        $params = [];
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where[] = 'd.status = ?';
            $params[] = $status;
        }
        $dealers = Database::select(
            'SELECT d.*, t.name AS tier_name, t.discount_percent
             FROM dealers d LEFT JOIN dealer_tiers t ON t.id = d.tier_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY CASE d.status WHEN "pending" THEN 0 WHEN "approved" THEN 1 ELSE 2 END, d.applied_at DESC',
            $params
        );
        return View::render('admin/dealers/index', [
            'pageTitle' => 'Dealers',
            'dealers' => $dealers,
            'status' => $status,
            'counts' => (new DealerService())->counts(),
            'tiers' => (new DealerService())->tiers(),
        ], 'layouts/admin');
    }

    public function dealer(string $id): string
    {
        $dealer = (new DealerService())->find((int) $id);
        if (!$dealer) {
            flash('error', 'Dealer not found.');
            Response::redirect(url('/admin/dealers'));
        }
        return View::render('admin/dealers/show', [
            'pageTitle' => $dealer['company'],
            'dealer' => $dealer,
            'tiers' => (new DealerService())->tiers(true),
        ], 'layouts/admin');
    }

    public function approveDealer(string $id): never
    {
        Csrf::require();
        $result = (new DealerService())->assignTier((int) $id, Request::int('tier_id') ?: null, Auth::id(), trim((string) Request::input('note', '')));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/dealers/' . (int) $id));
    }

    public function rejectDealer(string $id): never
    {
        Csrf::require();
        $note = trim((string) Request::input('note', ''));
        $result = (new DealerService())->reject((int) $id, Auth::id(), $note);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/dealers/' . (int) $id));
    }

    public function tiers(): string
    {
        $editId = Request::int('edit', 0);
        return View::render('admin/dealers/tiers', [
            'pageTitle' => 'Dealer tiers',
            'tiers' => (new DealerService())->tiers(),
            'editTier' => $editId > 0 ? Database::first('SELECT * FROM dealer_tiers WHERE id = ?', [$editId]) : null,
        ], 'layouts/admin');
    }

    public function saveTier(?string $id = null): never
    {
        Csrf::require();
        $result = (new DealerService())->saveTier(Request::all(), $id !== null ? (int) $id : null);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input(Request::all());
            Response::redirect(url('/admin/dealers/tiers' . ($id !== null ? '?edit=' . (int) $id : '')));
        }
        flash('success', 'Dealer tier saved.');
        Response::redirect(url('/admin/dealers/tiers'));
    }

    public function deleteTier(string $id): never
    {
        Csrf::require();
        $result = (new DealerService())->deleteTier((int) $id);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/dealers/tiers'));
    }
}
