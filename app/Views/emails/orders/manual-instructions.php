<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Order <?= e($order['order_number']) ?> received</h1>
<p>Hi <?= e($order['shipping_address_data']['first_name'] ?? 'there') ?>,</p>
<p>Thank you — we have your order. Your selected payment method is <strong>pay by invoice</strong>, so no card or PayPal payment was taken online.</p>
<?= \App\Core\View::partial('emails/partials/order-summary', ['order' => $order]) ?>
<?php $instructions = trim((string) setting('checkout.manual_payment_instructions', '')); ?>
<?php if ($instructions !== ''): ?>
  <div style="background:#FDF3E1;border:1px solid #F3DF8F;border-radius:6px;padding:14px 16px;margin:0 0 18px;font-size:14px;color:#5E4600;">
    <strong style="display:block;margin-bottom:4px;">Payment instructions</strong>
    <?= nl2br(e($instructions)) ?>
  </div>
<?php endif; ?>
<p>If you have a question about your order or payment, reply to this email or call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a>.</p>
