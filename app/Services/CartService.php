<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Money;
use App\Core\Session;
use App\Core\Str;

final class CartService
{
    private static ?self $instance = null;
    private ?array $cart = null;
    private ?array $itemsCache = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function __construct()
    {
        $this->resolveCart();
    }

    public function id(): int
    {
        return (int) ($this->cart['id'] ?? 0);
    }

    public function token(): string
    {
        return (string) ($this->cart['token'] ?? '');
    }

    private function resolveCart(): void
    {
        $userId = Auth::id();
        $token = $this->cookieToken();

        if ($userId > 0) {
            $cart = Database::first('SELECT * FROM carts WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$userId]);
            if ($cart) {
                $this->cart = $cart;
                if ($token !== '' && $token !== $cart['token']) {
                    $this->mergeGuestCart($token, $cart);
                }
                $this->setCookie($cart['token']);
                return;
            }
        }

        if ($token !== '') {
            $cart = Database::first('SELECT * FROM carts WHERE token = ? LIMIT 1', [$token]);
            if ($cart) {
                if ($userId > 0 && empty($cart['user_id'])) {
                    Database::execute('UPDATE carts SET user_id = ?, updated_at = ? WHERE id = ?', [$userId, now(), $cart['id']]);
                    $cart['user_id'] = $userId;
                }
                $this->cart = $cart;
                $this->setCookie($cart['token']);
                return;
            }
        }

        $newToken = $token !== '' ? $token : Str::random(32);
        $id = Database::insert('carts', [
            'token' => $newToken,
            'user_id' => $userId > 0 ? $userId : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cart = [
            'id' => $id,
            'token' => $newToken,
            'user_id' => $userId > 0 ? $userId : null,
        ];
        $this->setCookie($newToken);
    }

    private function cookieToken(): string
    {
        $token = (string) ($_COOKIE['tm_cart'] ?? '');
        if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $token;
        }
        $sessionToken = (string) Session::get('cart_token', '');
        return preg_match('/^[a-f0-9]{64}$/', $sessionToken) ? $sessionToken : '';
    }

    private function setCookie(string $token): void
    {
        Session::set('cart_token', $token);
        if (!headers_sent()) {
            setcookie('tm_cart', $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    private function mergeGuestCart(string $guestToken, array $userCart): void
    {
        $guest = Database::first('SELECT * FROM carts WHERE token = ? LIMIT 1', [$guestToken]);
        if (!$guest || (int) $guest['id'] === (int) $userCart['id']) {
            return;
        }
        $guestItems = Database::select('SELECT * FROM cart_items WHERE cart_id = ?', [$guest['id']]);
        foreach ($guestItems as $item) {
            $existing = Database::first(
                'SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?',
                [$userCart['id'], $item['product_id']]
            );
            if ($existing) {
                Database::execute(
                    'UPDATE cart_items SET qty = ? WHERE id = ?',
                    [min(99, (int) $existing['qty'] + (int) $item['qty']), $existing['id']]
                );
            } else {
                Database::execute(
                    'UPDATE cart_items SET cart_id = ? WHERE id = ?',
                    [$userCart['id'], $item['id']]
                );
            }
        }
        Database::execute('DELETE FROM cart_items WHERE cart_id = ?', [$guest['id']]);
        Database::execute('DELETE FROM carts WHERE id = ?', [$guest['id']]);
        $this->itemsCache = null;
    }

    /**
     * Attach a guest cart after login/registration.
     */
    public static function mergeForUser(int $userId): void
    {
        $token = (string) ($_COOKIE['tm_cart'] ?? Session::get('cart_token', ''));
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            self::$instance = null;
            return;
        }
        $guest = Database::first('SELECT * FROM carts WHERE token = ? LIMIT 1', [$token]);
        $userCart = Database::first('SELECT * FROM carts WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$userId]);
        if ($guest && (int) $guest['id'] !== (int) ($userCart['id'] ?? 0)) {
            if (!$userCart) {
                Database::execute('UPDATE carts SET user_id = ?, updated_at = ? WHERE id = ?', [$userId, now(), $guest['id']]);
            } else {
                foreach (Database::select('SELECT * FROM cart_items WHERE cart_id = ?', [$guest['id']]) as $item) {
                    $existing = Database::first(
                        'SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?',
                        [$userCart['id'], $item['product_id']]
                    );
                    if ($existing) {
                        Database::execute('UPDATE cart_items SET qty = ? WHERE id = ?', [
                            min(99, (int) $existing['qty'] + (int) $item['qty']),
                            $existing['id'],
                        ]);
                    } else {
                        Database::execute('UPDATE cart_items SET cart_id = ? WHERE id = ?', [$userCart['id'], $item['id']]);
                    }
                }
                Database::execute('DELETE FROM cart_items WHERE cart_id = ?', [$guest['id']]);
                Database::execute('DELETE FROM carts WHERE id = ?', [$guest['id']]);
            }
        }
        self::$instance = null;
    }

    public function add(int $productId, int $qty = 1): array
    {
        $qty = max(1, min(99, $qty));
        $catalog = CatalogService::instance();
        $product = $catalog->findAny($productId);
        if (!$product) {
            return ['ok' => false, 'message' => 'That product is no longer available.'];
        }
        if (!$catalog->canPurchase($product)) {
            return ['ok' => false, 'message' => 'This product is not available to add to the cart right now.'];
        }

        $existing = Database::first(
            'SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?',
            [$this->id(), $productId]
        );
        $newQty = min(99, ($existing ? (int) $existing['qty'] : 0) + $qty);

        if (!$catalog->stockAvailable($product, $newQty)) {
            $available = max(0, (int) $product['stock_qty']);
            return [
                'ok' => false,
                'message' => $available > 0
                    ? 'Only ' . $available . ' of this item are available.'
                    : 'This item is currently out of stock.',
            ];
        }

        if ($existing) {
            Database::execute('UPDATE cart_items SET qty = ? WHERE id = ?', [$newQty, $existing['id']]);
        } else {
            Database::insert('cart_items', [
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'qty' => $newQty,
                'created_at' => now(),
            ]);
        }
        $this->touch();
        $this->itemsCache = null;
        return [
            'ok' => true,
            'message' => $product['name'] . ' was added to your cart.',
            'count' => $this->count(),
            'qty' => $newQty,
        ];
    }

    public function updateQty(int $itemId, int $qty): array
    {
        $item = $this->rawItem($itemId);
        if (!$item) {
            return ['ok' => false, 'message' => 'That cart item was not found.'];
        }
        if ($qty <= 0) {
            Database::execute('DELETE FROM cart_items WHERE id = ? AND cart_id = ?', [$itemId, $this->id()]);
            $this->itemsCache = null;
            $this->touch();
            return ['ok' => true, 'message' => 'Item removed.'];
        }
        $qty = min(99, $qty);
        $product = CatalogService::instance()->findAny((int) $item['product_id']);
        if ($product && !CatalogService::instance()->stockAvailable($product, $qty)) {
            return ['ok' => false, 'message' => 'Only ' . max(0, (int) $product['stock_qty']) . ' available.'];
        }
        Database::execute('UPDATE cart_items SET qty = ? WHERE id = ? AND cart_id = ?', [$qty, $itemId, $this->id()]);
        $this->itemsCache = null;
        $this->touch();
        return ['ok' => true, 'message' => 'Cart updated.', 'qty' => $qty];
    }

    public function remove(int $itemId): array
    {
        $deleted = Database::execute('DELETE FROM cart_items WHERE id = ? AND cart_id = ?', [$itemId, $this->id()]);
        $this->itemsCache = null;
        $this->touch();
        return ['ok' => $deleted > 0, 'message' => $deleted > 0 ? 'Item removed from your cart.' : 'Item not found.'];
    }

    public function clear(): void
    {
        Database::execute('DELETE FROM cart_items WHERE cart_id = ?', [$this->id()]);
        Session::forget('cart_discount_code');
        Session::forget('cart_shipping_method');
        $this->itemsCache = null;
        $this->touch();
    }

    private function touch(): void
    {
        Database::execute('UPDATE carts SET updated_at = ? WHERE id = ?', [now(), $this->id()]);
    }

    private function rawItem(int $itemId): ?array
    {
        return Database::first('SELECT * FROM cart_items WHERE id = ? AND cart_id = ?', [$itemId, $this->id()]);
    }

    /**
     * @return array<int,array{item:array,product:array,qty:int,line_cents:int,available:bool,image:string}>
     */
    public function items(): array
    {
        if ($this->itemsCache !== null) {
            return $this->itemsCache;
        }
        $rows = Database::select(
            'SELECT ci.id AS item_id, ci.qty, p.*
             FROM cart_items ci INNER JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ? ORDER BY ci.id',
            [$this->id()]
        );
        $catalog = CatalogService::instance();
        $items = [];
        foreach ($rows as $row) {
            $qty = (int) $row['qty'];
            $product = $catalog->decorate($row);
            $available = (int) $row['is_active'] === 1 && $catalog->canPurchase($product);
            $line = $product['price_info']['unit_cents'] * $qty;
            $items[] = [
                'item_id' => (int) $row['item_id'],
                'qty' => $qty,
                'product' => $product,
                'line_cents' => $available ? $line : 0,
                'available' => $available,
                'image' => $catalog->primaryImage($product),
            ];
        }
        return $this->itemsCache = $items;
    }

    public function count(): int
    {
        return (int) Database::value('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE cart_id = ?', [$this->id()]);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function applyDiscount(string $code): array
    {
        $subtotal = $this->subtotalCents();
        $result = (new DiscountService())->validate($code, $subtotal, Auth::user()['email'] ?? null, Auth::id() ?: null);
        if (!$result['ok']) {
            return ['ok' => false, 'message' => $result['error']];
        }
        Session::set('cart_discount_code', $result['discount']['code']);
        return [
            'ok' => true,
            'message' => 'Discount ' . $result['discount']['code'] . ' applied.',
            'amount' => Money::formatCents($result['amount_cents']),
        ];
    }

    public function removeDiscount(): void
    {
        Session::forget('cart_discount_code');
    }

    public function discountCode(): string
    {
        return (string) Session::get('cart_discount_code', '');
    }

    public function setShippingMethod(int $methodId): void
    {
        Session::set('cart_shipping_method', $methodId);
    }

    public function subtotalCents(): int
    {
        $sum = 0;
        foreach ($this->items() as $item) {
            $sum += $item['line_cents'];
        }
        return $sum;
    }

    public function taxableSubtotalCents(): int
    {
        $sum = 0;
        foreach ($this->items() as $item) {
            if ((int) ($item['product']['is_taxable'] ?? 1) === 1 && $item['available']) {
                $sum += $item['line_cents'];
            }
        }
        return $sum;
    }

    public function weightLb(): float
    {
        $weight = 0.0;
        foreach ($this->items() as $item) {
            $weight += (float) ($item['product']['weight'] ?? 0) * $item['qty'];
        }
        return round($weight, 2);
    }

    /**
     * @return array<string,mixed>
     */
    public function summary(?array $address = null, ?int $shippingMethodId = null): array
    {
        $items = $this->items();
        $subtotal = $this->subtotalCents();
        $discountAmount = 0;
        $discount = null;
        $discountError = null;
        $code = $this->discountCode();
        if ($code !== '') {
            $result = (new DiscountService())->validate($code, $subtotal, Auth::user()['email'] ?? null, Auth::id() ?: null);
            if ($result['ok']) {
                $discount = $result['discount'];
                $discountAmount = $result['amount_cents'];
            } else {
                $discountError = $result['error'];
            }
        }

        $shippingOptions = [];
        $shipping = null;
        if ($address !== null && $subtotal > 0) {
            $shippingOptions = (new ShippingService())->options($address, $subtotal, $this->weightLb());
            $selectedId = $shippingMethodId ?? (int) Session::get('cart_shipping_method', 0);
            foreach ($shippingOptions as $option) {
                if ($selectedId > 0 && $option['id'] === $selectedId) {
                    $shipping = $option;
                    break;
                }
            }
            if ($shipping === null && $shippingOptions !== []) {
                $shipping = $shippingOptions[0];
            }
        }

        $tax = ['cents' => 0, 'rate' => 0.0, 'name' => '', 'state' => ''];
        if ($address !== null && $subtotal > 0) {
            $taxable = $this->taxableSubtotalCents();
            if ($subtotal > 0) {
                $taxable -= (int) round($discountAmount * ($this->taxableSubtotalCents() / max(1, $subtotal)));
            }
            $tax = (new TaxService())->calculate(max(0, $taxable), $shipping['cents'] ?? 0, $address);
        }

        $shippingCents = $shipping['cents'] ?? 0;
        $total = max(0, $subtotal - $discountAmount + $shippingCents + $tax['cents']);

        return [
            'items' => $items,
            'count' => $this->count(),
            'subtotal_cents' => $subtotal,
            'discount_code' => $code,
            'discount' => $discount,
            'discount_amount_cents' => $discountAmount,
            'discount_error' => $discountError,
            'shipping_options' => $shippingOptions,
            'shipping' => $shipping,
            'shipping_cents' => $shippingCents,
            'tax_cents' => (int) $tax['cents'],
            'tax_rate' => (float) $tax['rate'],
            'tax_name' => (string) $tax['name'],
            'total_cents' => $total,
            'weight_lb' => $this->weightLb(),
            'has_unavailable' => (bool) array_filter($items, static fn ($i) => !$i['available']),
        ];
    }
}
