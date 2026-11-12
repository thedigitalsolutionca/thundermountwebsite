<?php
use App\Core\Csrf;
$errs = errors();
$edit = $editTier;
$value = static fn (string $key, string $default = '') => old($key, $edit[$key] ?? $default);
?>
<div class="admin-head">
  <div><h2>Dealer tiers</h2><p>Discounts are applied automatically to signed-in dealer orders.</p></div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/dealers') ?>">Back to dealers</a>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.5fr 1fr;align-items:start">
  <div class="admin-card">
    <div class="admin-card-head"><div><h3>All tiers</h3><span><?= count($tiers) ?> total</span></div></div>
    <?php if ($tiers === []): ?>
      <div class="admin-empty"><?= icon('tag') ?><p>No tiers yet. Create the first one so dealers can be approved.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Tier</th><th>Discount</th><th>Min order</th><th>Free ship over</th><th>Dealers</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($tiers as $tier): ?>
              <tr>
                <td><strong><?= e($tier['name']) ?></strong><?php if (!$tier['is_active']): ?> <span class="badge">Inactive</span><?php endif; ?><?php if ($tier['notes']): ?><div class="tiny muted"><?= e($tier['notes']) ?></div><?php endif; ?></td>
                <td><?= e(rtrim(rtrim(number_format((float) $tier['discount_percent'], 2, '.', ''), '0'), '.')) ?>%</td>
                <td><?= e(number_format((float) $tier['min_order_total'], 2)) ?></td>
                <td><?= $tier['free_shipping_min'] !== null ? e(number_format((float) $tier['free_shipping_min'], 2)) : '—' ?></td>
                <td><?= (int) $tier['dealer_count'] ?></td>
                <td>
                  <div class="flex" style="gap:4px;justify-content:flex-end">
                    <a class="btn btn-xs btn-outline" href="<?= url('/admin/dealers/tiers?edit=' . (int) $tier['id']) ?>">Edit</a>
                    <form action="<?= url('/admin/dealers/tiers/' . (int) $tier['id'] . '/delete') ?>" method="post" data-confirm="Delete this tier?">
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
    <div class="admin-card-head"><div><h3><?= $edit ? 'Edit tier' : 'New tier' ?></h3></div><?php if ($edit): ?><a class="small bold" href="<?= url('/admin/dealers/tiers') ?>">Cancel</a><?php endif; ?></div>
    <div class="admin-card-body">
      <?php if ($errs !== []): ?><div class="admin-alert"><?= icon('bell') ?><div><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div><?php endif; ?>
      <form action="<?= $edit ? url('/admin/dealers/tiers/' . (int) $edit['id']) : url('/admin/dealers/tiers') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="tier-name">Name</label><input class="input" id="tier-name" name="name" value="<?= e($value('name')) ?>" required placeholder="Trade, Installer, Distributor"></div>
        <div class="field"><label for="tier-discount">Discount percent</label><input class="input" id="tier-discount" name="discount_percent" type="number" step="0.01" min="0" max="100" value="<?= e($value('discount_percent', '0')) ?>" required></div>
        <div class="field"><label for="tier-min">Minimum order total</label><input class="input" id="tier-min" name="min_order_total" type="number" step="0.01" min="0" value="<?= e($value('min_order_total', '0')) ?>"></div>
        <div class="field"><label for="tier-free">Free shipping over <span class="muted">(optional)</span></label><input class="input" id="tier-free" name="free_shipping_min" type="number" step="0.01" min="0" value="<?= e($value('free_shipping_min')) ?>"></div>
        <div class="field"><label for="tier-notes">Notes</label><input class="input" id="tier-notes" name="notes" maxlength="255" value="<?= e($value('notes')) ?>"></div>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= $edit ? ((int) $edit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Active</span></label>
        <button class="btn btn-primary btn-block mt-2" type="submit"><?= $edit ? 'Save tier' : 'Create tier' ?></button>
      </form>
    </div>
  </div>
</div>
<?php clear_input(); ?>
