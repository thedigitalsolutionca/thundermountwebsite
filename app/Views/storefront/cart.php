<?php
use App\Core\Csrf;
$checkoutReady = $readiness['ready'];
?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Your cart']]]) ?>
    <span class="eyebrow">Cart</span>
    <h1 style="margin-bottom:4px">Your cart</h1>
    <p class="muted" style="margin:0"><?= (int) $summary['count'] ?> item<?= (int) $summary['count'] === 1 ? '' : 's' ?></p>
  </div>
</div>

<section class="section-tight" style="padding-top:10px">
  <div class="container">
    <?php if ($summary['items'] === []): ?>
      <div class="empty card card-body" style="max-width:620px;margin:0 auto">
        <?= icon('cart') ?>
        <h2 style="font-size:1.4rem">Your cart is empty</h2>
        <p>Find the right mount for your door and opener, or call us if you need help sizing.</p>
        <div class="flex" style="justify-content:center;flex-wrap:wrap">
          <a class="btn btn-primary" href="<?= url('/shop') ?>">Shop products</a>
          <a class="btn btn-outline" href="tel:8053386680">Call 805-338-6680</a>
        </div>
      </div>
    <?php else: ?>
      <div class="checkout">
        <div>
          <div class="card">
            <div class="card-head">
              <h2 style="font-size:1.1rem;margin:0">Items</h2>
              <a class="small bold" href="<?= url('/shop') ?>">Continue shopping</a>
            </div>
            <div class="card-body">
              <?php foreach ($summary['items'] as $item): ?>
                <div class="cart-line cart-line-lg">
                  <img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['product']['name']) ?>" width="96" height="96">
                  <div>
                    <div class="name"><a href="<?= e(url($item['product']['url'])) ?>"><?= e($item['product']['name']) ?></a></div>
                    <div class="meta">Model <?= e($item['product']['model']) ?> · SKU <?= e($item['product']['sku']) ?></div>
                    <div class="meta"><?= e(money($item['product']['price_info']['unit_cents'])) ?> each<?= $item['product']['price_info']['is_dealer'] ? ' · dealer price' : '' ?></div>
                    <?php if (!$item['available']): ?><div class="meta" style="color:var(--red)">This item is no longer available. Remove it to continue.</div><?php endif; ?>
                    <div class="flex wrap" style="gap:8px;margin-top:8px">
                      <form action="<?= url('/cart/update') ?>" method="post" class="qty-form" data-cart-update>
                        <?= csrf_field() ?>
                        <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                        <div class="qty qty-sm">
                          <button type="button" data-qty-dec aria-label="Decrease quantity for <?= e($item['product']['name']) ?>">−</button>
                          <label class="sr-only" for="qty-<?= (int) $item['item_id'] ?>">Quantity</label>
                          <input id="qty-<?= (int) $item['item_id'] ?>" type="number" name="qty" value="<?= (int) $item['qty'] ?>" min="0" max="99" inputmode="numeric" data-auto-submit-qty>
                          <button type="button" data-qty-inc aria-label="Increase quantity for <?= e($item['product']['name']) ?>">+</button>
                        </div>
                        <noscript><button class="btn btn-xs btn-outline" type="submit">Update</button></noscript>
                      </form>
                      <form action="<?= url('/cart/remove') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                        <button class="remove" type="submit">Remove</button>
                      </form>
                    </div>
                  </div>
                  <strong style="white-space:nowrap"><?= e(money($item['line_cents'])) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="card mt-3">
            <div class="card-body">
              <h2 style="font-size:1.05rem;margin-top:0">Discount code</h2>
              <?php if ($summary['discount_code'] !== '' && $summary['discount']): ?>
                <div class="flex between wrap">
                  <p class="small" style="margin:0"><strong><?= e($summary['discount_code']) ?></strong> applied — saving <?= e(money($summary['discount_amount_cents'])) ?>.</p>
                  <form action="<?= url('/cart/discount/remove') ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="btn btn-xs btn-ghost" type="submit">Remove code</button>
                  </form>
                </div>
              <?php else: ?>
                <?php if ($summary['discount_error']): ?><div class="notice notice-error"><?= icon('bell') ?><div><?= e($summary['discount_error']) ?></div></div><?php endif; ?>
                <form action="<?= url('/cart/discount') ?>" method="post" class="flex wrap">
                  <?= csrf_field() ?>
                  <label class="sr-only" for="discount-code">Discount code</label>
                  <input class="input" id="discount-code" name="code" placeholder="Enter a code" style="max-width:240px" value="<?= e($summary['discount_code']) ?>">
                  <button class="btn btn-dark" type="submit">Apply</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <aside class="summary-sticky">
          <div class="card">
            <div class="card-head"><h2 style="font-size:1.1rem;margin:0">Order summary</h2></div>
            <div class="card-body">
              <div class="totals-row"><span>Subtotal</span><span><?= e(money($summary['subtotal_cents'])) ?></span></div>
              <?php if ($summary['discount_amount_cents'] > 0): ?>
                <div class="totals-row" style="color:var(--green)"><span>Discount</span><span>−<?= e(money($summary['discount_amount_cents'])) ?></span></div>
              <?php endif; ?>
              <div class="totals-row"><span>Shipping</span><span class="muted small">Calculated at checkout</span></div>
              <div class="totals-row"><span>Tax</span><span class="muted small">Calculated at checkout</span></div>
              <div class="totals-row total"><span>Estimated total</span><span><?= e(money($summary['subtotal_cents'] - $summary['discount_amount_cents'])) ?></span></div>

              <?php if ($summary['has_unavailable']): ?>
                <div class="notice notice-warn" style="margin-top:12px"><?= icon('bell') ?><div>Remove unavailable items before checkout.</div></div>
              <?php endif; ?>

              <?php if ($checkoutReady && !$summary['has_unavailable']): ?>
                <a class="btn btn-primary btn-block btn-lg mt-2" href="<?= url('/checkout') ?>">Secure checkout <?= icon('lock') ?></a>
              <?php elseif ($readiness['manual'] && !$summary['has_unavailable']): ?>
                <div class="notice notice-info" style="margin-top:12px"><?= icon('bell') ?><div>Shipping and tax are not available online yet. We can review your cart and send a confirmed invoice; no payment is taken when you request one.</div></div>
                <a class="btn btn-primary btn-block btn-lg mt-2" href="<?= url('/checkout') ?>">Request an invoice <?= icon('arrow-right') ?></a>
              <?php else: ?>
                <div class="notice notice-info" style="margin-top:12px">
                  <?= icon('phone') ?>
                  <div><strong>Online checkout is temporarily unavailable.</strong> Call <a href="tel:8053386680" class="bold">805-338-6680</a> and we will take your order.</div>
                </div>
              <?php endif; ?>

              <?php if ($readiness['paypal']): ?>
              <div class="payments-row" aria-label="Accepted payment methods">
                <span class="pay-logo paypal">PayPal</span>
                <span class="pay-logo">VISA</span>
                <span class="pay-logo">Mastercard</span>
                <span class="pay-logo">Venmo</span>
                <span class="pay-logo">Apple&nbsp;Pay</span>
                <span class="pay-logo">G&nbsp;Pay</span>
              </div>
              <p class="tiny muted" style="text-align:center;margin:10px 0 0"><?= icon('lock') ?> Payment is processed securely by PayPal. Card details never touch our server.</p>
              <?php elseif ($readiness['manual']): ?>
              <p class="tiny muted" style="text-align:center;margin:10px 0 0">Pay by invoice: no payment is collected online. Our team confirms the next steps.</p>
              <?php endif; ?>
            </div>
          </div>
          <div class="card card-body mt-2 small muted">
            <strong style="color:var(--ink)">Need help?</strong>
            <p style="margin:4px 0 0">Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>. We can confirm fitment before you order.</p>
          </div>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>
