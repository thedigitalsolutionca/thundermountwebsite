<?php use App\Core\Money; ?>
<div class="admin-head">
  <div><h2>Dealers</h2><p>Review applications, assign pricing tiers and manage trade accounts.</p></div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/dealers/tiers') ?>"><?= icon('tag') ?> Manage tiers</a>
</div>

<div class="admin-tabs">
  <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= url('/admin/dealers') ?>">All</a>
  <a class="<?= $status === 'pending' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=pending') ?>">Pending <?php if (!empty($counts['pending'])): ?><span><?= (int) $counts['pending'] ?></span><?php endif; ?></a>
  <a class="<?= $status === 'approved' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=approved') ?>">Approved <?php if (!empty($counts['approved'])): ?><span><?= (int) $counts['approved'] ?></span><?php endif; ?></a>
  <a class="<?= $status === 'rejected' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=rejected') ?>">Not approved</a>
</div>

<div class="admin-card">
  <?php if ($dealers === []): ?>
    <div class="admin-empty"><?= icon('users') ?><p>No dealer applications in this view.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Company</th><th>Contact</th><th>Business type</th><th>Tier</th><th>Applied</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($dealers as $dealer): ?>
            <tr>
              <td><a class="bold" href="<?= url('/admin/dealers/' . (int) $dealer['id']) ?>"><?= e($dealer['company']) ?></a><div class="tiny muted">#<?= (int) $dealer['id'] ?> · <?= e($dealer['city']) ?>, <?= e($dealer['state']) ?></div></td>
              <td class="small"><?= e($dealer['contact_name']) ?><div class="tiny muted"><?= e($dealer['email']) ?></div></td>
              <td class="small"><?= e($dealer['business_type']) ?></td>
              <td class="small"><?= $dealer['tier_name'] ? e($dealer['tier_name']) . ' (' . e(rtrim(rtrim(number_format((float) $dealer['discount_percent'], 2, '.', ''), '0'), '.')) . '%)' : '—' ?></td>
              <td class="small muted"><?= e(fmt_date($dealer['applied_at'])) ?></td>
              <td><span class="badge <?= $dealer['status'] === 'approved' ? 'badge-green' : ($dealer['status'] === 'rejected' ? 'badge-red' : 'badge-amber') ?>"><?= e(ucfirst($dealer['status'])) ?></span></td>
              <td><a class="btn btn-xs btn-outline" href="<?= url('/admin/dealers/' . (int) $dealer['id']) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
