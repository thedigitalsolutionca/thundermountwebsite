<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Money;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\DealerService;
use App\Services\OrderService;
use App\Services\UploadService;

final class DealerController
{
    public function program(): string
    {
        $dealerService = new DealerService();
        return View::render('storefront/dealer/program', [
            'pageTitle' => 'Dealer Program',
            'metaDescription' => 'Trade pricing and support for garage door companies, installers and retailers. Apply for a Thunder Mounts dealer account.',
            'tiers' => $dealerService->tiers(true),
        ], 'layouts/storefront');
    }

    public function loginForm(): string
    {
        if (Auth::check()) {
            Response::redirect(url('/dealer/account'));
        }
        return View::render('storefront/dealer/login', [
            'pageTitle' => 'Dealer sign-in',
            'robots' => 'noindex,follow',
        ], 'layouts/storefront');
    }

    public function applyForm(): string
    {
        if (!Auth::check()) {
            flash('info', 'Create a free account first, then apply for dealer pricing.');
            Response::redirect(url('/register'));
        }
        $dealer = (new DealerService())->forUser((int) Auth::id());
        if ($dealer) {
            Response::redirect(url('/dealer/account'));
        }
        return View::render('storefront/dealer/apply', [
            'pageTitle' => 'Apply for a dealer account',
            'metaDescription' => 'Apply for Thunder Mounts trade pricing.',
            'robots' => 'noindex,follow',
        ], 'layouts/storefront');
    }

    public function apply(): never
    {
        if (!Auth::check()) {
            Response::redirect(url('/register'));
        }
        Csrf::require();
        $input = Request::all();
        $documentPath = null;
        $file = Request::files('document');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = (new UploadService())->document($file, 'dealers');
            if (!$upload['ok']) {
                Session::set('_errors', ['document' => $upload['error']]);
                unset($input['_token']);
                remember_input($input);
                Response::redirect(url('/dealer/apply'));
            }
            $documentPath = $upload['path'];
        }
        unset($input['_token']);
        if (empty($input['agree'])) {
            Session::set('_errors', ['agree' => 'Please confirm the information is accurate and that you are authorised to request trade pricing.']);
            remember_input($input);
            Response::redirect(url('/dealer/apply'));
        }
        $result = (new DealerService())->apply((int) Auth::id(), $input, $documentPath);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            unset($input['_token']);
            remember_input($input);
            Response::redirect(url('/dealer/apply'));
        }
        clear_input();
        flash('success', 'Your dealer application was received. We review applications during business hours.');
        Response::redirect(url('/dealer/account'));
    }

    public function dashboard(): string
    {
        if (!Auth::check()) {
            Response::redirect(url('/dealer/login'));
        }
        $user = Auth::user();
        $dealer = (new DealerService())->forUser((int) $user['id']);
        if (!$dealer) {
            return View::render('storefront/dealer/not-applied', [
                'pageTitle' => 'Dealer portal',
                'robots' => 'noindex,nofollow',
            ], 'layouts/storefront');
        }
        $orderService = new OrderService();
        $dealerId = (int) $dealer['id'];
        $orders = $dealer['status'] === 'approved' ? $orderService->forDealer($dealerId, 5) : [];
        $totalSpent = (float) \App\Core\Database::value(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE dealer_id = ? AND payment_status IN ('paid','partially_refunded')",
            [$dealerId],
            0
        );
        return View::render('storefront/dealer/dashboard', [
            'pageTitle' => 'Dealer portal',
            'robots' => 'noindex,nofollow',
            'dealer' => $dealer,
            'orders' => $orders,
            'orderCount' => $orderService->countForDealer($dealerId),
            'totalSpent' => $totalSpent,
        ], 'layouts/storefront');
    }

    public function orders(): string
    {
        if (!Auth::check()) {
            Response::redirect(url('/dealer/login'));
        }
        $dealer = (new DealerService())->forUser((int) Auth::id());
        if (!$dealer || $dealer['status'] !== 'approved') {
            Response::redirect(url('/dealer/account'));
        }
        $orders = (new OrderService())->forDealer((int) $dealer['id'], 100);
        return View::render('storefront/dealer/orders', [
            'pageTitle' => 'Dealer orders',
            'robots' => 'noindex,nofollow',
            'dealer' => $dealer,
            'orders' => $orders,
        ], 'layouts/storefront');
    }
}
