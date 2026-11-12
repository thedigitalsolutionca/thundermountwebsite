<?php $errs = errors(); ?>
<div class="auth-wrap">
  <aside class="auth-side">
    <div style="position:relative;z-index:1;max-width:460px">
      <span class="eyebrow" style="color:#FF8A8F">Dealer portal</span>
      <h2>Trade pricing, ready when you are.</h2>
      <p style="color:#C9CED4">Sign in to see your dealer prices at checkout, review trade orders and keep your business details current.</p>
      <ul class="checklist">
        <li>Tier pricing applied automatically</li>
        <li>Trade order history</li>
        <li>Direct support from the factory</li>
      </ul>
    </div>
  </aside>
  <div class="auth-form">
    <div class="card">
      <div class="card-body">
        <span class="eyebrow">Approved dealers</span>
        <h1 style="font-size:1.8rem;margin-bottom:6px">Dealer sign-in</h1>
        <p class="muted small">Use the account tied to your approved dealer application.</p>

        <?php if (isset($errs['email'])): ?><div class="notice notice-error"><?= icon('bell') ?><div><?= e($errs['email']) ?></div></div><?php endif; ?>

        <form action="<?= url('/login') ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="next" value="/dealer/account">
          <div class="field">
            <label for="dealer-login-email">Email address</label>
            <input class="input" id="dealer-login-email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" autofocus>
          </div>
          <div class="field">
            <div class="flex between"><label for="dealer-login-password">Password</label><a class="small red bold" href="<?= url('/forgot-password') ?>">Forgot password?</a></div>
            <input class="input" id="dealer-login-password" name="password" type="password" required autocomplete="current-password">
          </div>
          <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in to dealer portal</button>
        </form>

        <div class="divider"></div>
        <p class="small muted" style="margin:0">Not a dealer yet? <a href="<?= url('/dealer/apply') ?>" class="bold red">Apply for an account</a>.<br>Customer account? <a href="<?= url('/login') ?>">Sign in here</a>.</p>
      </div>
    </div>
  </div>
</div>
<?php clear_input(); ?>
