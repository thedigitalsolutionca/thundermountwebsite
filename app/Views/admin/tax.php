<?php
use App\Core\Csrf;
$errs = errors();
$edit = $editRate;
$value = static fn (string $key, string $default = '') => old($key, $edit[$key] ?? $default);
?>
<div class="admin-head">
  <div><h2>Sales tax</h2><p>Only charge tax where you are registered. Rates are applied by destination state/province.</p></div>
  <span class="badge <?= $configured ? 'badge-green' : 'badge-amber' ?>"><?= $configured ? 'Tax collection ready' : 'No active rates' ?></span>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.4fr 1fr;align-items:start">
  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Rates</h3><span><?= count($rates) ?> configured</span></div></div>
      <?php if ($rates === []): ?>
        <div class="admin-empty"><?= icon('percent') ?><p>No tax rates yet. Add the states where you collect sales tax.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Country</th><th>State</th><th>Rate</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($rates as $rate): ?>
                <tr>
                  <td><strong><?= e($rate['name']) ?></strong></td>
                  <td><?= e($rate['country']) ?></td>
                  <td><?= e($rate['state']) ?></td>
                  <td><?= e(rtrim(rtrim(number_format((float) $rate['rate'], 4, '.', ''), '0'), '.')) ?>%</td>
                  <td><?= (int) $rate['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge">Off</span>' ?></td>
                  <td>
                    <div class="flex" style="gap:4px;justify-content:flex-end">
                      <a class="btn btn-xs btn-outline" href="<?= url('/admin/tax?edit=' . (int) $rate['id']) ?>">Edit</a>
                      <form action="<?= url('/admin/tax/' . (int) $rate['id'] . '/delete') ?>" method="post" data-confirm="Delete this rate?">
                        <?= csrf_field() ?><button class="btn btn-xs btn-danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Tax settings</h3></div></div>
      <div class="admin-card-body">
        <form action="<?= url('/admin/tax/settings') ?>" method="post">
          <?= csrf_field() ?>
          <label class="check"><input type="checkbox" name="tax_enabled" value="1" <?= $enabled ? 'checked' : '' ?>><span>Collect sales tax</span></label>
          <label class="check"><input type="checkbox" name="shipping_taxable" value="1" <?= $shippingTaxable ? 'checked' : '' ?>><span>Shipping is taxable in my state</span></label>
          <button class="btn btn-primary mt-2" type="submit">Save tax settings</button>
        </form>
        <p class="tiny muted" style="margin:10px 0 0">Tax is charged by destination state/province. If collection is enabled and no active rate exists, checkout is paused until you add one; if rates exist but none match an address, that order is not taxed. Confirm your registration obligations with your accountant.</p>
      </div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><div><h3><?= $edit ? 'Edit rate' : 'New rate' ?></h3></div><?php if ($edit): ?><a class="small bold" href="<?= url('/admin/tax') ?>">Cancel</a><?php endif; ?></div>
    <div class="admin-card-body">
      <?php if ($errs !== []): ?><div class="admin-alert"><?= icon('bell') ?><div><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div><?php endif; ?>
      <form action="<?= $edit ? url('/admin/tax/' . (int) $edit['id']) : url('/admin/tax') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="rate-name">Name</label><input class="input" id="rate-name" name="name" value="<?= e($value('name')) ?>" required placeholder="California state + local"></div>
        <div class="form-row">
          <div class="field"><label for="rate-country">Country</label><input class="input" id="rate-country" name="country" value="<?= e($value('country', 'US')) ?>" maxlength="2" required></div>
          <div class="field"><label for="rate-state">State / province</label><input class="input" id="rate-state" name="state" value="<?= e($value('state')) ?>" placeholder="CA" maxlength="60" required></div>
        </div>
        <div class="field"><label for="rate-rate">Rate (%)</label><input class="input" id="rate-rate" name="rate" type="number" step="0.0001" min="0" max="100" value="<?= e($value('rate')) ?>" required></div>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= $edit ? ((int) $edit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Active</span></label>
        <button class="btn btn-primary btn-block mt-2" type="submit"><?= $edit ? 'Save rate' : 'Create rate' ?></button>
      </form>
    </div>
  </div>
</div>
<?php clear_input(); ?>
