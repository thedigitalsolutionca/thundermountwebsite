<?php
$errs = errors();
?>
<div class="auth-wrap">
  <aside class="auth-side">
    <div style="position:relative;z-index:1;max-width:460px">
      <span class="eyebrow" style="color:#FF8A8F">Customer account</span>
      <h2>Your orders and details, in one place.</h2>
      <p style="color:#C9CED4">Sign in to track orders, reorder the mounts you use, save job-site addresses and check out faster.</p>
      <ul class="checklist">
        <li>Order history and tracking numbers</li>
        <li>Saved delivery addresses</li>
        <li>Dealer pricing for approved trade accounts</li>
      </ul>
    </div>
  </aside>
  <div class="auth-form">
    <div class="card">
      <div class="card-body">
        <h1 style="font-size:1.8rem;margin-bottom:6px">Sign in</h1>
        <p class="muted small">New here? <a class="bold red" href="<?= url('/register') ?>">Create an account</a>.</p>

        <?php if (!empty($errs['email'])): ?><div class="notice notice-error"><?= icon('bell') ?><div><?= e($errs['email']) ?></div></div><?php endif; ?>

        <form action="<?= url('/login') ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="next" value="<?= e($next ?? '') ?>">
          <div class="field">
            <label for="login-email">Email address</label>
            <input class="input" id="login-email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
          </div>
          <div class="field">
            <div class="flex between">
              <label for="login-password">Password</label>
              <a class="small red bold" href="<?= url('/forgot-password') ?>">Forgot password?</a>
            </div>
            <input class="input" id="login-password" name="password" type="password" autocomplete="current-password" required>
          </div>
          <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
        </form>

        <div class="divider"></div>
        <div class="text-center">
          <p class="small muted" style="margin-bottom:8px">Bought as a guest? You can still <a href="<?= url('/order-lookup') ?>" class="bold">look up an order</a>.</p>
          <p class="small" style="margin:0">Trade customer? <a href="<?= url('/dealer/login') ?>" class="bold red">Dealer sign-in</a> · <a href="<?= url('/dealer/apply') ?>">Apply for an account</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
