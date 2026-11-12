<?php
$errs = errors();
?>
<div class="auth-wrap">
  <aside class="auth-side">
    <div style="position:relative;z-index:1;max-width:460px">
      <span class="eyebrow" style="color:#FF8A8F">Join Thunder Mounts</span>
      <h2>A better standard for your garage.</h2>
      <p style="color:#C9CED4">Create an account to order online, track shipments and keep your installation details together. Installers can apply for a dealer account after registering.</p>
      <ul class="checklist">
        <li>Fast checkout with saved addresses</li>
        <li>Order history and reorder</li>
        <li>Review products you have purchased</li>
      </ul>
    </div>
  </aside>
  <div class="auth-form" style="padding-top:40px;padding-bottom:40px">
    <div class="card">
      <div class="card-body">
        <h1 style="font-size:1.8rem;margin-bottom:6px">Create your account</h1>
        <p class="muted small">Already registered? <a class="bold red" href="<?= url('/login') ?>">Sign in</a>.</p>

        <form action="<?= url('/register') ?>" method="post" novalidate>
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="field">
              <label for="first_name">First name</label>
              <input class="input <?= isset($errs['first_name']) ? 'invalid' : '' ?>" id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" required autocomplete="given-name">
              <?php if (isset($errs['first_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['first_name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="last_name">Last name</label>
              <input class="input <?= isset($errs['last_name']) ? 'invalid' : '' ?>" id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" required autocomplete="family-name">
              <?php if (isset($errs['last_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['last_name']) ?></span><?php endif; ?>
            </div>
          </div>
          <div class="field">
            <label for="reg-email">Email address</label>
            <input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="reg-email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email">
            <?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="reg-phone">Phone</label>
              <input class="input <?= isset($errs['phone']) ? 'invalid' : '' ?>" id="reg-phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" required autocomplete="tel">
              <?php if (isset($errs['phone'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['phone']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="company">Company <span class="muted">(optional)</span></label>
              <input class="input" id="company" name="company" value="<?= e(old('company')) ?>" autocomplete="organization">
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="reg-password">Password</label>
              <input class="input <?= isset($errs['password']) ? 'invalid' : '' ?>" id="reg-password" name="password" type="password" required minlength="8" autocomplete="new-password">
              <span class="hint">At least 8 characters.</span>
              <?php if (isset($errs['password'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['password']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="reg-password2">Confirm password</label>
              <input class="input <?= isset($errs['password_confirmation']) ? 'invalid' : '' ?>" id="reg-password2" name="password_confirmation" type="password" required autocomplete="new-password">
              <?php if (isset($errs['password_confirmation'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['password_confirmation']) ?></span><?php endif; ?>
            </div>
          </div>
          <label class="check" style="margin:12px 0 16px">
            <input type="checkbox" name="accept_terms" value="1" <?= old('accept_terms') ? 'checked' : '' ?> required>
            <span>I agree to the <a href="<?= url('/page/terms') ?>" target="_blank" class="bold">Terms of Service</a> and <a href="<?= url('/page/privacy') ?>" target="_blank" class="bold">Privacy Policy</a>.</span>
          </label>
          <?php if (isset($errs['accept_terms'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['accept_terms']) ?></span><?php endif; ?>
          <button class="btn btn-primary btn-block btn-lg" type="submit">Create account</button>
        </form>
      </div>
    </div>
  </div>
</div>
