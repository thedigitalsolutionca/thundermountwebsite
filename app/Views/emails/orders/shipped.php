<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Your order has shipped</h1>
<p>Hi <?= e($order['shipping_address_data']['first_name'] ?? 'there') ?>,</p>
<p>Order <strong><?= e($order['order_number']) ?></strong> is on its way.</p>
<?php if (!empty($order['tracking_number'])): ?>
  <div style="background:#E6EEF9;border:1px solid #C7D9F2;border-radius:6px;padding:14px 16px;margin:0 0 18px;font-size:14px;color:#143E78;">
    <strong style="display:block;margin-bottom:3px;">Tracking number</strong>
    <?= e($order['tracking_carrier'] ?: 'Carrier') ?> &middot; <?= e($order['tracking_number']) ?>
  </div>
<?php endif; ?>
<?= \App\Core\View::partial('emails/partials/order-summary', ['order' => $order]) ?>
<p style="margin:0;">Need help with installation? Call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a> or reply to this email.</p>
