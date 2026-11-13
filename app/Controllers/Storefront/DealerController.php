<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\DealerService;
use App\Services\OrderService;
use App\Services\UploadService;

final class DealerController
{
    private function abortClosed(): never
    {
        Response::html(View::render('errors/404', ['pageTitle' => 'Page not found'], 'layouts/storefront'), 404);
    }

    public function program(): string
    {
        if (!DealerService::arePagesEnabled()) {
            $this->abortClosed();
        }
        $dealerService = new DealerService();
        return View::render('storefront/dealer/program', [
            'pageTitle' => 'Dealer Program',
            'metaDescription' => 'Trade pricing and support for garage door companies, installers and retailers. Apply for a Thunder Mounts dealer account.',
            'tiers' => $dealerService->tiers(true),
        ], 'layouts/storefront');
    }

    public function loginForm(): string
    {
        if (!DealerService::isProgramEnabled()) {
            if (Auth::check()) {
                Response::redirect(url('/account'));
            }
            $this->abortClosed();
        }
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
        if (!DealerService::isProgramEnabled()) {
            $this->abortClosed();
        }
        if (!DealerService::areApplicationsEnabled()) {
            flash('info', 'New dealer applications are currently closed. Please contact us if you have questions.');
            Response::redirect(url(DealerService::arePagesEnabled() ? '/dealer-program' : '/contact'));
        }
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
        if (!DealerService::isProgramEnabled()) {
            $this->abortClosed();
        }
        if (!DealerService::areApplicationsEnabled()) {
            flash('info', 'New dealer applications are currently closed.');
            Response::redirect(url(DealerService::arePagesEnabled() ? '/dealer-program' : '/contact'));
        }
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
        $status = $result['dealer']['status'] ?? 'pending';
        if ($status === 'approved') {
            flash('success', 'Your dealer account has been approved! Your trade pricing is now active.');
        } else {
            flash('success', 'Your dealer application was received. We review applications during business hours.');
        }
        Response::redirect(url('/dealer/account'));
    }

    public function dashboard(): string
    {
        if (!DealerService::isProgramEnabled()) {
            if (Auth::check()) {
                flash('info', 'The dealer portal is currently closed. You can manage your orders from your account.');
                Response::redirect(url('/account'));
            }
            $this->abortClosed();
        }
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
        $orders = in_array($dealer['status'], ['approved', 'inactive'], true) ? $orderService->forDealer($dealerId, 5) : [];
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
        if (!DealerService::isProgramEnabled()) {
            if (Auth::check()) {
                Response::redirect(url('/account/orders'));
            }
            $this->abortClosed();
        }
        if (!Auth::check()) {
            Response::redirect(url('/dealer/login'));
        }
        $dealer = (new DealerService())->forUser((int) Auth::id());
        if (!$dealer || !in_array($dealer['status'], ['approved', 'inactive'], true)) {
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
