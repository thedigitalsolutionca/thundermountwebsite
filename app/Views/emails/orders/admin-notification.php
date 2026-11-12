<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">New order <?= e($order['order_number']) ?></h1>
<p>A new order has been placed on the website.</p>
<?= \App\Core\View::partial('emails/partials/order-summary', ['order' => $order]) ?>
<p style="margin:0 0 14px;"><strong>Payment:</strong> <?= e($order['payment_method']) ?> &middot; <?= e($order['payment_status']) ?></p>
<?php if (!empty($order['customer_note'])): ?>
  <p style="margin:0 0 14px;"><strong>Customer note:</strong><br><?= nl2br(e($order['customer_note'])) ?></p>
<?php endif; ?>
<p style="margin:0;">
  <a href="<?= e(absolute_url('/admin/orders/' . $order['order_number'])) ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">Open in the admin</a>
</p>
