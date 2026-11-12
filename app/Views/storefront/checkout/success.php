<?php
use App\Core\Money;
use App\Core\Session;
use App\Services\OrderService;
$address = (new OrderService())->addressText($order['shipping_address_data']);
$createdAccount = (bool) Session::get('checkout_created_account', false);
?>
<section class="section">
  <div class="container" style="max-width:900px">
    <div class="card">
      <div class="card-body" style="padding:34px">
        <div class="success-mark" aria-hidden="true"><?= icon('check') ?></div>
        <span class="eyebrow"><?= $order['payment_status'] === 'paid' ? 'Order confirmed' : 'Order received' ?></span>
        <h1 style="font-size:2.1rem;margin-bottom:6px">Thank you — your order is in.</h1>
        <p class="muted" style="font-size:1.05rem">Order <strong style="color:var(--ink)"><?= e($order['order_number']) ?></strong>. <?= $order['payment_status'] === 'paid' ? 'We’ll send a confirmation to' : 'We’ll send payment instructions to' ?> <strong style="color:var(--ink)"><?= e($order['email']) ?></strong>.</p>

        <div class="notice <?= $order['payment_status'] === 'paid' ? 'notice-success' : 'notice-warn' ?>" style="margin:18px 0">
          <?= icon($order['payment_status'] === 'paid' ? 'check' : 'clock') ?>
          <div>
            <?php if ($order['payment_status'] === 'paid'): ?>
              <strong>Payment received.</strong> We are preparing your order. You will get a shipping confirmation with tracking.
            <?php else: ?>
              <strong>Order placed — payment pending.</strong> Follow the payment instructions in your email. Your order is released once payment is received.
            <?php endif; ?>
          </div>
        </div>

        <div class="grid grid-2" style="gap:20px;align-items:start">
          <div>
            <h2 style="font-size:1.05rem">Items</h2>
            <?php foreach ($order['items'] as $item): ?>
              <div class="order-line">
                <div>
                  <strong><?= e($item['name']) ?></strong>
                  <div class="small muted">SKU <?= e($item['sku']) ?> · Qty <?= (int) $item['qty'] ?></div>
                </div>
                <div style="text-align:right"><strong><?= e(Money::format((float) $item['line_total'])) ?></strong></div>
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
          <div>
            <h2 style="font-size:1.05rem">Delivering to</h2>
            <p class="small muted" style="white-space:pre-line"><?= e($address) ?></p>
            <h2 style="font-size:1.05rem;margin-top:16px">Shipping method</h2>
            <p class="small muted"><?= e($order['shipping_method_name'] ?: 'To be confirmed') ?></p>
            <h2 style="font-size:1.05rem;margin-top:16px">Payment</h2>
            <p class="small" style="margin:0"><span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span> · <?= $order['payment_method'] === 'manual' ? 'Invoice' : 'PayPal' ?></p>
          </div>
        </div>

        <?php if ($createdAccount): ?>
          <div class="notice notice-info" style="margin-top:18px"><?= icon('user') ?><div>Your account is ready — use <strong><?= e($order['email']) ?></strong> to <a href="<?= url('/login') ?>" class="bold">sign in</a> and track this order.</div></div>
        <?php endif; ?>

        <div class="flex wrap mt-3">
          <?php if (auth()): ?><a class="btn btn-primary" href="<?= url('/account/orders/' . urlencode($order['order_number'])) ?>">View order</a><?php endif; ?>
          <a class="btn btn-outline" href="<?= url('/shop') ?>">Continue shopping</a>
          <button class="btn btn-ghost" type="button" data-print>Print receipt</button>
        </div>
        <p class="small muted mt-2" style="margin-bottom:0">Questions? Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a> with order <?= e($order['order_number']) ?>.</p>
      </div>
    </div>
  </div>
</section>
