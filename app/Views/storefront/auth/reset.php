<?php $errs = errors(); ?>
<div class="auth-wrap" style="grid-template-columns:1fr">
  <div class="auth-form" style="padding:56px 24px">
    <div class="card">
      <div class="card-body">
        <span class="eyebrow">Account recovery</span>
        <h1 style="font-size:1.8rem;margin-bottom:6px">Choose a new password</h1>
        <?php if (isset($errs['token'])): ?>
          <div class="notice notice-error"><?= icon('bell') ?><div><?= e($errs['token']) ?></div></div>
        <?php endif; ?>
        <form action="<?= url('/reset-password') ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
          <input type="hidden" name="email" value="<?= e($email ?? '') ?>">
          <div class="field">
            <label for="reset-password">New password</label>
            <input class="input <?= isset($errs['password']) ? 'invalid' : '' ?>" id="reset-password" name="password" type="password" required minlength="8" autocomplete="new-password" autofocus>
            <span class="hint">At least 8 characters.</span>
            <?php if (isset($errs['password'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['password']) ?></span><?php endif; ?>
          </div>
          <div class="field">
            <label for="reset-password2">Confirm new password</label>
            <input class="input <?= isset($errs['password_confirmation']) ? 'invalid' : '' ?>" id="reset-password2" name="password_confirmation" type="password" required autocomplete="new-password">
            <?php if (isset($errs['password_confirmation'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['password_confirmation']) ?></span><?php endif; ?>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Update password</button>
        </form>
        <p class="small muted mt-2" style="margin-bottom:0">The reset link is tied to <strong><?= e($email ?? '') ?></strong>. <a href="<?= url('/forgot-password') ?>" class="bold">Use a different email</a>.</p>
      </div>
    </div>
  </div>
</div>
