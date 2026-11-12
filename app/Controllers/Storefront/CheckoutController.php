<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Money;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\CheckoutService;
use App\Services\ContentService;
use App\Services\OrderService;
use App\Services\PayPalService;
use Throwable;

final class CheckoutController
{
    public function index(): string
    {
        if (cart()->isEmpty()) {
            flash('error', 'Your cart is empty. Add a product before checking out.');
            Response::redirect(url('/cart'));
        }
        $checkout = CheckoutService::instance();
        $readiness = $checkout->readiness();
        if (!$readiness['ready']) {
            return View::render('storefront/checkout/unavailable', [
                'pageTitle' => 'Checkout',
                'robots' => 'noindex,nofollow',
                'readiness' => $readiness,
                'summary' => cart()->summary(),
                'data' => $checkout->blankCheckout(old()),
            ], 'layouts/storefront');
        }

        $data = $checkout->blankCheckout(old());
        $address = $checkout->addressFrom($data);
        $summary = cart()->summary($address, null);
        $paypal = new PayPalService();
        return View::render('storefront/checkout/index', [
            'pageTitle' => 'Secure checkout',
            'metaDescription' => 'Complete your Thunder Mounts order with PayPal or pay by invoice, subject to availability.',
            'robots' => 'noindex,nofollow',
            'data' => $data,
            'summary' => $summary,
            'readiness' => $readiness,
            'paypal' => $paypal,
            'paypalSdk' => $readiness['paypal'] ? $paypal->sdkUrl() : '',
        ], 'layouts/storefront');
    }

    public function shipping(): never
    {
        Csrf::require();
        if (cart()->isEmpty()) {
            Response::json(['ok' => false, 'message' => 'Your cart is empty.'], 422);
        }
        $checkout = CheckoutService::instance();
        $address = $checkout->addressFrom($_POST);
        $selected = Request::int('shipping_method', 0);
        $summary = cart()->summary($address, $selected);
        $options = array_map(static fn ($option) => [
            'id' => $option['id'],
            'name' => $option['name'],
            'cents' => $option['cents'],
            'price' => money($option['cents']),
            'description' => $option['description'],
        ], $summary['shipping_options']);
        Response::json([
            'ok' => $options !== [],
            'options' => $options,
            'selected' => $summary['shipping']['id'] ?? null,
            'totals' => [
                'subtotal' => money($summary['subtotal_cents']),
                'discount' => money($summary['discount_amount_cents']),
                'shipping' => money($summary['shipping_cents']),
                'tax' => money($summary['tax_cents']),
                'total' => money($summary['total_cents']),
            ],
            'tax_name' => $summary['tax_name'],
            'message' => $options === [] ? 'We could not find a shipping method for this address. Please call 805-338-6680.' : '',
        ]);
    }

    public function paypalCreate(): never
    {
        Csrf::require();
        $checkout = CheckoutService::instance();
        $methodId = Request::int('shipping_method', 0);
        $source = (string) Request::input('source', 'paypal');
        $source = in_array($source, ['paypal', 'card', 'googlepay', 'applepay'], true) ? $source : 'paypal';
        $result = $checkout->startPayPal($_POST, $methodId, $source);
        if (!$result['ok']) {
            Response::json([
                'ok' => false,
                'errors' => $result['errors'],
                'message' => reset($result['errors']) ?: 'Checkout could not be started.',
            ], 422);
        }
        Response::json([
            'ok' => true,
            'id' => $result['paypal_order_id'],
            'order_number' => $result['order']['order_number'],
        ]);
    }

    public function paypalCapture(): never
    {
        Csrf::require();
        $paypalOrderId = (string) Request::input('paypal_order_id', '');
        if ($paypalOrderId === '') {
            Response::json(['ok' => false, 'message' => 'Missing PayPal order reference.'], 422);
        }
        $result = CheckoutService::instance()->capturePayPal($paypalOrderId);
        if (!$result['ok']) {
            Response::json(['ok' => false, 'message' => $result['message']], 422);
        }
        Response::json([
            'ok' => true,
            'order_number' => $result['order']['order_number'],
            'redirect' => url('/checkout/success?order=' . urlencode($result['order']['order_number'])),
        ]);
    }

    /**
     * PayPal redirect return (used by wallet flows that leave the page).
     */
    public function paypalReturn(): never
    {
        $number = (string) Request::query('order', '');
        $paypalOrderId = (string) Request::query('token', '');
        $order = (new OrderService())->findByNumber($number);
        if (!$order) {
            flash('error', 'We could not find that order.');
            Response::redirect(url('/cart'));
        }
        if ($order['payment_status'] === 'paid') {
            Response::redirect(url('/checkout/success?order=' . urlencode($number)));
        }
        if ($paypalOrderId !== '') {
            $result = CheckoutService::instance()->capturePayPal($paypalOrderId);
            if ($result['ok']) {
                Response::redirect(url('/checkout/success?order=' . urlencode($number)));
            }
            flash('error', $result['message']);
        }
        Response::redirect(url('/checkout/cancel?order=' . urlencode($number)));
    }

