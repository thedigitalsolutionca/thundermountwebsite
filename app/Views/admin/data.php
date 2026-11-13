<div class="admin-head"><div><h2>Data management</h2><p>Review records before deleting. Nothing is removed by loading this page.</p></div><a class="btn btn-outline btn-sm" href="<?= url('/admin/orders/export') ?>">Export orders first</a></div>
<div class="admin-alert"><?= icon('shield') ?><div><strong>Permanent deletion cannot be undone.</strong><p>Back up your database before cleanup. Paid orders, payment references, inventory audit trails, webhook deduplication records, current security logs, admins, and dealer accounts are protected here. Existing catalog, discounts, pages, shipping, and tax editors retain their own delete controls.</p></div></div>
<?php if ($error !== ''): ?><div class="admin-alert" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="get" action="<?= url('/admin/data') ?>" class="admin-filters">
  <div class="field"><label for="data-type">Data type</label><select class="select" id="data-type" name="type"><?php foreach ($types as $key => $definition): ?><option value="<?= e($key) ?>"<?= selected($key, $type) ?>><?= e($definition['label']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="data-before">Created before (exclusive)</label><input class="input" type="date" id="data-before" name="before" required max="<?= date('Y-m-d', strtotime('tomorrow')) ?>" value="<?= e($before) ?>"></div>
  <button class="btn btn-dark btn-sm" type="submit">Preview eligible records</button>
</form>
<div class="admin-card">
  <div class="admin-card-head"><div><h3><?= e($types[$type]['label']) ?></h3><span><?= (int) $preview['count'] ?> eligible · page <?= (int) $preview['page'] ?> of <?= (int) $preview['pages'] ?></span></div></div>
  <div class="admin-card-body"><p class="small muted"><?= e($types[$type]['help']) ?></p><p class="tiny muted">Dates use the store timezone. Showing up to 50 records per page. Only checked records will be deleted; no hidden “delete everything” action is available.</p></div>
  <?php if ($preview['rows'] === []): ?><div class="admin-empty"><p>No eligible records match this cutoff or selected ID. Protected records cannot be deleted here.</p></div>
  <?php else: ?>
  <form action="<?= url('/admin/data/delete') ?>" method="post" data-confirm="Permanently delete the selected records? This cannot be undone.">
    <?= csrf_field() ?><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="before" value="<?= e($before) ?>">
    <div class="table-wrap"><table class="data-table"><thead><tr><th><label><input type="checkbox" data-select-records aria-label="Select all visible records"> Select</label></th><th>ID</th><th>Record</th><th>Created</th></tr></thead><tbody>
    <?php foreach ($preview['rows'] as $record): ?><tr><td><input type="checkbox" name="ids[]" value="<?= (int) $record['id'] ?>" aria-label="Select record <?= (int) $record['id'] ?>"></td><td>#<?= (int) $record['id'] ?></td><td><?= e(mb_substr($record['label'], 0, 160)) ?></td><td><?= e(fmt_datetime($record['created_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="admin-card-body cleanup-confirm">
      <p class="small"><strong>Final confirmation</strong> · Select records above, type DELETE, and enter your current admin password.</p>
      <div class="form-row"><div class="field"><label for="cleanup-confirm">Type DELETE</label><input class="input" id="cleanup-confirm" name="confirmation" required pattern="DELETE" autocomplete="off" placeholder="DELETE"></div><div class="field"><label for="cleanup-password">Current administrator password</label><input class="input" id="cleanup-password" name="password" type="password" required autocomplete="current-password"></div></div>
      <button class="btn btn-primary" type="submit">Permanently delete selected records</button>
    </div>
  </form>
  <?php endif; ?>
</div>
<div class="flex wrap mt-3">
  <?php if ($preview['page'] > 1): ?><a class="btn btn-outline btn-sm" href="<?= url('/admin/data?' . http_build_query(['type' => $type, 'before' => $before, 'page' => $preview['page'] - 1, 'record' => \App\Core\Request::int('record')])) ?>">Previous</a><?php endif; ?>
  <?php if ($preview['page'] < $preview['pages']): ?><a class="btn btn-outline btn-sm" href="<?= url('/admin/data?' . http_build_query(['type' => $type, 'before' => $before, 'page' => $preview['page'] + 1, 'record' => \App\Core\Request::int('record')])) ?>">Next</a><?php endif; ?>
</div>
