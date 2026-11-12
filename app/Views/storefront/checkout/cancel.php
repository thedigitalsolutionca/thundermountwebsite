<?php $number = $order['order_number'] ?? ''; ?>
<section class="section">
  <div class="container" style="max-width:660px">
    <div class="card card-body" style="padding:32px;text-align:center">
      <div class="empty" style="padding:0">
        <?= icon('cart') ?>
        <h1 style="font-size:1.8rem;margin-bottom:6px">Checkout was cancelled</h1>
        <p class="muted">No payment was taken. Your cart is still saved, so you can pick up where you left off.</p>
        <?php if ($number !== ''): ?><p class="small muted">Reference: <strong><?= e($number) ?></strong><?php endif; ?>
        <div class="flex" style="justify-content:center;flex-wrap:wrap;margin-top:14px">
          <a class="btn btn-primary" href="<?= url('/checkout') ?>">Return to checkout</a>
          <a class="btn btn-outline" href="<?= url('/cart') ?>">View cart</a>
        </div>
        <p class="small muted mt-3" style="margin-bottom:0">Trouble paying? Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.</p>
      </div>
    </div>
  </div>
</section>
