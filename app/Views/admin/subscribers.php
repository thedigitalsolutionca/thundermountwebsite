<?php use App\Core\Csrf; ?>
<div class="admin-head">
  <div><h2>Newsletter</h2><p><?= (int) $total ?> active subscriber<?= (int) $total === 1 ? '' : 's' ?>.</p></div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/subscribers/export') ?>"><?= icon('download') ?> Export CSV</a>
</div>
<div class="admin-card">
  <?php if ($subscribers === []): ?>
    <div class="admin-empty"><?= icon('mail') ?><p>No subscribers yet. The footer signup form adds them here.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Email</th><th>Status</th><th>Subscribed</th></tr></thead>
        <tbody>
          <?php foreach ($subscribers as $subscriber): ?>
            <tr>
              <td><?= e($subscriber['email']) ?></td>
              <td><span class="badge <?= $subscriber['status'] === 'subscribed' ? 'badge-green' : '' ?>"><?= e(ucfirst($subscriber['status'])) ?></span></td>
              <td class="small muted"><?= e(fmt_datetime($subscriber['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
