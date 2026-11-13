<?php
use App\Core\Money;
$status = $dealer['status'];
$tier = $dealer['tier'] ?? null;
?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Dealer portal']]]) ?>
    <div class="section-head" style="margin-bottom:18px">
      <div>
        <span class="eyebrow">Dealer portal</span>
        <h1 style="font-size:1.9rem;margin-bottom:4px"><?= e($dealer['company']) ?></h1>
        <p class="muted" style="margin:0">Account #<?= (int) $dealer['id'] ?> · Applied <?= e(fmt_date($dealer['applied_at'])) ?></p>
      </div>
      <span class="badge <?= e(badge_class($status)) ?>" style="font-size:.8rem;padding:8px 12px"><?= e(status_label($status)) ?></span>
    </div>

    <?php if ($status === 'pending'): ?>
      <div class="notice notice-warn">
        <?= icon('clock') ?>
        <div>
          <strong>Application received — under review.</strong>
          We review dealer applications during business hours (Mon–Fri 9–4, Sat 10–2). You will get an email at <strong><?= e($dealer['email']) ?></strong> once approved.
          Questions? Call <a href="tel:8053386680" class="bold">805-338-6680</a>.
        </div>
      </div>
    <?php elseif ($status === 'inactive'): ?>
      <div class="notice notice-info">
        <?= icon('bell') ?>
        <div>
          <strong>Dealer trade account inactive.</strong>
          Your dealer trade pricing is currently inactive, though your past order history and standard customer account remain available.
          To reactivate your trade account, call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.
        </div>
      </div>
    <?php elseif ($status === 'rejected'): ?>
      <div class="notice notice-error">
        <?= icon('bell') ?>
        <div>
          <strong>Application not approved.</strong>
          <?= $dealer['admin_notes'] ? e($dealer['admin_notes']) : 'Contact us if you believe this is a mistake or want to supply more information.' ?>
          Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.
        </div>
      </div>
    <?php elseif (!dealer_pricing_enabled()): ?>
      <div class="notice notice-info">
        <?= icon('bell') ?>
        <div><strong>Trade pricing paused.</strong> Standard store pricing is currently active. Contact us if you need a custom trade quote.</div>
      </div>
    <?php else: ?>
      <div class="notice notice-success">
        <?= icon('check') ?>
        <div><strong>Approved dealer.</strong> Your pricing is applied automatically while signed in. <a href="<?= url('/shop') ?>" class="bold">Shop with dealer pricing →</a></div>
      </div>
    <?php endif; ?>

    <div class="stat-grid" style="margin:20px 0">
      <div class="card card-body">
        <span class="small muted">Pricing tier</span>
        <strong style="font-size:1.4rem"><?= $tier ? e($tier['name']) : ($status === 'approved' ? 'Assigned by Thunder Mounts' : ($status === 'inactive' ? 'Inactive' : 'Pending approval')) ?></strong>
        <?php if ($tier && $status === 'approved'): ?><span class="small muted"><?= e(rtrim(rtrim(number_format((float) $tier['discount_percent'], 2, '.', ''), '0'), '.')) ?>% trade discount</span><?php endif; ?>
      </div>
      <div class="card card-body">
        <span class="small muted">Trade orders</span>
        <strong style="font-size:1.4rem"><?= (int) $orderCount ?></strong>
        <a class="small bold" href="<?= url('/dealer/orders') ?>">View orders →</a>
      </div>
      <div class="card card-body">
        <span class="small muted">Total spent</span>
        <strong style="font-size:1.4rem"><?= e(Money::format($totalSpent)) ?></strong>
        <span class="small muted">Paid orders</span>
      </div>
    </div>

    <div class="dealer-layout">
      <div class="card">
        <div class="card-head"><h2 style="font-size:1.05rem;margin:0">Recent dealer orders</h2><a class="small bold" href="<?= url('/dealer/orders') ?>">All orders</a></div>
        <?php if (!in_array($status, ['approved', 'inactive'], true)): ?>
          <div class="empty"><?= icon('lock') ?><h4>Ordering unlocks on approval</h4><p class="small">Once approved, your tier pricing and trade order history appear here.</p></div>
        <?php elseif ($orders === []): ?>
          <div class="empty"><?= icon('package') ?><h4>No trade orders yet</h4><p class="small">Your orders will appear here after your first dealer purchase.</p><a class="btn btn-primary btn-sm" href="<?= url('/shop') ?>">Shop products</a></div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td><strong><?= e($order['order_number']) ?></strong></td>
                    <td><?= e(fmt_date($order['created_at'])) ?></td>
                    <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
                    <td><?= e(Money::format((float) $order['total'])) ?></td>
                    <td><a class="btn btn-xs btn-outline" href="<?= url('/account/orders/' . urlencode($order['order_number'])) ?>">View</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="stack">
        <div class="card card-body">
          <h2 style="font-size:1rem;margin-top:0">Business details</h2>
          <p class="small" style="margin:0 0 2px"><strong><?= e($dealer['contact_name']) ?></strong></p>
          <p class="small muted" style="margin:0 0 2px"><?= e($dealer['email']) ?></p>
          <p class="small muted" style="margin:0 0 8px"><?= e($dealer['phone']) ?></p>
          <p class="small muted" style="white-space:pre-line;margin:0 0 10px"><?= e(trim($dealer['address1'] . "\n" . ($dealer['address2'] ? $dealer['address2'] . "\n" : '') . $dealer['city'] . ', ' . $dealer['state'] . ' ' . $dealer['postcode'])) ?></p>
          <?php if ($dealer['resale_number']): ?><p class="tiny muted" style="margin:0 0 6px">Resale #: <?= e($dealer['resale_number']) ?></p><?php endif; ?>
          <?php if ($dealer['document_path']): ?><p class="small" style="margin:0"><a href="<?= e(upload_url($dealer['document_path'])) ?>" target="_blank" rel="noopener" class="bold">View uploaded document</a></p><?php endif; ?>
          <a class="btn btn-outline btn-sm mt-2" href="<?= url('/account/profile') ?>">Edit account details</a>
        </div>
        <div class="card card-body">
          <h2 style="font-size:1rem;margin-top:0">Dealer support</h2>
          <p class="small muted">Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>. Give us your account number <?= (int) $dealer['id'] ?> and we will pull up your file.</p>
        </div>
      </div>
    </div>
  </div>
</div>
