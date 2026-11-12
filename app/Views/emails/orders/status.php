<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Update on order <?= e($order['order_number']) ?></h1>
<p>Hi <?= e($order['shipping_address_data']['first_name'] ?? 'there') ?>,</p>
<p><?= nl2br(e($message ?? 'Your order status has changed.')) ?></p>
<p style="margin:0 0 4px;font-size:14px;"><strong>Current status:</strong> <?= e(status_label((string) $order['status'])) ?></p>
<?= \App\Core\View::partial('emails/partials/order-summary', ['order' => $order]) ?>
<p style="margin:0;">Questions? Reply to this email or call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a>.</p>
