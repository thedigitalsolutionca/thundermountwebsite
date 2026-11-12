<?php
use App\Core\Csrf;
$errs = errors();
$edit = $editDiscount;
$value = static fn (string $key, string $default = '') => old($key, $edit[$key] ?? $default);
$dateValue = static function (?string $raw): string {
    return $raw ? date('Y-m-d\TH:i', strtotime($raw)) : '';
};
?>
<div class="admin-head">
  <div><h2>Discounts</h2><p>Codes are validated server-side and usage limits are enforced at checkout.</p></div>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.4fr 1fr;align-items:start">
  <div class="admin-card">
    <div class="admin-card-head"><div><h3>Discount codes</h3><span><?= count($discounts) ?> total</span></div></div>
    <?php if ($discounts === []): ?>
      <div class="admin-empty"><?= icon('percent') ?><p>No discount codes yet.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Code</th><th>Discount</th><th>Minimum</th><th>Used</th><th>Window</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($discounts as $discount): ?>
              <tr>
                <td><strong><?= e($discount['code']) ?></strong><?php if ((int) $discount['dealer_only']): ?><div class="tiny muted">Dealer only</div><?php endif; ?><?php if ($discount['description']): ?><div class="tiny muted"><?= e($discount['description']) ?></div><?php endif; ?></td>
                <td><?= $discount['type'] === 'percent' ? e(rtrim(rtrim(number_format((float) $discount['value'], 2, '.', ''), '0'), '.')) . '%' : '$' . e(number_format((float) $discount['value'], 2)) ?></td>
                <td><?= (float) $discount['min_subtotal'] > 0 ? '$' . e(number_format((float) $discount['min_subtotal'], 2)) : '—' ?></td>
                <td><?= (int) $discount['used_count'] ?><?= $discount['usage_limit'] ? ' / ' . (int) $discount['usage_limit'] : '' ?></td>
                <td class="tiny muted"><?= $discount['starts_at'] ? e(fmt_date($discount['starts_at'])) : 'Now' ?> – <?= $discount['ends_at'] ? e(fmt_date($discount['ends_at'])) : 'No end' ?></td>
                <td><?= (int) $discount['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge">Inactive</span>' ?></td>
                <td>
                  <div class="flex" style="gap:4px;justify-content:flex-end">
                    <a class="btn btn-xs btn-outline" href="<?= url('/admin/discounts?edit=' . (int) $discount['id']) ?>">Edit</a>
                    <form action="<?= url('/admin/discounts/' . (int) $discount['id'] . '/delete') ?>" method="post" data-confirm="Delete this discount?">
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
    <div class="admin-card-head"><div><h3><?= $edit ? 'Edit code' : 'New code' ?></h3></div><?php if ($edit): ?><a class="small bold" href="<?= url('/admin/discounts') ?>">Cancel</a><?php endif; ?></div>
    <div class="admin-card-body">
      <?php if ($errs !== []): ?><div class="admin-alert"><?= icon('bell') ?><div><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div><?php endif; ?>
      <form action="<?= $edit ? url('/admin/discounts/' . (int) $edit['id']) : url('/admin/discounts') ?>" method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="code">Code</label><input class="input" id="code" name="code" value="<?= e($value('code')) ?>" required style="text-transform:uppercase" placeholder="TRADE10"></div>
        <div class="field"><label for="description">Description</label><input class="input" id="description" name="description" value="<?= e($value('description')) ?>" maxlength="190"></div>
        <div class="form-row">
          <div class="field"><label for="type">Type</label><select class="select" id="type" name="type"><option value="percent"<?= selected('percent', $value('type', 'percent')) ?>>Percent off</option><option value="fixed"<?= selected('fixed', $value('type', 'percent')) ?>>Fixed amount</option></select></div>
          <div class="field"><label for="value">Value</label><input class="input" id="value" name="value" type="number" step="0.01" min="0" value="<?= e($value('value')) ?>" required></div>
        </div>
        <div class="field"><label for="min_subtotal">Minimum subtotal</label><input class="input" id="min_subtotal" name="min_subtotal" type="number" step="0.01" min="0" value="<?= e($value('min_subtotal', '0')) ?>"></div>
        <div class="form-row">
          <div class="field"><label for="starts_at">Starts</label><input class="input" id="starts_at" name="starts_at" type="datetime-local" value="<?= e(old('starts_at', $dateValue($edit['starts_at'] ?? null))) ?>"></div>
          <div class="field"><label for="ends_at">Ends</label><input class="input" id="ends_at" name="ends_at" type="datetime-local" value="<?= e(old('ends_at', $dateValue($edit['ends_at'] ?? null))) ?>"></div>
        </div>
        <div class="form-row">
          <div class="field"><label for="usage_limit">Total uses</label><input class="input" id="usage_limit" name="usage_limit" type="number" min="1" value="<?= e($value('usage_limit')) ?>" placeholder="Unlimited"></div>
          <div class="field"><label for="per_user_limit">Per customer</label><input class="input" id="per_user_limit" name="per_user_limit" type="number" min="1" value="<?= e($value('per_user_limit')) ?>" placeholder="Unlimited"></div>
        </div>
        <label class="check"><input type="checkbox" name="dealer_only" value="1" <?= $edit ? ((int) $edit['dealer_only'] === 1 ? 'checked' : '') : '' ?>><span>Dealer accounts only</span></label>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= $edit ? ((int) $edit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Active</span></label>
        <button class="btn btn-primary btn-block mt-2" type="submit"><?= $edit ? 'Save code' : 'Create code' ?></button>
      </form>
    </div>
  </div>
</div>
<?php clear_input(); ?>
