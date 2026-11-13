<?php
use App\Core\Money;
$programOn = dealer_program_enabled();
$pagesOn = dealer_pages_enabled();
$appsOn = dealer_applications_enabled();
$pricingOn = dealer_pricing_enabled();
?>
<div class="admin-head">
  <div><h2>Dealers</h2><p>Control the dealer program, review applications, assign tiers and manage trade accounts.</p></div>
  <div class="flex wrap" style="gap:8px">
    <a class="btn btn-outline btn-sm" href="<?= url('/admin/settings?tab=dealer') ?>"><?= icon('settings') ?> Program settings</a>
    <a class="btn btn-outline btn-sm" href="<?= url('/admin/dealers/tiers') ?>"><?= icon('tag') ?> Manage tiers</a>
  </div>
</div>

<div class="admin-card dealer-status-card <?= $programOn ? 'is-open' : 'is-closed' ?>" style="margin-bottom:16px">
  <div class="admin-card-body">
    <div class="flex between wrap" style="gap:16px;align-items:center">
      <div>
        <div class="flex wrap" style="gap:8px;align-items:center;margin-bottom:4px">
          <span class="badge <?= $programOn ? 'badge-green' : 'badge-red' ?>"><?= $programOn ? 'Program open' : 'Program closed' ?></span>
          <?php if ($programOn): ?>
            <span class="badge <?= $pagesOn ? 'badge-blue' : 'badge-gray' ?>">Pages: <?= $pagesOn ? 'Visible' : 'Hidden' ?></span>
            <span class="badge <?= $appsOn ? 'badge-blue' : 'badge-gray' ?>">Applications: <?= $appsOn ? 'Open' : 'Closed' ?></span>
            <span class="badge <?= $pricingOn ? 'badge-blue' : 'badge-gray' ?>">Trade pricing: <?= $pricingOn ? 'Active' : 'Paused' ?></span>
          <?php endif; ?>
        </div>
        <p class="small muted" style="margin:0">
          <?php if ($programOn): ?>
            Approved dealers receive assigned tier pricing while signed in. You can pause applications, hide dealer pages, or close the entire dealer program at any time without losing order history.
          <?php else: ?>
            The dealer program is currently turned off across the storefront. All dealer pages, navigation links, applications, dealer pricing and dealer-only coupons are disabled.
          <?php endif; ?>
        </p>
      </div>
      <div class="flex wrap" style="gap:8px">
        <form action="<?= url('/admin/dealers/program-toggle') ?>" method="post" <?= $programOn ? 'data-confirm="Close the entire dealer program? All public dealer pages, links, applications and trade pricing will be turned off until you reopen it."' : '' ?>>
          <?= csrf_field() ?>
          <input type="hidden" name="enable" value="<?= $programOn ? '0' : '1' ?>">
          <input type="hidden" name="return_to" value="/admin/dealers">
          <button class="btn btn-sm <?= $programOn ? 'btn-danger' : 'btn-primary' ?>" type="submit">
            <?= $programOn ? 'Close dealer program' : 'Reopen dealer program' ?>
          </button>
        </form>
        <?php if (!empty($counts['approved'])): ?>
          <form action="<?= url('/admin/dealers/deactivate-all') ?>" method="post" data-confirm="Deactivate all <?= (int) $counts['approved'] ?> approved dealer account(s)? Their tier assignments and past orders are preserved so you can reactivate individuals later.">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="/admin/dealers">
            <button class="btn btn-sm btn-outline" type="submit">Deactivate all approved (<?= (int) $counts['approved'] ?>)</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="admin-tabs">
  <a class="<?= $status === '' ? 'active' : '' ?>" href="<?= url('/admin/dealers') ?>">All <span><?= (int) array_sum($counts) ?></span></a>
  <a class="<?= $status === 'pending' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=pending') ?>">Pending <?php if (!empty($counts['pending'])): ?><span><?= (int) $counts['pending'] ?></span><?php endif; ?></a>
  <a class="<?= $status === 'approved' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=approved') ?>">Approved <?php if (!empty($counts['approved'])): ?><span><?= (int) $counts['approved'] ?></span><?php endif; ?></a>
  <a class="<?= $status === 'inactive' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=inactive') ?>">Inactive <?php if (!empty($counts['inactive'])): ?><span><?= (int) $counts['inactive'] ?></span><?php endif; ?></a>
  <a class="<?= $status === 'rejected' ? 'active' : '' ?>" href="<?= url('/admin/dealers?status=rejected') ?>">Not approved <?php if (!empty($counts['rejected'])): ?><span><?= (int) $counts['rejected'] ?></span><?php endif; ?></a>
</div>

<form method="get" action="<?= url('/admin/dealers') ?>" class="admin-filters">
  <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <input class="input" type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search company, contact or email">
  <button class="btn btn-dark btn-sm" type="submit">Search</button>
  <?php if (!empty($q) || $status !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= url('/admin/dealers') ?>">Reset</a><?php endif; ?>
</form>

<div class="admin-card">
  <?php if ($dealers === []): ?>
    <div class="admin-empty"><?= icon('users') ?><p>No dealer records match this view.</p></div>
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
              <td><span class="badge <?= e(badge_class($dealer['status'])) ?>"><?= e(ucfirst($dealer['status'])) ?></span></td>
              <td>
                <div class="flex" style="gap:4px;justify-content:flex-end">
                  <?php if ($dealer['status'] === 'approved'): ?>
                    <form action="<?= url('/admin/dealers/' . (int) $dealer['id'] . '/deactivate') ?>" method="post" data-confirm="Deactivate <?= e($dealer['company']) ?>'s dealer pricing? Their account and order history will be preserved.">
                      <?= csrf_field() ?>
                      <button class="btn btn-xs btn-ghost" type="submit">Deactivate</button>
                    </form>
                  <?php elseif ($dealer['status'] === 'inactive'): ?>
                    <form action="<?= url('/admin/dealers/' . (int) $dealer['id'] . '/reactivate') ?>" method="post">
                      <?= csrf_field() ?>
                      <button class="btn btn-xs btn-outline" type="submit">Reactivate</button>
                    </form>
                  <?php endif; ?>
                  <a class="btn btn-xs btn-outline" href="<?= url('/admin/dealers/' . (int) $dealer['id']) ?>">Open</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
