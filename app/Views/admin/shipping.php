<?php
use App\Core\Csrf;
$errs = errors();
$zoneEdit = $editZone;
$methodEdit = $editMethod;
$zoneVal = static fn (string $key, string $default = '') => old($key, $zoneEdit[$key] ?? $default);
$methodVal = static fn (string $key, string $default = '') => old($key, $methodEdit[$key] ?? $default);
?>
<div class="admin-head">
  <div><h2>Shipping</h2><p>Zones match the customer's country and state. Methods within a zone set the price.</p></div>
  <span class="badge <?= $configured ? 'badge-green' : 'badge-amber' ?>"><?= $configured ? 'Checkout shipping configured' : 'No active shipping methods' ?></span>
</div>

<?php if ($errs !== []): ?>
  <div class="admin-alert"><?= icon('bell') ?><div><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <div><h3>Base location</h3><span>Ship-from address used as the origin reference for your shipping setup.</span></div>
    <span class="badge badge-green"><?= e($origin['city'] ?: $origin['state']) ?>, <?= e($origin['country']) ?> <?= e($origin['postcode']) ?></span>
  </div>
  <div class="admin-card-body">
    <form action="<?= url('/admin/shipping/origin') ?>" method="post">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="field"><label for="origin-country">Country</label><input class="input" id="origin-country" name="origin_country" value="<?= e(old('origin_country', $origin['country'])) ?>" maxlength="2" required><span class="hint">Two-letter code, for example US.</span></div>
        <div class="field"><label for="origin-state">State / province</label><input class="input" id="origin-state" name="origin_state" value="<?= e(old('origin_state', $origin['state'])) ?>" maxlength="60" required placeholder="CA"></div>
      </div>
      <div class="form-row">
        <div class="field"><label for="origin-city">City</label><input class="input" id="origin-city" name="origin_city" value="<?= e(old('origin_city', $origin['city'])) ?>" maxlength="120" placeholder="Simi Valley"></div>
        <div class="field"><label for="origin-postcode">ZIP / postal code</label><input class="input" id="origin-postcode" name="origin_postcode" value="<?= e(old('origin_postcode', $origin['postcode'])) ?>" maxlength="20" required placeholder="93063"></div>
      </div>
      <button class="btn btn-primary" type="submit">Save base location</button>
    </form>
  </div>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.5fr 1fr;align-items:start">
  <div class="stack">
    <?php if ($zones === []): ?>
      <div class="admin-card"><div class="admin-empty"><?= icon('truck') ?><p>No shipping zones yet. Create a zone and at least one method before taking orders.</p></div></div>
    <?php endif; ?>
    <?php foreach ($zones as $zone): ?>
      <div class="admin-card">
        <div class="admin-card-head">
          <div>
            <h3><?= e($zone['name']) ?><?php if ((int) $zone['is_default']): ?> <span class="badge badge-blue">Default</span><?php endif; ?><?php if (!(int) $zone['is_active']): ?> <span class="badge">Inactive</span><?php endif; ?></h3>
            <span><?= e($zone['countries']) ?> · <?= $zone['states'] ? e($zone['states']) : 'All states' ?></span>
          </div>
          <div class="flex" style="gap:4px">
            <a class="btn btn-xs btn-outline" href="<?= url('/admin/shipping?zone=' . (int) $zone['id']) ?>">Edit</a>
            <form action="<?= url('/admin/shipping/zones/' . (int) $zone['id'] . '/delete') ?>" method="post" data-confirm="Delete this zone?">
              <?= csrf_field() ?><button class="btn btn-xs btn-danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
        <?php $methods = $methodsByZone[(int) $zone['id']] ?? []; ?>
        <?php if ($methods === []): ?>
          <div class="admin-card-body"><p class="small muted" style="margin:0">No methods in this zone yet.</p></div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Method</th><th>Type</th><th>Rate</th><th>Free over</th><th>ETA</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($methods as $method): ?>
                  <tr>
                    <td><strong><?= e($method['name']) ?></strong></td>
                    <td class="small"><?= e(ucfirst($method['type'])) ?><?php if ($method['type'] === 'weight'): ?> (<?= e(number_format((float) $method['per_unit'], 2)) ?>/lb)<?php endif; ?></td>
                    <td><?= e(number_format((float) $method['rate'], 2)) ?></td>
                    <td class="small"><?= $method['free_over'] !== null ? e(number_format((float) $method['free_over'], 2)) : '—' ?></td>
                    <td class="small"><?= $method['est_days_min'] !== null ? (int) $method['est_days_min'] . '–' . (int) $method['est_days_max'] . ' days' : '—' ?></td>
                    <td><?= (int) $method['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge">Off</span>' ?></td>
                    <td>
                      <div class="flex" style="gap:4px;justify-content:flex-end">
                        <a class="btn btn-xs btn-outline" href="<?= url('/admin/shipping?method=' . (int) $method['id'] . '#method-form') ?>">Edit</a>
                        <form action="<?= url('/admin/shipping/methods/' . (int) $method['id'] . '/delete') ?>" method="post" data-confirm="Delete this method?">
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
    <?php endforeach; ?>
  </div>

  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3><?= $zoneEdit ? 'Edit zone' : 'New zone' ?></h3></div><?php if ($zoneEdit): ?><a class="small bold" href="<?= url('/admin/shipping') ?>">Cancel</a><?php endif; ?></div>
      <div class="admin-card-body">
        <form action="<?= $zoneEdit ? url('/admin/shipping/zones/' . (int) $zoneEdit['id']) : url('/admin/shipping/zones') ?>" method="post">
          <?= csrf_field() ?>
          <div class="field"><label for="zone-name">Zone name</label><input class="input" id="zone-name" name="name" value="<?= e($zoneVal('name')) ?>" required placeholder="Continental US"></div>
          <div class="field"><label for="zone-countries">Countries</label><input class="input" id="zone-countries" name="countries" value="<?= e($zoneVal('countries', 'US')) ?>" required placeholder="US"><span class="hint">Comma-separated two-letter codes.</span></div>
          <div class="field"><label for="zone-states">States</label><input class="input" id="zone-states" name="states" value="<?= e($zoneVal('states')) ?>" placeholder="CA,NV,AZ — blank for all"><span class="hint">Leave blank to include every state in the selected countries.</span></div>
          <label class="check"><input type="checkbox" name="is_default" value="1" <?= $zoneEdit ? ((int) $zoneEdit['is_default'] === 1 ? 'checked' : '') : '' ?>><span>Default fallback zone</span></label>
          <label class="check"><input type="checkbox" name="is_active" value="1" <?= $zoneEdit ? ((int) $zoneEdit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Active</span></label>
          <button class="btn btn-primary btn-block mt-2" type="submit"><?= $zoneEdit ? 'Save zone' : 'Create zone' ?></button>
        </form>
      </div>
    </div>

    <div class="admin-card" id="method-form">
      <div class="admin-card-head"><div><h3><?= $methodEdit ? 'Edit method' : 'New method' ?></h3></div><?php if ($methodEdit): ?><a class="small bold" href="<?= url('/admin/shipping') ?>">Cancel</a><?php endif; ?></div>
      <div class="admin-card-body">
        <?php if ($zones === []): ?>
          <p class="small muted">Create a zone first.</p>
        <?php else: ?>
          <form action="<?= $methodEdit ? url('/admin/shipping/methods/' . (int) $methodEdit['id']) : url('/admin/shipping/methods') ?>" method="post">
            <?= csrf_field() ?>
            <div class="field"><label for="method-zone">Zone</label><select class="select" id="method-zone" name="zone_id" required>
              <?php foreach ($zones as $zone): ?><option value="<?= (int) $zone['id'] ?>"<?= selected((string) $zone['id'], (string) $methodVal('zone_id')) ?>><?= e($zone['name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field"><label for="method-name">Method name</label><input class="input" id="method-name" name="name" value="<?= e($methodVal('name')) ?>" required placeholder="UPS Ground"></div>
            <div class="form-row">
              <div class="field"><label for="method-type">Type</label><select class="select" id="method-type" name="type"><option value="flat"<?= selected('flat', $methodVal('type', 'flat')) ?>>Flat rate</option><option value="weight"<?= selected('weight', $methodVal('type', 'flat')) ?>>By weight</option><option value="free"<?= selected('free', $methodVal('type', 'flat')) ?>>Free</option></select></div>
              <div class="field"><label for="method-rate">Base rate</label><input class="input" id="method-rate" name="rate" type="number" step="0.01" min="0" value="<?= e($methodVal('rate', '0.00')) ?>"></div>
            </div>
            <div class="form-row">
              <div class="field"><label for="method-per-unit">Per lb</label><input class="input" id="method-per-unit" name="per_unit" type="number" step="0.01" min="0" value="<?= e($methodVal('per_unit', '0.00')) ?>"><span class="hint">Weight type only.</span></div>
              <div class="field"><label for="method-free-over">Free over</label><input class="input" id="method-free-over" name="free_over" type="number" step="0.01" min="0" value="<?= e($methodVal('free_over')) ?>"><span class="hint">Subtotal threshold.</span></div>
            </div>
            <div class="form-row">
              <div class="field"><label for="method-min-weight">Min weight (lb)</label><input class="input" id="method-min-weight" name="min_weight" type="number" step="0.01" min="0" value="<?= e($methodVal('min_weight', '0.00')) ?>"></div>
              <div class="field"><label for="method-max-weight">Max weight (lb)</label><input class="input" id="method-max-weight" name="max_weight" type="number" step="0.01" min="0" value="<?= e($methodVal('max_weight')) ?>"></div>
            </div>
            <div class="form-row">
              <div class="field"><label for="method-days-min">ETA min days</label><input class="input" id="method-days-min" name="est_days_min" type="number" min="0" value="<?= e($methodVal('est_days_min')) ?>"></div>
              <div class="field"><label for="method-days-max">ETA max days</label><input class="input" id="method-days-max" name="est_days_max" type="number" min="0" value="<?= e($methodVal('est_days_max')) ?>"></div>
            </div>
            <div class="form-row">
              <div class="field"><label for="method-sort">Sort order</label><input class="input" id="method-sort" name="sort_order" type="number" value="<?= e($methodVal('sort_order', '0')) ?>"></div>
              <div class="field" style="display:flex;align-items:flex-end"><label class="check"><input type="checkbox" name="is_active" value="1" <?= $methodEdit ? ((int) $methodEdit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Active</span></label></div>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= $methodEdit ? 'Save method' : 'Create method' ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php clear_input(); ?>
