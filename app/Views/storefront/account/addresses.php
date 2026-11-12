<?php
use App\Core\Csrf;
$errs = errors();
$edit = $editAddress;
$value = static fn (string $key, string $default = '') => old($key, $edit[$key] ?? $default);
?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'My account', 'url' => '/account'], ['label' => 'Addresses']]]) ?>
    <div class="account-layout">
      <?= \App\Core\View::partial('storefront/account/sidebar') ?>
      <div>
        <div class="section-head" style="margin-bottom:18px">
          <div>
            <span class="eyebrow">Saved addresses</span>
            <h1 style="font-size:1.9rem;margin:0">Addresses</h1>
            <p class="muted" style="margin:6px 0 0">Save delivery addresses for faster checkout.</p>
          </div>
        </div>

        <div class="grid grid-2" style="align-items:start">
          <div class="stack">
            <?php if ($addresses === []): ?>
              <div class="empty card card-body">
                <?= icon('map-pin') ?>
                <h4>No addresses saved</h4>
                <p>Add your first address using the form.</p>
              </div>
            <?php else: ?>
              <?php foreach ($addresses as $address): ?>
                <div class="card card-body">
                  <div class="flex between">
                    <h3 style="font-size:1rem;margin:0"><?= e($address['label']) ?></h3>
                    <div class="flex" style="gap:6px">
                      <?php if ((int) $address['is_default_shipping']): ?><span class="badge badge-green">Default delivery</span><?php endif; ?>
                      <?php if ((int) $address['is_default_billing']): ?><span class="badge badge-blue">Default billing</span><?php endif; ?>
                    </div>
                  </div>
                  <p class="small muted" style="white-space:pre-line;margin:8px 0"><?= e(trim(
                      $address['first_name'] . ' ' . $address['last_name'] . "\n" .
                      ($address['company'] ? $address['company'] . "\n" : '') .
                      $address['address1'] . "\n" .
                      ($address['address2'] ? $address['address2'] . "\n" : '') .
                      $address['city'] . ', ' . $address['state'] . ' ' . $address['postcode']
                  )) ?></p>
                  <div class="flex wrap" style="gap:6px">
                    <a class="btn btn-xs btn-outline" href="<?= url('/account/addresses?edit=' . (int) $address['id']) ?>">Edit</a>
                    <form action="<?= url('/account/addresses/' . (int) $address['id'] . '/default') ?>" method="post">
                      <?= csrf_field() ?><input type="hidden" name="type" value="shipping">
                      <button class="btn btn-xs btn-ghost" type="submit">Make default delivery</button>
                    </form>
                    <form action="<?= url('/account/addresses/' . (int) $address['id'] . '/delete') ?>" method="post" data-confirm="Remove this address?">
                      <?= csrf_field() ?>
                      <button class="btn btn-xs btn-danger" type="submit">Remove</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="card">
            <div class="card-head">
              <h2 style="font-size:1.05rem;margin:0"><?= $edit ? 'Edit address' : 'Add an address' ?></h2>
              <?php if ($edit): ?><a class="small bold" href="<?= url('/account/addresses') ?>">Cancel edit</a><?php endif; ?>
            </div>
            <div class="card-body">
              <form action="<?= url('/account/addresses') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
                <div class="field">
                  <label for="label">Label</label>
                  <input class="input <?= isset($errs['label']) ? 'invalid' : '' ?>" id="label" name="label" value="<?= e($value('label', $edit ? $edit['label'] : 'Home')) ?>" placeholder="Home, Shop, Job site" required>
                  <?php if (isset($errs['label'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['label']) ?></span><?php endif; ?>
                </div>
                <div class="form-row">
                  <div class="field"><label for="addr-first">First name</label><input class="input" id="addr-first" name="first_name" value="<?= e($value('first_name', $edit['first_name'] ?? (auth()['first_name'] ?? ''))) ?>" required></div>
                  <div class="field"><label for="addr-last">Last name</label><input class="input" id="addr-last" name="last_name" value="<?= e($value('last_name', $edit['last_name'] ?? (auth()['last_name'] ?? ''))) ?>" required></div>
                </div>
                <div class="field"><label for="addr-company">Company <span class="muted">(optional)</span></label><input class="input" id="addr-company" name="company" value="<?= e($value('company', $edit['company'] ?? (auth()['company'] ?? ''))) ?>"></div>
                <div class="field"><label for="addr1">Address</label><input class="input" id="addr1" name="address1" value="<?= e($value('address1', $edit['address1'] ?? '')) ?>" required autocomplete="address-line1"></div>
                <div class="field"><label for="addr2">Apartment, suite, etc. <span class="muted">(optional)</span></label><input class="input" id="addr2" name="address2" value="<?= e($value('address2', $edit['address2'] ?? '')) ?>" autocomplete="address-line2"></div>
                <div class="form-row">
                  <div class="field"><label for="addr-city">City</label><input class="input" id="addr-city" name="city" value="<?= e($value('city', $edit['city'] ?? '')) ?>" required autocomplete="address-level2"></div>
                  <div class="field">
                    <label for="addr-state">State / province</label>
                    <select class="select" id="addr-state" name="state" required>
                      <?= \App\Core\View::partial('storefront/partials/state-options', ['selected' => $value('state', $edit['state'] ?? '')]) ?>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="field"><label for="addr-zip">ZIP / postal code</label><input class="input" id="addr-zip" name="postcode" value="<?= e($value('postcode', $edit['postcode'] ?? '')) ?>" required autocomplete="postal-code"></div>
                  <div class="field">
                    <label for="addr-country">Country</label>
                    <select class="select" id="addr-country" name="country">
                      <option value="US"<?= selected('US', $value('country', $edit['country'] ?? 'US')) ?>>United States</option>
                      <option value="CA"<?= selected('CA', $value('country', $edit['country'] ?? 'US')) ?>>Canada</option>
                    </select>
                  </div>
                </div>
                <div class="field"><label for="addr-phone">Phone</label><input class="input <?= isset($errs['phone']) ? 'invalid' : '' ?>" id="addr-phone" name="phone" type="tel" value="<?= e($value('phone', $edit['phone'] ?? (auth()['phone'] ?? ''))) ?>" required autocomplete="tel"><?php if (isset($errs['phone'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['phone']) ?></span><?php endif; ?></div>
                <button class="btn btn-primary btn-block" type="submit"><?= $edit ? 'Save changes' : 'Save address' ?></button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
