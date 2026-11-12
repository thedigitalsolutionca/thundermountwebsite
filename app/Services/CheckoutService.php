<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Money;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Str;
use App\Core\Validator;
use Throwable;

final class CheckoutService
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function paypal(): PayPalService
    {
        return new PayPalService();
    }

    public function paymentOptions(): array
    {
        return [
            'paypal' => $this->paypal()->isConfigured() && Settings::bool('paypal.enabled', false),
            'manual' => Settings::bool('checkout.manual_payment_enabled', false)
                && trim((string) Settings::get('checkout.manual_payment_instructions', '')) !== '',
        ];
    }

    /**
     * @return array{ready:bool,payment:bool,shipping:bool,tax:bool,issues:array,paypal:bool,manual:bool}
     */
    public function readiness(): array
    {
        $payments = $this->paymentOptions();
        $shipping = (new ShippingService())->configured();
        $tax = (new TaxService())->configured();
        $issues = [];
        if (!$payments['paypal'] && !$payments['manual']) {
            $issues[] = 'No payment method is enabled. Configure PayPal in Admin → Settings → PayPal, or enable pay-by-invoice.';
        }
        if (!$shipping) {
            $issues[] = 'No active shipping method. Add at least one method in Admin → Shipping.';
        }
        if (!$tax) {
            $issues[] = 'Sales tax is enabled but no active tax rate exists. Add a rate in Admin → Tax, or disable tax collection.';
        }
        return [
            'ready' => $issues === [],
            'payment' => $payments['paypal'] || $payments['manual'],
            'shipping' => $shipping,
            'tax' => $tax,
            'paypal' => $payments['paypal'],
            'manual' => $payments['manual'],
            'issues' => $issues,
        ];
    }

    public function blankCheckout(array $prefill = []): array
    {
        $user = Auth::user();
        $defaults = [
            'email' => $user['email'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'company' => $user['company'] ?? '',
            'phone' => $user['phone'] ?? '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'postcode' => '',
            'country' => 'US',
            'billing_same' => '1',
            'customer_note' => '',
            'create_account' => '0',
            'password' => '',
            'accept_terms' => '0',
            'payment_method' => '',
            'shipping_method' => '',
        ];
        if ($user) {
            $address = Database::first(
                'SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC, id DESC LIMIT 1',
                [$user['id']]
            );
            if ($address) {
                foreach (['first_name', 'last_name', 'company', 'phone', 'address1', 'address2', 'city', 'state', 'postcode', 'country'] as $field) {
                    $defaults[$field] = $address[$field] ?? $defaults[$field];
                }
            }
        }
        return array_merge($defaults, $prefill);
    }

    public function validate(array $input): array
    {
        $errors = [];
        $validator = new Validator($input, [
            'email' => 'Email address',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'address1' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'postcode' => 'ZIP / postal code',
            'country' => 'Country',
            'phone' => 'Phone number',
        ]);
        $validator->validate([
            'email' => 'required|email|max:190',
            'first_name' => 'required|max:80',
            'last_name' => 'required|max:80',
            'address1' => 'required|max:190',
            'city' => 'required|max:100',
            'state' => 'required|max:60',
            'postcode' => 'required|postcode',
            'country' => 'required|in:US,CA',
            'phone' => 'required|phone|max:30',
            'customer_note' => 'max:1000',
        ]);
        $errors = $validator->errors();

        if (empty($input['accept_terms'])) {
            $errors['accept_terms'] = 'Please accept the terms to place your order.';
        }

        $cart = cart();
        if ($cart->isEmpty()) {
            $errors['cart'] = 'Your cart is empty.';
        }
        $summary = $cart->summary($this->addressFrom($input), 0);
        foreach ($summary['items'] as $item) {
            if (!$item['available']) {
                $errors['cart'] = $item['product']['name'] . ' is no longer available. Please remove it from your cart.';
                break;
            }
            if (!CatalogService::instance()->stockAvailable($item['product'], $item['qty'])) {
                $errors['cart'] = 'There is not enough stock for ' . $item['product']['name'] . '. Please update your cart.';
                break;
            }
        }
        if ($summary['discount_error']) {
            $errors['discount'] = $summary['discount_error'] . ' Remove the code to continue.';
        }

        $shippingOptions = $summary['shipping_options'];
        if ($shippingOptions === []) {
            $errors['shipping_method'] = 'No shipping method is available for this address. Please call 805-338-6680 so we can help.';
        }
        $methodId = (int) ($input['shipping_method'] ?? 0);
        $validMethod = false;
        foreach ($shippingOptions as $option) {
            if ($option['id'] === $methodId) {
                $validMethod = true;
                break;
            }
        }
        if (!$validMethod) {
            $errors['shipping_method'] = 'Please choose a shipping method.';
        }

        $readiness = $this->readiness();
        if (!$readiness['payment']) {
            $errors['payment'] = 'Online payment is temporarily unavailable. Please call 805-338-6680 to place your order.';
        }
        if (!$readiness['shipping']) {
            $errors['shipping_method'] = 'Shipping is not configured yet. Please call 805-338-6680 to place your order.';
        }
        if (!$readiness['tax']) {
            $errors['tax'] = 'Sales tax is not configured yet. Please call 805-338-6680 to place your order.';
        }

        if (!Auth::check() && !empty($input['create_account'])) {
            $password = (string) ($input['password'] ?? '');
            if (mb_strlen($password) < 8) {
                $errors['password'] = 'Choose a password of at least 8 characters.';
            }
            $existing = Database::value('SELECT COUNT(*) FROM users WHERE email = ?', [mb_strtolower(trim((string) $input['email']))]);
            if ((int) $existing > 0) {
                $errors['email'] = 'An account already exists for this email. Sign in or continue as a guest.';
            }
        }

        if (!empty($input['billing_same']) === false) {
            $billing = new Validator($input, [
                'billing_first_name' => 'Billing first name',
                'billing_last_name' => 'Billing last name',
                'billing_address1' => 'Billing address',
                'billing_city' => 'Billing city',
                'billing_state' => 'Billing state',
                'billing_postcode' => 'Billing postal code',
                'billing_country' => 'Billing country',
            ]);
            $billing->validate([
                'billing_first_name' => 'required|max:80',
                'billing_last_name' => 'required|max:80',
                'billing_address1' => 'required|max:190',
                'billing_city' => 'required|max:100',
                'billing_state' => 'required|max:60',
                'billing_postcode' => 'required|postcode',
                'billing_country' => 'required|in:US,CA',
            ]);
            $errors = array_merge($errors, $billing->errors());
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'summary' => $summary,
            'shipping_method_id' => $validMethod ? $methodId : 0,
        ];
    }

    public function addressFrom(array $input): array
    {
        return [
            'first_name' => trim((string) ($input['first_name'] ?? '')),
            'last_name' => trim((string) ($input['last_name'] ?? '')),
            'company' => trim((string) ($input['company'] ?? '')),
            'address1' => trim((string) ($input['address1'] ?? '')),
            'address2' => trim((string) ($input['address2'] ?? '')),
            'city' => trim((string) ($input['city'] ?? '')),
            'state' => strtoupper(trim((string) ($input['state'] ?? ''))),
            'postcode' => strtoupper(trim((string) ($input['postcode'] ?? ''))),
            'country' => strtoupper(trim((string) ($input['country'] ?? 'US'))),
            'phone' => trim((string) ($input['phone'] ?? '')),
        ];
    }

    public function billingFrom(array $input): array
    {
        if (!empty($input['billing_same'])) {
            return $this->addressFrom($input);
        }
        return [
            'first_name' => trim((string) ($input['billing_first_name'] ?? '')),
            'last_name' => trim((string) ($input['billing_last_name'] ?? '')),
            'company' => trim((string) ($input['billing_company'] ?? '')),
            'address1' => trim((string) ($input['billing_address1'] ?? '')),
            'address2' => trim((string) ($input['billing_address2'] ?? '')),
            'city' => trim((string) ($input['billing_city'] ?? '')),
            'state' => strtoupper(trim((string) ($input['billing_state'] ?? ''))),
            'postcode' => strtoupper(trim((string) ($input['billing_postcode'] ?? ''))),
            'country' => strtoupper(trim((string) ($input['billing_country'] ?? 'US'))),
        ];
    }

    /**
     * Create the local pending order (no payment yet).
     */
    public function createOrder(array $input, int $shippingMethodId, string $paymentMethod): array
    {
        $validation = $this->validate($input);
        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors'], 'order' => null];
        }
        $cart = cart();
        $summary = $cart->summary($this->addressFrom($input), $shippingMethodId);
        if ($summary['shipping'] === null) {
            return ['ok' => false, 'errors' => ['shipping_method' => 'Please choose a shipping method.'], 'order' => null];
        }

        $userId = Auth::id() ?: null;
        if (Auth::check() && !empty($input['create_account'])) {
            $userId = Auth::id();
        }

        $dealer = null;
        if ($userId && Auth::isDealer()) {
            $dealer = Database::first('SELECT id FROM dealers WHERE user_id = ? LIMIT 1', [$userId]);
        }

        $createdAccount = false;
        if (!Auth::check() && !empty($input['create_account'])) {
            $account = (new AccountService())->register([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'company' => $input['company'] ?? '',
                'password' => $input['password'],
                'password_confirmation' => $input['password'],
            ], true);
            if (!$account['ok']) {
                return ['ok' => false, 'errors' => $account['errors'], 'order' => null];
            }
            $userId = (int) $account['user']['id'];
            $createdAccount = true;
        }

        $shipping = $this->addressFrom($input);
        $billing = $this->billingFrom($input);
        $orderNumber = Str::orderNumber();
        $notes = trim((string) ($input['customer_note'] ?? ''));

        try {
            $orderId = Database::transaction(function () use ($orderNumber, $userId, $dealer, $input, $summary, $shipping, $billing, $notes, $paymentMethod): int {
                $id = Database::insert('orders', [
                    'order_number' => $orderNumber,
                    'user_id' => $userId,
                    'dealer_id' => $dealer['id'] ?? null,
                    'email' => mb_strtolower(trim((string) $input['email'])),
                    'phone' => substr(trim((string) ($input['phone'] ?? '')), 0, 30),
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $paymentMethod,
                    'currency' => 'USD',
                    'subtotal' => Money::fromCents($summary['subtotal_cents']),
                    'discount_total' => Money::fromCents($summary['discount_amount_cents']),
                    'shipping_total' => Money::fromCents($summary['shipping_cents']),
                    'tax_total' => Money::fromCents($summary['tax_cents']),
                    'total' => Money::fromCents($summary['total_cents']),
                    'discount_code' => $summary['discount_code'],
                    'shipping_method_name' => $summary['shipping']['name'] ?? '',
                    'shipping_address' => json_encode($shipping, JSON_UNESCAPED_UNICODE),
                    'billing_address' => json_encode($billing, JSON_UNESCAPED_UNICODE),
                    'customer_note' => $notes !== '' ? $notes : null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($summary['items'] as $item) {
                    Database::insert('order_items', [
                        'order_id' => $id,
                        'product_id' => $item['product']['id'],
                        'name' => $item['product']['name'],
                        'sku' => $item['product']['sku'],
                        'qty' => $item['qty'],
                        'unit_price' => Money::fromCents($item['product']['price_info']['unit_cents']),
                        'line_total' => Money::fromCents($item['line_cents']),
                        'is_dealer_price' => $item['product']['price_info']['is_dealer'] ? 1 : 0,
                    ]);
                }

                Database::insert('order_status_history', [
                    'order_id' => $id,
                    'from_status' => '',
                    'to_status' => 'pending',
                    'note' => 'Checkout started (' . $paymentMethod . ')',
                    'created_at' => now(),
                ]);

                if ($userId) {
                    $this->saveAddress($userId, $shipping, $billing);
                }
                return $id;
            });
        } catch (Throwable $e) {
            Logger::error('Order creation failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'errors' => ['general' => 'We could not create your order. Please try again or call 805-338-6680.'], 'order' => null];
        }

        $order = (new OrderService())->findByNumber($orderNumber);
        if ($order === null) {
            return ['ok' => false, 'errors' => ['general' => 'Order could not be loaded.'], 'order' => null];
        }

        Session::set('pending_order_number', $orderNumber);
        Session::set('checkout_created_account', $createdAccount);
        return ['ok' => true, 'errors' => [], 'order' => $order, 'summary' => $summary];
    }

    private function saveAddress(?int $userId, array $shipping, array $billing): void
    {
        if (!$userId) {
            return;
        }
        $hasShipping = Database::value('SELECT COUNT(*) FROM addresses WHERE user_id = ? AND is_default_shipping = 1', [$userId]);
        Database::insert('addresses', [
            'user_id' => $userId,
            'label' => 'Order address',
            'first_name' => $shipping['first_name'],
            'last_name' => $shipping['last_name'],
            'company' => $shipping['company'],
            'address1' => $shipping['address1'],
            'address2' => $shipping['address2'],
            'city' => $shipping['city'],
            'state' => $shipping['state'],
            'postcode' => $shipping['postcode'],
            'country' => $shipping['country'],
            'phone' => $shipping['phone'],
            'is_default_billing' => 0,
            'is_default_shipping' => $hasShipping ? 0 : 1,
            'created_at' => now(),
        ]);
    }

    /**
     * Begin PayPal checkout: local pending order + PayPal order.
     */
    public function startPayPal(array $input, int $shippingMethodId, string $source = 'paypal'): array
    {
        if (!$this->readiness()['paypal']) {
            return ['ok' => false, 'errors' => ['payment' => 'PayPal is not configured yet. Please call 805-338-6680.'], 'paypal_order_id' => null];
        }
        $previous = (string) Session::get('pending_order_number', '');
        if ($previous !== '') {
            $old = Database::first("SELECT * FROM orders WHERE order_number = ? AND payment_status = 'unpaid' AND status = 'pending'", [$previous]);
            if ($old) {
                Database::execute("UPDATE orders SET status = 'cancelled', cancelled_at = ?, updated_at = ? WHERE id = ?", [now(), now(), $old['id']]);
            }
        }

        $result = $this->createOrder($input, $shippingMethodId, 'paypal');
        if (!$result['ok']) {
            return ['ok' => false, 'errors' => $result['errors'], 'paypal_order_id' => null];
        }
        $order = $result['order'];
        try {
            $paypalOrder = $this->paypal()->createOrder($order, $source);
        } catch (Throwable $e) {
            Logger::error('PayPal create order failed', ['order' => $order['order_number'], 'error' => $e->getMessage()]);
            Database::execute("UPDATE orders SET status = 'cancelled', cancelled_at = ?, updated_at = ? WHERE id = ?", [now(), now(), $order['id']]);
            return [
                'ok' => false,
                'errors' => ['payment' => 'We could not start the PayPal checkout. Please try again, or call 805-338-6680.'],
                'paypal_order_id' => null,
            ];
        }
        Database::execute('UPDATE orders SET paypal_order_id = ?, updated_at = ? WHERE id = ?', [
            $paypalOrder['id'] ?? null,
            now(),
            $order['id'],
        ]);
        return [
            'ok' => true,
            'errors' => [],
            'order' => $order,
            'paypal_order_id' => $paypalOrder['id'] ?? null,
        ];
    }

    /**
     * Manual / invoice order (only when the merchant enables it).
     */
    public function createManualOrder(array $input, int $shippingMethodId): array
    {
        if (!$this->readiness()['manual']) {
            return ['ok' => false, 'errors' => ['payment' => 'Pay by invoice is not available.'], 'order' => null];
        }
        $result = $this->createOrder($input, $shippingMethodId, 'manual');
        if (!$result['ok']) {
            return $result;
        }
        $order = $result['order'];
        (new OrderService())->decrementStockForOrder((int) $order['id'], 'manual_order');
        cart()->clear();
        Session::forget('pending_order_number');
        Session::set('last_order_number', $order['order_number']);
        try {
            Mailer::send(
                (string) $order['email'],
                'Order ' . $order['order_number'] . ' received — payment instructions',
                Mailer::template('orders/manual-instructions', ['order' => $order])
            );
            $adminEmail = (string) Settings::get('store.notification_email', 'thunder@thundermounts.com');
            Mailer::send(
                $adminEmail,
                'Invoice order ' . $order['order_number'] . ' — ' . Money::format((float) $order['total']),
                Mailer::template('orders/admin-notification', ['order' => $order])
            );
        } catch (Throwable $e) {
            Logger::error('Manual order email failed', ['error' => $e->getMessage()]);
        }
        return ['ok' => true, 'errors' => [], 'order' => $order];
    }

    public function capturePayPal(string $paypalOrderId): array
    {
        $order = (new OrderService())->findByPayPalOrder($paypalOrderId);
        if (!$order) {
            return ['ok' => false, 'message' => 'Order not found for this payment.', 'order' => null];
        }
        if ($order['payment_status'] === 'paid') {
            return ['ok' => true, 'message' => 'Payment already recorded.', 'order' => $order];
        }
        try {
            $capture = $this->paypal()->captureOrder($paypalOrderId);
        } catch (Throwable $e) {
            Logger::error('PayPal capture failed', ['paypal_order' => $paypalOrderId, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'The payment could not be completed. Please try again.', 'order' => null];
        }
        if (($capture['status'] ?? '') !== 'COMPLETED') {
            return ['ok' => false, 'message' => 'PayPal did not complete the payment. Please try again.', 'order' => null];
        }
        $entry = $capture['purchase_units'][0]['payments']['captures'][0] ?? $capture;
        return (new OrderService())->finalizePayPalCapture($order['order_number'], array_merge($entry, [
            'payer' => $capture['payer'] ?? [],
        ]));
    }
}
