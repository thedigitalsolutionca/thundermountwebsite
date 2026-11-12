<?php
use App\Core\Csrf;
$errs = errors();
$user = auth();
$v = static fn (string $key, string $default = '') => old($key, $default);
?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Dealer Program', 'url' => '/dealer-program'], ['label' => 'Apply']]]) ?>
    <span class="eyebrow">Dealer application</span>
    <h1 style="margin-bottom:4px">Apply for trade pricing</h1>
    <p class="muted" style="margin:0;max-width:640px">Tell us about your business. We review applications during business hours and email you when your account is approved and priced.</p>
  </div>
</div>

<section class="section-tight" style="padding-top:10px">
  <div class="container" style="max-width:900px">
    <?php if (isset($errs['general'])): ?><div class="notice notice-error"><?= icon('bell') ?><div><?= e($errs['general']) ?></div></div><?php endif; ?>
    <form action="<?= url('/dealer/apply') ?>" method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="card">
        <div class="card-head"><h2 style="font-size:1.1rem;margin:0">Business details</h2><span class="small muted">Step 1 of 2</span></div>
        <div class="card-body">
          <div class="form-row">
            <div class="field">
              <label for="company">Business name</label>
              <input class="input <?= isset($errs['company']) ? 'invalid' : '' ?>" id="company" name="company" value="<?= e($v('company', $user['company'] ?? '')) ?>" required autocomplete="organization">
              <?php if (isset($errs['company'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['company']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label for="business_type">Business type</label>
              <select class="select <?= isset($errs['business_type']) ? 'invalid' : '' ?>" id="business_type" name="business_type" required>
                <option value="">Select one</option>
                <?php foreach (['Garage door dealer', 'Garage door installer', 'Opener installer', 'General contractor', 'Remodeler', 'Retailer / distributor', 'Property management', 'Other'] as $type): ?>
                  <option value="<?= e($type) ?>"<?= selected($type, $v('business_type')) ?>><?= e($type) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errs['business_type'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['business_type']) ?></span><?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="field"><label for="contact_name">Contact name</label><input class="input <?= isset($errs['contact_name']) ? 'invalid' : '' ?>" id="contact_name" name="contact_name" value="<?= e($v('contact_name', trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?>" required></div>
            <div class="field"><label for="dealer-email">Business email</label><input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="dealer-email" name="email" type="email" value="<?= e($v('email', $user['email'] ?? '')) ?>" required></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="dealer-phone">Phone</label><input class="input <?= isset($errs['phone']) ? 'invalid' : '' ?>" id="dealer-phone" name="phone" type="tel" value="<?= e($v('phone', $user['phone'] ?? '')) ?>" required></div>
            <div class="field"><label for="website">Website <span class="muted">(optional)</span></label><input class="input" id="website" name="website" type="url" value="<?= e($v('website')) ?>" placeholder="https://"></div>
          </div>
          <div class="field">
            <label for="resale_number">Resale / tax permit number <span class="muted">(optional)</span></label>
            <input class="input" id="resale_number" name="resale_number" value="<?= e($v('resale_number')) ?>">
            <span class="hint">Attach your certificate below if you have it.</span>
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-head"><h2 style="font-size:1.1rem;margin:0">Business address</h2><span class="small muted">Step 2 of 2</span></div>
        <div class="card-body">
          <div class="field"><label for="address1">Street address</label><input class="input <?= isset($errs['address1']) ? 'invalid' : '' ?>" id="address1" name="address1" value="<?= e($v('address1')) ?>" required autocomplete="address-line1"></div>
          <div class="field"><label for="address2">Suite / unit <span class="muted">(optional)</span></label><input class="input" id="address2" name="address2" value="<?= e($v('address2')) ?>"></div>
          <div class="form-row">
            <div class="field"><label for="city">City</label><input class="input" id="city" name="city" value="<?= e($v('city')) ?>" required></div>
            <div class="field"><label for="state">State / province</label><select class="select" id="state" name="state" required><?= \App\Core\View::partial('storefront/partials/state-options', ['selected' => $v('state')]) ?></select></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="postcode">ZIP / postal code</label><input class="input" id="postcode" name="postcode" value="<?= e($v('postcode')) ?>" required></div>
            <div class="field"><label for="country">Country</label><select class="select" id="country" name="country"><option value="US"<?= selected('US', $v('country', 'US')) ?>>United States</option><option value="CA"<?= selected('CA', $v('country', 'US')) ?>>Canada</option></select></div>
          </div>
          <div class="field">
            <label for="document">Resale certificate or business licence <span class="muted">(PDF, JPG or PNG, up to 10 MB)</span></label>
            <input class="input" id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            <?php if (isset($errs['document'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['document']) ?></span><?php endif; ?>
          </div>
          <label class="check" style="margin-top:12px">
            <input type="checkbox" name="agree" value="1" <?= old('agree') ? 'checked' : '' ?> required>
            <span>I confirm this information is accurate and that I am authorised to request trade pricing for this business.</span>
          </label>
          <?php if (isset($errs['agree'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['agree']) ?></span><?php endif; ?>
          <div class="flex wrap mt-2">
            <button class="btn btn-primary btn-lg" type="submit">Submit application</button>
            <a class="btn btn-ghost" href="<?= url('/dealer/account') ?>">Cancel</a>
          </div>
          <p class="small muted mt-2" style="margin-bottom:0">We use these details only to review your dealer account. See the <a href="<?= url('/page/privacy') ?>" class="bold">privacy policy</a>.</p>
        </div>
      </div>
    </form>
  </div>
</section>
<?php clear_input(); ?>
