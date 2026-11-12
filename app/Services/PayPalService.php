<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use App\Core\Money;
use App\Core\Settings;
use RuntimeException;

/**
 * PayPal Orders v2 REST client (no SDK / Composer required).
 * Works on shared hosting with cURL or PHP streams.
 */
final class PayPalService
{
    private string $mode;
    private string $clientId;
    private string $clientSecret;
    private string $webhookId;
    private string $currency;
    private string $baseUrl;

    public function __construct()
    {
        $this->mode = strtolower((string) (Settings::get('paypal.mode') ?: Config::get('paypal.mode', 'sandbox')));
        $this->clientId = trim((string) (Settings::get('encrypted.paypal.client_id') ?: Config::get('paypal.client_id', '')));
        $this->clientSecret = trim((string) (Settings::get('encrypted.paypal.client_secret') ?: Config::get('paypal.client_secret', '')));
        $this->webhookId = trim((string) (Settings::get('encrypted.paypal.webhook_id') ?: Config::get('paypal.webhook_id', '')));
        $this->currency = strtoupper((string) Settings::get('store.currency', 'USD'));
        $this->baseUrl = $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function webhookConfigured(): bool
    {
        return $this->webhookId !== '';
    }

    public function sdkUrl(): string
    {
        return 'https://www.paypal.com/sdk/js?client-id=' . rawurlencode($this->clientId)
            . '&currency=' . rawurlencode($this->currency)
            . '&intent=capture'
            . '&components=buttons,card-fields,applepay,googlepay,messages'
            . '&enable-funding=venmo,paylater'
            . '&disable-funding=credit'
            . '&locale=en_US';
    }

    public function createOrder(array $order, string $source = 'paypal'): array
    {
        $items = [];
        $itemTotal = 0;
        foreach ($order['items'] as $item) {
            $unit = Money::toCents($item['unit_price']);
            $qty = (int) $item['qty'];
            $itemTotal += $unit * $qty;
            $items[] = [
                'name' => mb_substr((string) $item['name'], 0, 127),
                'sku' => mb_substr((string) $item['sku'], 0, 127),
                'quantity' => (string) $qty,
                'unit_amount' => ['currency_code' => $this->currency, 'value' => Money::fromCents($unit)],
                'category' => 'PHYSICAL_GOODS',
            ];
        }
        $discount = Money::toCents($order['discount_total']);
        $shipping = Money::toCents($order['shipping_total']);
        $tax = Money::toCents($order['tax_total']);
        $total = Money::toCents($order['total']);

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $order['order_number'],
                'description' => 'Thunder Mounts order ' . $order['order_number'],
                'custom_id' => (string) $order['order_number'],
                'invoice_id' => (string) $order['order_number'],
                'amount' => [
                    'currency_code' => $this->currency,
                    'value' => Money::fromCents($total),
                    'breakdown' => [
                        'item_total' => ['currency_code' => $this->currency, 'value' => Money::fromCents($itemTotal)],
                        'shipping' => ['currency_code' => $this->currency, 'value' => Money::fromCents($shipping)],
                        'tax_total' => ['currency_code' => $this->currency, 'value' => Money::fromCents($tax)],
                    ],
                ],
                'items' => $items,
            ]],
            'application_context' => [
                'brand_name' => mb_substr((string) Settings::get('paypal.brand_name', 'Thunder Mounts'), 0, 127),
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => absolute_url('/checkout/paypal/return?order=' . urlencode((string) $order['order_number'])),
                'cancel_url' => absolute_url('/checkout/cancel?order=' . urlencode((string) $order['order_number'])),
            ],
        ];
        if ($discount > 0) {
            $payload['purchase_units'][0]['amount']['breakdown']['discount'] = [
                'currency_code' => $this->currency,
                'value' => Money::fromCents($discount),
            ];
        }

        $verification = ['verification' => ['method' => 'SCA_WHEN_REQUIRED']];
        if ($source === 'googlepay') {
            $payload['payment_source'] = ['google_pay' => ['attributes' => $verification]];
        } elseif ($source === 'applepay') {
            $payload['payment_source'] = ['apple_pay' => ['attributes' => $verification]];
        } elseif ($source === 'card') {
            $payload['payment_source'] = ['card' => ['attributes' => $verification]];
        }

        return $this->request('POST', '/v2/checkout/orders', $payload, [
            'PayPal-Request-Id' => 'create-' . $order['order_number'],
        ]);
    }

    public function captureOrder(string $paypalOrderId): array
    {
        return $this->request(
            'POST',
            '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture',
            new \stdClass(),
            ['PayPal-Request-Id' => 'capture-' . $paypalOrderId],
            45
        );
    }

    public function getOrder(string $paypalOrderId): array
    {
        return $this->request('GET', '/v2/checkout/orders/' . rawurlencode($paypalOrderId));
    }

    public function refund(string $captureId, ?int $amountCents = null, string $note = ''): array
    {
        $payload = [];
        if ($amountCents !== null) {
            $payload['amount'] = [
                'value' => Money::fromCents($amountCents),
                'currency_code' => $this->currency,
            ];
        }
        if ($note !== '') {
            $payload['note_to_payer'] = mb_substr($note, 0, 255);
        }
        return $this->request(
            'POST',
            '/v2/payments/captures/' . rawurlencode($captureId) . '/refund',
            $payload === [] ? new \stdClass() : $payload,
            ['PayPal-Request-Id' => 'refund-' . $captureId . '-' . ($amountCents ?? 'full')]
        );
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => 'Enter a client ID and secret first.'];
        }
        try {
            $this->accessToken();
            return ['ok' => true, 'message' => 'Connected to PayPal (' . $this->mode() . ').'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'PayPal rejected the credentials: ' . $e->getMessage()];
        }
    }

    public function verifyWebhook(array $headers, array $event): bool
    {
        if (!$this->isConfigured() || $this->webhookId === '') {
            return false;
        }
        $required = ['paypal-transmission-id', 'paypal-transmission-time', 'paypal-transmission-sig', 'paypal-cert-url', 'paypal-auth-algo'];
        foreach ($required as $header) {
            if (empty($headers[$header])) {
                return false;
            }
        }
        $payload = [
            'auth_algo' => $headers['paypal-auth-algo'],
            'cert_url' => $headers['paypal-cert-url'],
            'transmission_id' => $headers['paypal-transmission-id'],
            'transmission_sig' => $headers['paypal-transmission-sig'],
            'transmission_time' => $headers['paypal-transmission-time'],
            'webhook_id' => $this->webhookId,
            'webhook_event' => $event,
        ];
        try {
            $result = $this->request('POST', '/v1/notifications/verify-webhook-signature', $payload);
        } catch (\Throwable $e) {
            Logger::error('PayPal webhook verification failed', ['error' => $e->getMessage()]);
            return false;
        }
        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }

    private function request(string $method, string $path, mixed $payload = null, array $extraHeaders = [], int $timeout = 25): array
    {
        $token = $this->accessToken();
        $headers = array_merge([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Accept-Language: en_US',
            'User-Agent: ThunderMounts/1.0',
        ], array_map(
            static fn ($key, $value) => $key . ': ' . $value,
            array_keys($extraHeaders),
            array_values($extraHeaders)
        ));

        $body = null;
        if ($payload !== null) {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                throw new RuntimeException('Could not encode the PayPal request.');
            }
        }
        $response = $this->http($method, $this->baseUrl . $path, $headers, $body, $timeout);
        $decoded = json_decode($response['body'], true);
        if ($response['status'] >= 400) {
            $message = $decoded['message'] ?? $decoded['error_description'] ?? 'PayPal request failed.';
            $details = $decoded['details'][0]['description'] ?? '';
            Logger::error('PayPal API error', [
                'status' => $response['status'],
                'path' => $path,
                'message' => $message,
                'details' => $details,
            ]);
            throw new RuntimeException($message . ($details ? ' — ' . $details : ''));
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('Unexpected response from PayPal.');
        }
        return $decoded;
    }

    private function accessToken(): string
    {
        $cacheKey = 'paypal-token-' . substr(hash('sha256', $this->mode . '|' . $this->clientId . '|' . $this->clientSecret), 0, 20) . '.json';
        $cacheFile = (string) Config::get('storage.cache', dirname(__DIR__, 2) . '/storage/cache') . '/' . $cacheKey;
        if (is_file($cacheFile)) {
            $cached = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['token']) && (int) ($cached['expires'] ?? 0) > time() + 60) {
                return (string) $cached['token'];
            }
        }

        $basic = base64_encode($this->clientId . ':' . $this->clientSecret);
        $response = $this->http(
            'POST',
            $this->baseUrl . '/v1/oauth2/token',
            [
                'Authorization: Basic ' . $basic,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: ThunderMounts/1.0',
            ],
            'grant_type=client_credentials',
            25
        );
        $decoded = json_decode($response['body'], true);
        if ($response['status'] >= 400 || empty($decoded['access_token'])) {
            Logger::error('PayPal authentication failed', ['status' => $response['status']]);
            throw new RuntimeException('Could not authenticate with PayPal. Check the API credentials and mode.');
        }
        $token = (string) $decoded['access_token'];
        @file_put_contents($cacheFile, json_encode([
            'token' => $token,
            'expires' => time() + max(60, (int) ($decoded['expires_in'] ?? 3000)),
        ]), LOCK_EX);
        return $token;
    }

    /**
     * @return array{status:int,body:string}
     */
    private function http(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $result = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($result === false) {
                Logger::error('PayPal cURL error', ['error' => $error]);
                throw new RuntimeException('Could not reach PayPal. Please try again.');
            }
            return ['status' => $status, 'body' => (string) $result];
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
        if ($result === false) {
            Logger::error('PayPal stream error', ['url' => $url]);
            throw new RuntimeException('Could not reach PayPal. Please try again.');
        }
        return ['status' => $status, 'body' => $result];
    }
}
