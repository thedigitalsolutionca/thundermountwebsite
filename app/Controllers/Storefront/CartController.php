<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CheckoutService;

final class CartController
{
    public function show(): string
    {
        $summary = cart()->summary();
        return View::render('storefront/cart', [
            'pageTitle' => 'Your cart',
            'metaDescription' => 'Review your Thunder Mounts cart and continue to secure checkout.',
            'robots' => 'noindex,nofollow',
            'summary' => $summary,
            'readiness' => CheckoutService::instance()->readiness(),
        ], 'layouts/storefront');
    }

    public function add(): never
    {
        Csrf::require();
        $productId = Request::int('product_id');
        $qty = Request::int('qty', 1);
        $result = cart()->add($productId, $qty);
        if ($this->ajax()) {
            Response::json(array_merge($result, ['count' => cart()->count()]), $result['ok'] ? 200 : 422);
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        if ($result['ok']) {
            Response::redirect(url('/cart'));
        }
        Response::back(url('/shop'));
    }

    public function update(): never
    {
        Csrf::require();
        $itemId = Request::int('item_id');
        $qty = Request::int('qty', 1);
        $result = cart()->updateQty($itemId, $qty);
        if ($this->ajax()) {
            Response::json(array_merge($result, [
                'count' => cart()->count(),
                'summary' => $this->summaryPayload(),
            ]), $result['ok'] ? 200 : 422);
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/cart'));
    }

    public function remove(): never
    {
        Csrf::require();
        $result = cart()->remove(Request::int('item_id'));
        if ($this->ajax()) {
            Response::json(array_merge($result, [
                'count' => cart()->count(),
                'summary' => $this->summaryPayload(),
            ]), $result['ok'] ? 200 : 422);
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/cart'));
    }

    public function discount(): never
    {
        Csrf::require();
        $code = (string) Request::input('code', '');
        $result = cart()->applyDiscount($code);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/cart'));
    }

    public function removeDiscount(): never
    {
        Csrf::require();
        cart()->removeDiscount();
        flash('success', 'Discount code removed.');
        Response::redirect(url('/cart'));
    }

    public function drawer(): never
    {
        $summary = cart()->summary();
        Response::json([
            'html' => View::render('storefront/partials/cart-drawer-content', ['summary' => $summary]),
            'count' => $summary['count'],
            'subtotal' => money($summary['subtotal_cents']),
        ]);
    }

    private function summaryPayload(): array
    {
        $summary = cart()->summary();
        return [
            'subtotal' => money($summary['subtotal_cents']),
            'total' => money($summary['total_cents']),
            'discount' => money($summary['discount_amount_cents']),
        ];
    }

    private function ajax(): bool
    {
        return Request::wantsJson() || Request::header('X-Requested-With') !== null;
    }
}
