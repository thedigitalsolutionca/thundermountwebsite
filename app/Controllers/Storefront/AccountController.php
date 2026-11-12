<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AccountService;
use App\Services\CatalogService;
use App\Services\DealerService;
use App\Services\OrderService;

final class AccountController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function dashboard(): string
    {
        $user = Auth::user();
        $orderService = new OrderService();
        $orders = $orderService->forUser((int) $user['id'], 5);
        $addresses = (new AccountService())->addresses((int) $user['id']);
        $dealer = (new DealerService())->forUser((int) $user['id']);
        $totalSpent = Database::value(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = ? AND payment_status IN ('paid','partially_refunded')",
            [$user['id']],
            0
        );
        return View::render('storefront/account/dashboard', [
            'pageTitle' => 'My account',
            'robots' => 'noindex,nofollow',
            'orders' => $orders,
            'addresses' => $addresses,
            'dealer' => $dealer,
            'totalSpent' => (float) $totalSpent,
            'orderCount' => $orderService->countForUser((int) $user['id']),
        ], 'layouts/storefront');
    }

    public function orders(): string
    {
        $user = Auth::user();
        $page = max(1, Request::int('page', 1));
        $perPage = 10;
        $service = new OrderService();
        $total = $service->countForUser((int) $user['id']);
        $paginator = new Paginator($total, $perPage, $page);
        return View::render('storefront/account/orders', [
            'pageTitle' => 'Your orders',
            'robots' => 'noindex,nofollow',
            'orders' => $service->forUser((int) $user['id'], $perPage, $paginator->offset()),
            'paginator' => $paginator,
        ], 'layouts/storefront');
    }

    public function order(string $number): string
    {
        $user = Auth::user();
        $order = (new OrderService())->findByNumber($number);
        if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
            Session::flash('error', 'That order could not be found on your account.');
            Response::redirect(url('/account/orders'));
        }
        $customerNotes = array_values(array_filter($order['notes'], static fn ($n) => (int) $n['is_customer_visible'] === 1));
        return View::render('storefront/account/order', [
            'pageTitle' => 'Order ' . $order['order_number'],
            'robots' => 'noindex,nofollow',
            'order' => $order,
            'customerNotes' => $customerNotes,
        ], 'layouts/storefront');
    }

    public function reorder(string $number): never
    {
        Csrf::require();
        $user = Auth::user();
        $order = (new OrderService())->findByNumber($number);
        if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
            Session::flash('error', 'That order could not be found.');
            Response::redirect(url('/account/orders'));
        }
        $added = 0;
        foreach ($order['items'] as $item) {
            if ($item['product_id']) {
                $result = cart()->add((int) $item['product_id'], (int) $item['qty']);
                if ($result['ok']) {
                    $added++;
                }
            }
        }
        if ($added === 0) {
            flash('error', 'None of the items in that order are currently available to reorder.');
            Response::redirect(url('/account/orders/' . urlencode($number)));
        }
        flash('success', 'The available items were added to your cart.');
        Response::redirect(url('/cart'));
    }

    public function addresses(): string
    {
        $user = Auth::user();
        $service = new AccountService();
        $editId = Request::int('edit', 0);
        $editAddress = $editId > 0 ? $service->address((int) $user['id'], $editId) : null;
        return View::render('storefront/account/addresses', [
            'pageTitle' => 'Saved addresses',
            'robots' => 'noindex,nofollow',
            'addresses' => $service->addresses((int) $user['id']),
            'editAddress' => $editAddress,
        ], 'layouts/storefront');
    }

    public function saveAddress(): never
    {
        Csrf::require();
        $user = Auth::user();
        $id = Request::int('id', 0);
        $input = [
            'label' => Request::input('label', ''),
            'first_name' => Request::input('first_name', ''),
            'last_name' => Request::input('last_name', ''),
            'company' => Request::input('company', ''),
            'address1' => Request::input('address1', ''),
            'address2' => Request::input('address2', ''),
            'city' => Request::input('city', ''),
            'state' => Request::input('state', ''),
            'postcode' => Request::input('postcode', ''),
            'country' => Request::input('country', 'US'),
            'phone' => Request::input('phone', ''),
        ];
        $result = (new AccountService())->saveAddress((int) $user['id'], $input, $id > 0 ? $id : null);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($input);
            Response::redirect(url('/account/addresses' . ($id > 0 ? '?edit=' . $id : '')));
        }
        clear_input();
        flash('success', $id > 0 ? 'Address updated.' : 'Address saved.');
        Response::redirect(url('/account/addresses'));
    }

    public function deleteAddress(int $id): never
    {
        Csrf::require();
        $deleted = (new AccountService())->deleteAddress((int) Auth::id(), $id);
        flash($deleted ? 'success' : 'error', $deleted ? 'Address removed.' : 'Address not found.');
        Response::redirect(url('/account/addresses'));
    }

    public function defaultAddress(int $id): never
    {
        Csrf::require();
        $type = Request::input('type', 'shipping') === 'billing' ? 'billing' : 'shipping';
        (new AccountService())->setDefaultAddress((int) Auth::id(), $id, $type);
        flash('success', 'Default ' . $type . ' address updated.');
        Response::redirect(url('/account/addresses'));
    }

    public function profile(): string
    {
        return View::render('storefront/account/profile', [
            'pageTitle' => 'Account settings',
            'robots' => 'noindex,nofollow',
        ], 'layouts/storefront');
    }

    public function updateProfile(): never
    {
        Csrf::require();
        $user = Auth::user();
        $input = [
            'first_name' => Request::input('first_name', ''),
            'last_name' => Request::input('last_name', ''),
            'email' => Request::input('email', ''),
            'phone' => Request::input('phone', ''),
            'company' => Request::input('company', ''),
        ];
        $result = (new AccountService())->updateProfile((int) $user['id'], $input);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($input);
            Response::redirect(url('/account/profile'));
        }
        clear_input();
        flash('success', 'Your details were updated.');
        Response::redirect(url('/account/profile'));
    }

    public function updatePassword(): never
    {
        Csrf::require();
        $result = (new AccountService())->changePassword(
            (int) Auth::id(),
            (string) Request::post('current_password', ''),
            (string) Request::post('password', ''),
            (string) Request::post('password_confirmation', '')
        );
        if (!$result['ok']) {
            Session::set('_errors', ['password_form' => reset($result['errors']) ?: 'Check your password fields.']);
            Response::redirect(url('/account/profile#password'));
        }
        flash('success', 'Your password was updated.');
        Response::redirect(url('/account/profile'));
    }
}
