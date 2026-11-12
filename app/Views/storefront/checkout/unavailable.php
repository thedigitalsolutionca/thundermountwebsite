<?php use App\Core\Settings; ?>
<section class="section">
  <div class="container" style="max-width:720px">
    <div class="card card-body" style="padding:32px;text-align:center">
      <div class="empty" style="padding:0">
        <?= icon('phone') ?>
        <h1 style="font-size:1.9rem;margin-bottom:6px">Let's finish your order by phone</h1>
        <p class="muted" style="max-width:520px;margin:0 auto 16px">Online checkout is not available right now. Your cart is saved. Call us during business hours and we will take your order and payment over the phone.</p>
        <div class="flex" style="justify-content:center;flex-wrap:wrap">
          <a class="btn btn-primary btn-lg" href="tel:8053386680">Call <?= e(Settings::get('store.phone', '805-338-6680')) ?></a>
          <a class="btn btn-outline" href="mailto:<?= e(Settings::get('store.email', 'thunder@thundermounts.com')) ?>">Email us</a>
        </div>
        <p class="small muted mt-3"><?= e(Settings::get('store.hours', 'Monday–Friday 9:00–4:00, Saturday 10:00–2:00')) ?></p>
        <p class="small muted" style="margin-bottom:0">You can still <a href="<?= url('/cart') ?>" class="bold">review your cart</a> or <a href="<?= url('/shop') ?>" class="bold">keep shopping</a>.</p>
      </div>
    </div>
  </div>
</section>
