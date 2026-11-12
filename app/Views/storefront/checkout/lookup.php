<?php $errs = errors(); ?>
<section class="section">
  <div class="container" style="max-width:560px">
    <div class="card">
      <div class="card-body" style="padding:30px">
        <span class="eyebrow">Order lookup</span>
        <h1 style="font-size:1.8rem;margin-bottom:6px">Find your order</h1>
        <p class="muted small">Enter the order number from your confirmation email and the email address used at checkout.</p>
        <?php if (isset($errs['lookup'])): ?><div class="notice notice-error"><?= icon('bell') ?><div><?= e($errs['lookup']) ?></div></div><?php endif; ?>
        <form action="<?= url('/order-lookup') ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <div class="field">
            <label for="lookup-order">Order number</label>
            <input class="input" id="lookup-order" name="order_number" value="<?= e(old('order_number')) ?>" placeholder="TM-20260101-ABC123" required autofocus>
          </div>
          <div class="field">
            <label for="lookup-email">Email address</label>
            <input class="input" id="lookup-email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email">
          </div>
          <button class="btn btn-primary btn-block" type="submit">Find order</button>
        </form>
        <p class="small muted mt-2" style="margin-bottom:0">Have an account? <a href="<?= url('/login?next=' . urlencode('/account/orders')) ?>" class="bold">Sign in</a> to see all your orders.</p>
      </div>
    </div>
  </div>
</section>
