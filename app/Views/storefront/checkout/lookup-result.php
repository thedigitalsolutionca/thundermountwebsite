<?php
use App\Core\Money;
use App\Services\OrderService;
$address = (new OrderService())->addressText($order['shipping_address_data']);
?>
<section class="section">
  <div class="container" style="max-width:820px">
    <div class="flex between wrap mb-3" style="align-items:flex-end">
      <div>
        <span class="eyebrow">Order lookup</span>
        <h1 style="font-size:1.9rem;margin-bottom:4px"><?= e($order['order_number']) ?></h1>
        <p class="muted" style="margin:0">Placed <?= e(fmt_datetime($order['created_at'])) ?> · <?= e(Money::format((float) $order['total'])) ?></p>
      </div>
      <div class="flex" style="gap:8px">
        <span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span>
        <span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span>
      </div>
    </div>

    <div class="card card-body">
      <?php if ($order['tracking_number']): ?>
        <div class="notice notice-info"><?= icon('truck') ?><div><strong>Shipped via <?= e($order['tracking_carrier'] ?: 'carrier') ?>.</strong> Tracking: <?= e($order['tracking_number']) ?></div></div>
      <?php endif; ?>
      <?php foreach ($order['items'] as $item): ?>
        <div class="order-line">
          <div><strong><?= e($item['name']) ?></strong><div class="small muted">SKU <?= e($item['sku']) ?> · Qty <?= (int) $item['qty'] ?></div></div>
          <strong><?= e(Money::format((float) $item['line_total'])) ?></strong>
        </div>
      <?php endforeach; ?>
      <div class="totals mt-2">
        <div class="totals-row"><span>Subtotal</span><span><?= e(Money::format((float) $order['subtotal'])) ?></span></div>
        <?php if ((float) $order['discount_total'] > 0): ?><div class="totals-row" style="color:var(--green)"><span>Discount</span><span>−<?= e(Money::format((float) $order['discount_total'])) ?></span></div><?php endif; ?>
        <div class="totals-row"><span>Shipping</span><span><?= (float) $order['shipping_total'] > 0 ? e(Money::format((float) $order['shipping_total'])) : 'Free' ?></span></div>
        <div class="totals-row"><span>Tax</span><span><?= e(Money::format((float) $order['tax_total'])) ?></span></div>
        <div class="totals-row total"><span>Total</span><span><?= e(Money::format((float) $order['total'])) ?></span></div>
      </div>
    </div>

    <div class="grid grid-2 mt-3" style="align-items:start">
      <div class="card card-body">
        <h2 style="font-size:1rem;margin-top:0">Delivery address</h2>
        <p class="small muted" style="white-space:pre-line;margin:0"><?= e($address) ?></p>
      </div>
      <div class="card card-body">
        <h2 style="font-size:1rem;margin-top:0">Need help?</h2>
        <p class="small muted" style="margin:0">Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a> and include order <?= e($order['order_number']) ?>.</p>
      </div>
    </div>
    <div class="flex wrap mt-3">
      <a class="btn btn-outline" href="<?= url('/order-lookup') ?>">Look up another order</a>
      <button class="btn btn-ghost" type="button" data-print>Print</button>
    </div>
  </div>
</section>
