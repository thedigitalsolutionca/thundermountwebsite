<?php
use App\Core\Csrf;
$user = auth();
$errs = errors();
?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'My account', 'url' => '/account'], ['label' => 'Settings']]]) ?>
    <div class="account-layout">
      <?= \App\Core\View::partial('storefront/account/sidebar') ?>
      <div>
        <div class="section-head" style="margin-bottom:18px">
          <div>
            <span class="eyebrow">Account settings</span>
            <h1 style="font-size:1.9rem;margin:0">Your details</h1>
          </div>
        </div>

        <div class="grid grid-2" style="align-items:start">
          <div class="card">
            <div class="card-head"><h2 style="font-size:1.05rem;margin:0">Profile</h2></div>
            <div class="card-body">
              <form action="<?= url('/account/profile') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="form-row">
                  <div class="field">
                    <label for="prof-first">First name</label>
                    <input class="input <?= isset($errs['first_name']) ? 'invalid' : '' ?>" id="prof-first" name="first_name" value="<?= e(old('first_name', $user['first_name'])) ?>" required>
                    <?php if (isset($errs['first_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['first_name']) ?></span><?php endif; ?>
                  </div>
                  <div class="field"><label for="prof-last">Last name</label><input class="input" id="prof-last" name="last_name" value="<?= e(old('last_name', $user['last_name'])) ?>" required></div>
                </div>
                <div class="field">
                  <label for="prof-email">Email address</label>
                  <input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="prof-email" name="email" type="email" value="<?= e(old('email', $user['email'])) ?>" required>
                  <?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?>
                </div>
                <div class="form-row">
                  <div class="field"><label for="prof-phone">Phone</label><input class="input" id="prof-phone" name="phone" type="tel" value="<?= e(old('phone', $user['phone'])) ?>"></div>
                  <div class="field"><label for="prof-company">Company</label><input class="input" id="prof-company" name="company" value="<?= e(old('company', $user['company'])) ?>"></div>
                </div>
                <button class="btn btn-primary" type="submit">Save details</button>
              </form>
            </div>
          </div>

          <div class="card" id="password">
            <div class="card-head"><h2 style="font-size:1.05rem;margin:0">Change password</h2></div>
            <div class="card-body">
              <?php if (isset($errs['password_form'])): ?><div class="notice notice-error"><?= e($errs['password_form']) ?></div><?php endif; ?>
              <form action="<?= url('/account/password') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="field"><label for="current-password">Current password</label><input class="input" id="current-password" name="current_password" type="password" required autocomplete="current-password"></div>
                <div class="field"><label for="new-password">New password</label><input class="input" id="new-password" name="password" type="password" required minlength="8" autocomplete="new-password"><span class="hint">At least 8 characters.</span></div>
                <div class="field"><label for="confirm-password">Confirm new password</label><input class="input" id="confirm-password" name="password_confirmation" type="password" required autocomplete="new-password"></div>
                <button class="btn btn-dark" type="submit">Update password</button>
              </form>
            </div>
          </div>
        </div>

        <div class="card card-body mt-3">
          <h3 style="font-size:1rem;margin-top:0">Account information</h3>
          <p class="small muted" style="margin:0">Email verification: <?= $user['email_verified_at'] ? 'verified' : 'not yet verified' ?> · Member since <?= e(fmt_date($user['created_at'])) ?>.</p>
          <p class="small muted" style="margin:6px 0 0">Need your account data corrected or removed? Email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php clear_input(); ?>
