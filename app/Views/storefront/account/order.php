<?php
use App\Core\Csrf;
use App\Core\Money;
use App\Services\OrderService;

$steps = ['pending' => 'Order placed', 'paid' => 'Payment received', 'processing' => 'Processing', 'shipped' => 'Shipped', 'completed' => 'Delivered'];
$currentIndex = array_search($order['status'], array_keys($steps), true);
if ($order['status'] === 'cancelled' || $order['status'] === 'refunded') {
    $currentIndex = -1;
}
$addressText = (new OrderService())->addressText($order['shipping_address_data']);
?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'My account', 'url' => '/account'], ['label' => 'Orders', 'url' => '/account/orders'], ['label' => $order['order_number']]]]) ?>
    <div class="account-layout">
      <?= \App\Core\View::partial('storefront/account/sidebar') ?>
      <div>
        <div class="section-head" style="margin-bottom:18px">
          <div>
            <span class="eyebrow">Order <?= e($order['order_number']) ?></span>
            <h1 style="font-size:1.9rem;margin:0"><?= e(status_label($order['status'])) ?></h1>
            <p class="muted" style="margin:6px 0 0">Placed <?= e(fmt_datetime($order['created_at'])) ?> · <?= e(Money::format((float) $order['total'])) ?></p>
          </div>
          <div class="flex wrap">
            <button class="btn btn-outline btn-sm" type="button" data-print><?= icon('file-text') ?> Print</button>
            <form action="<?= url('/account/orders/' . urlencode($order['order_number']) . '/reorder') ?>" method="post">
              <?= csrf_field() ?>
              <button class="btn btn-primary btn-sm" type="submit">Reorder</button>
            </form>
          </div>
        </div>

        <div class="notice <?= $order['status'] === 'cancelled' || $order['status'] === 'refunded' ? 'notice-error' : 'notice-info' ?>">
          <?= icon($order['status'] === 'cancelled' || $order['status'] === 'refunded' ? 'bell' : 'truck') ?>
          <div>
            <?php if ($order['status'] === 'cancelled'): ?>
              <strong>This order was cancelled.</strong> If you have questions, call 805-338-6680.
            <?php elseif ($order['status'] === 'refunded'): ?>
              <strong>This order was refunded.</strong> Refunds are returned to the original payment method.
            <?php elseif ($order['tracking_number']): ?>
              <strong>Shipped via <?= e($order['tracking_carrier'] ?: 'carrier') ?>.</strong> Tracking number: <?= e($order['tracking_number']) ?>.
            <?php elseif ($order['payment_status'] === 'paid'): ?>
              <strong>Payment received.</strong> We will email you when your order ships.
            <?php else: ?>
              <strong>Awaiting payment.</strong> Complete payment to release your order, or call 805-338-6680.
            <?php endif; ?>
          </div>
        </div>

        <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'refunded'): ?>
          <div class="order-timeline" aria-label="Order progress">
            <?php $i = 0; foreach ($steps as $key => $label): ?>
              <div class="step <?= $currentIndex !== false && $i <= $currentIndex ? 'done' : '' ?>">
                <span class="step-dot"><?= $currentIndex !== false && $i <= $currentIndex ? icon('check') : $i + 1 ?></span>
                <span><?= e($label) ?></span>
              </div>
            <?php $i++; endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="grid" style="grid-template-columns:1.5fr 1fr;gap:20px;align-items:start">
          <div class="card">
            <div class="card-head"><h2 style="font-size:1.05rem;margin:0">Items</h2></div>
            <div class="card-body">
              <?php foreach ($order['items'] as $item): ?>
                <div class="order-line">
                  <div>
                    <strong><?= e($item['name']) ?></strong>
                    <div class="small muted">SKU <?= e($item['sku']) ?> · Qty <?= (int) $item['qty'] ?></div>
                  </div>
                  <div style="text-align:right">
                    <strong><?= e(Money::format((float) $item['line_total'])) ?></strong>
                    <div class="tiny muted"><?= e(Money::format((float) $item['unit_price'])) ?> each<?= (int) $item['is_dealer_price'] ? ' · dealer' : '' ?></div>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="mt-3">
                <div class="totals-row"><span>Subtotal</span><span><?= e(Money::format((float) $order['subtotal'])) ?></span></div>
                <?php if ((float) $order['discount_total'] > 0): ?><div class="totals-row" style="color:var(--green)"><span>Discount <?= e($order['discount_code']) ?></span><span>−<?= e(Money::format((float) $order['discount_total'])) ?></span></div><?php endif; ?>
                <div class="totals-row"><span>Shipping <?= $order['shipping_method_name'] ? '(' . e($order['shipping_method_name']) . ')' : '' ?></span><span><?= (float) $order['shipping_total'] > 0 ? e(Money::format((float) $order['shipping_total'])) : 'Free' ?></span></div>
                <div class="totals-row"><span>Tax</span><span><?= e(Money::format((float) $order['tax_total'])) ?></span></div>
                <div class="totals-row total"><span>Total</span><span><?= e(Money::format((float) $order['total'])) ?></span></div>
              </div>
            </div>
          </div>

          <div class="stack">
            <div class="card card-body">
              <h3 style="font-size:.95rem;margin-top:0">Delivery address</h3>
              <p class="small muted" style="white-space:pre-line;margin:0"><?= e($addressText) ?></p>
            </div>
            <div class="card card-body">
              <h3 style="font-size:.95rem;margin-top:0">Payment</h3>
              <p class="small" style="margin:0"><span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span></p>
              <p class="small muted" style="margin:8px 0 0"><?= $order['payment_method'] === 'manual' ? 'Invoice / arranged payment' : 'PayPal' ?><?= $order['paid_at'] ? ' · ' . e(fmt_date($order['paid_at'])) : '' ?></p>
            </div>
            <?php if ($customerNotes !== []): ?>
              <div class="card card-body">
                <h3 style="font-size:.95rem;margin-top:0">Updates from Thunder Mounts</h3>
                <?php foreach ($customerNotes as $note): ?>
                  <p class="small" style="margin:0 0 8px"><?= nl2br(e($note['note'])) ?><br><span class="tiny muted"><?= e(fmt_datetime($note['created_at'])) ?></span></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="card card-body">
              <h3 style="font-size:.95rem;margin-top:0">Need help?</h3>
              <p class="small muted">Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a> with your order number.</p>
            </div>
          </div>
        </div>
        <?php if ($order['customer_note']): ?>
          <div class="card card-body mt-3">
            <h3 style="font-size:.95rem;margin-top:0">Your note</h3>
            <p class="small muted" style="margin:0"><?= nl2br(e($order['customer_note'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
