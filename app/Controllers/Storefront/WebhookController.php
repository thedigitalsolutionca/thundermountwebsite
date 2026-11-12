<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Response;
use App\Services\OrderService;
use App\Services\PayPalService;
use Throwable;

final class WebhookController
{
    public function paypal(): never
    {
        $raw = file_get_contents('php://input') ?: '';
        $event = json_decode($raw, true);
        if (!is_array($event) || empty($event['event_type'])) {
            Response::json(['error' => 'Invalid payload'], 400);
        }

        $paypal = new PayPalService();
        if (!$paypal->webhookConfigured()) {
            Logger::error('PayPal webhook received but no webhook ID is configured');
            Response::json(['error' => 'Webhook not configured'], 503);
        }

        $headers = [
            'transmission_id' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '',
            'transmission_time' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '',
            'transmission_sig' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '',
            'cert_url' => $_SERVER['HTTP_PAYPAL_CERT_URL'] ?? '',
            'auth_algo' => $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '',
            'auth_version' => $_SERVER['HTTP_PAYPAL_AUTH_VERSION'] ?? '',
        ];
        if (!$paypal->verifyWebhook($headers, $event)) {
            Logger::error('PayPal webhook signature verification failed', ['event' => $event['id'] ?? '']);
            Response::json(['error' => 'Invalid signature'], 400);
        }

        $eventId = (string) ($event['id'] ?? '');
        if ($eventId !== '') {
            $existing = Database::first('SELECT id FROM paypal_webhook_events WHERE event_id = ?', [$eventId]);
            if ($existing) {
                Response::json(['ok' => true, 'duplicate' => true]);
            }
            Database::insert('paypal_webhook_events', [
                'event_id' => $eventId,
                'event_type' => (string) $event['event_type'],
                'resource_type' => (string) ($event['resource_type'] ?? ''),
                'payload' => $raw,
                'status' => 'received',
                'created_at' => now(),
            ]);
        }

        $type = (string) $event['event_type'];
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];

        try {
            if ($type === 'PAYMENT.CAPTURE.COMPLETED') {
                $orderNumber = (string) ($resource['custom_id'] ?? $resource['invoice_id'] ?? '');
                if ($orderNumber !== '') {
                    (new OrderService())->finalizePayPalCapture($orderNumber, array_merge($resource, [
                        'payer' => $event['resource']['payer'] ?? [],
                    ]));
                }
            } elseif (in_array($type, ['PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED'], true)) {
                $orderNumber = (string) ($resource['custom_id'] ?? $resource['invoice_id'] ?? '');
                if ($orderNumber !== '') {
                    $order = (new OrderService())->findByNumber($orderNumber);
                    if ($order) {
                        (new OrderService())->transition((int) $order['id'], 'refunded', 'PayPal reported a refund (' . $type . ').', null);
                    }
                }
            } elseif ($type === 'PAYMENT.CAPTURE.DENIED') {
                Logger::error('PayPal capture denied', ['resource' => $resource]);
            }
            if ($eventId !== '') {
                Database::execute("UPDATE paypal_webhook_events SET status = 'processed', processed_at = ? WHERE event_id = ?", [now(), $eventId]);
            }
        } catch (Throwable $e) {
            Logger::error('PayPal webhook processing failed', ['event' => $eventId, 'error' => $e->getMessage()]);
            if ($eventId !== '') {
                Database::execute("UPDATE paypal_webhook_events SET status = 'failed', error = ? WHERE event_id = ?", [mb_substr($e->getMessage(), 0, 500), $eventId]);
            }
            Response::json(['error' => 'Processing failed'], 500);
        }

        Response::json(['ok' => true]);
    }
}