    public function manual(): never
    {
        Csrf::require();
        $result = CheckoutService::instance()->createManualOrder($_POST, Request::int('shipping_method', 0));
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($_POST);
            Response::redirect(url('/checkout'));
        }
        clear_input();
        Response::redirect(url('/checkout/success?order=' . urlencode($result['order']['order_number'])));
    }

    /**
     * When rates or tax rules are not configured, collect an invoice enquiry
     * instead of creating an order with a fictitious zero shipping/tax total.
     */
    public function invoiceRequest(): never
    {
        Csrf::require();
        $readiness = CheckoutService::instance()->readiness();
        if (!$readiness['manual'] || $readiness['ready']) {
            flash('error', 'Invoice requests are not available. Please use the normal checkout or call us.');
            Response::redirect(url('/checkout'));
        }
        if (cart()->isEmpty()) {
            flash('error', 'Add a product before requesting an invoice.');
            Response::redirect(url('/cart'));
        }
        $input = [];
        foreach (['first_name', 'last_name', 'email', 'phone', 'address1', 'city', 'state', 'postcode', 'country', 'customer_note'] as $field) {
            $value = Request::post($field, '');
            $input[$field] = is_string($value) ? trim($value) : '';
        }
        $validator = (new Validator($input, [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'email' => 'Email address',
            'phone' => 'Phone number',
            'address1' => 'Street address',
            'city' => 'City',
            'state' => 'State or province',
            'postcode' => 'ZIP or postal code',
            'country' => 'Country',
        ]))->validate([
            'first_name' => 'required|max:80',
            'last_name' => 'required|max:80',
            'email' => 'required|email|max:190',
            'phone' => 'required|phone|max:30',
            'address1' => 'required|max:190',
            'city' => 'required|max:100',
            'state' => 'required|max:60',
            'postcode' => 'required|postcode',
            'country' => 'required|in:US,CA',
            'customer_note' => 'max:1000',
        ]);
        $errors = $validator->errors();
        if (mb_strlen(trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? ''))) > 120) {
            $errors['last_name'] = 'Please shorten the name to 120 characters or fewer.';
        }
        $summary = cart()->summary();
        if ($summary['has_unavailable']) {
            $errors['cart'] = 'Remove unavailable items from the cart before requesting an invoice.';
        }
        if ($summary['discount_error']) {
            $errors['discount'] = $summary['discount_error'] . ' Remove the code before requesting an invoice.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/checkout'));
        }

        $lines = [];
        foreach ($summary['items'] as $item) {
            $lines[] = $item['product']['sku'] . ' — ' . $item['product']['name'] . ' × ' . $item['qty'] . ' — ' . money($item['line_cents']);
        }
        $message = "Invoice enquiry — no order placed and no payment taken. Shipping and tax to be confirmed.\n\n"
            . "Cart:\n" . implode("\n", $lines) . "\n"
            . 'Product subtotal: ' . money($summary['subtotal_cents']) . "\n"
            . ($summary['discount'] ? 'Discount code: ' . $summary['discount_code'] . ' (estimated saving ' . money($summary['discount_amount_cents']) . ")\n" : '')
            . 'Estimated product total: ' . money($summary['subtotal_cents'] - $summary['discount_amount_cents']) . "\n\n"
            . 'Ship to: ' . $input['address1'] . ', ' . $input['city'] . ', '
            . strtoupper($input['state']) . ' ' . strtoupper($input['postcode']) . ', '
            . strtoupper($input['country']) . "\n"
            . 'Customer note: ' . ($input['customer_note'] ?: 'None.');
        $result = (new ContentService())->contact([
            'name' => trim($input['first_name'] . ' ' . $input['last_name']),
            'email' => $input['email'],
            'phone' => $input['phone'],
            'subject' => 'Invoice request from checkout',
            'message' => $message,
        ]);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($input);
            Response::redirect(url('/checkout'));
        }
        clear_input();
        flash('success', 'Your invoice request is saved. No order or payment was placed; we will confirm shipping, tax and the invoice with you.');
        Response::redirect(url('/cart'));
    }

    public function success(): string
    {
        $number = (string) Request::query('order', Session::get('last_order_number', ''));
        $order = $number !== '' ? (new OrderService())->findByNumber($number) : null;

        $owns = $order && (
            (Auth::check() && (int) $order['user_id'] === (int) Auth::id())
            || Session::get('last_order_number') === $order['order_number']
            || Session::get('pending_order_number') === $order['order_number']
        );
        if (!$order || !$owns) {
            flash('error', 'Sign in or use order lookup to view that order.');
            Response::redirect(url('/order-lookup'));
        }
        Session::set('last_order_number', $order['order_number']);
        return View::render('storefront/checkout/success', [
            'pageTitle' => 'Order ' . $order['order_number'] . ' confirmed',
            'robots' => 'noindex,nofollow',
            'order' => $order,
        ], 'layouts/storefront');
    }

    public function cancel(): string
    {
        $number = (string) Request::query('order', Session::get('pending_order_number', ''));
        $order = $number !== '' ? (new OrderService())->findByNumber($number) : null;
        return View::render('storefront/checkout/cancel', [
            'pageTitle' => 'Checkout cancelled',
            'robots' => 'noindex,nofollow',
            'order' => $order,
        ], 'layouts/storefront');
    }

    public function lookupForm(): string
    {
        return View::render('storefront/checkout/lookup', [
            'pageTitle' => 'Find your order',
            'metaDescription' => 'Look up a Thunder Mounts order with your order number and email.',
            'robots' => 'noindex,nofollow',
        ], 'layouts/storefront');
    }

    public function lookup(): string
    {
        Csrf::require();
        $number = (string) Request::input('order_number', '');
        $email = (string) Request::input('email', '');
        $order = (new OrderService())->findForGuest($number, $email);
        if (!$order) {
            Session::set('_errors', ['lookup' => 'No order matched that number and email. Check both and try again.']);
            remember_input(['order_number' => $number, 'email' => $email]);
            return $this->lookupForm();
        }
        clear_input();
        return View::render('storefront/checkout/lookup-result', [
            'pageTitle' => 'Order ' . $order['order_number'],
            'robots' => 'noindex,nofollow',
            'order' => $order,
        ], 'layouts/storefront');
    }
}
