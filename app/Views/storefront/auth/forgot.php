<?php $errs = errors(); ?>
<div class="auth-wrap" style="grid-template-columns:1fr">
  <div class="auth-form" style="padding:56px 24px">
    <div class="card">
      <div class="card-body">
        <span class="eyebrow">Account recovery</span>
        <h1 style="font-size:1.8rem;margin-bottom:6px">Reset your password</h1>
        <p class="muted small">Enter the email you use for Thunder Mounts and we will send a reset link. The link expires in one hour.</p>
        <form action="<?= url('/forgot-password') ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <div class="field">
            <label for="forgot-email">Email address</label>
            <input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="forgot-email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email" autofocus>
            <?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Email me a reset link</button>
        </form>
        <p class="small muted mt-2" style="margin-bottom:0">Remembered it? <a href="<?= url('/login') ?>" class="bold">Back to sign in</a>. Need a hand? Call <a href="tel:8053386680">805-338-6680</a>.</p>
      </div>
    </div>
  </div>
</div>
