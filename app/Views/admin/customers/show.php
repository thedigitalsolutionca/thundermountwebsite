<?php
use App\Core\Csrf;
use App\Core\Money;
?>
<div class="admin-head">
  <div>
    <h2><?= e(trim($customer['first_name'] . ' ' . $customer['last_name']) ?: $customer['email']) ?></h2>
    <p><?= e($customer['email']) ?> · <?= e(ucfirst($customer['role'])) ?> · joined <?= e(fmt_date($customer['created_at'])) ?></p>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/customers') ?>">Back</a>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.6fr 1fr;align-items:start">
  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Orders</h3><span><?= count($orders) ?> shown</span></div></div>
      <?php if ($orders === []): ?>
        <div class="admin-empty"><p>This customer has not placed an order yet.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Payment</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><a class="bold" href="<?= url('/admin/orders/' . urlencode($order['order_number'])) ?>"><?= e($order['order_number']) ?></a></td>
                  <td class="small"><?= e(fmt_date($order['created_at'])) ?></td>
                  <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
                  <td><span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span></td>
                  <td><?= e(Money::format((float) $order['total'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Addresses</h3></div></div>
      <div class="admin-card-body">
        <?php if ($addresses === []): ?><p class="small muted">No saved addresses.</p><?php endif; ?>
        <div class="grid grid-2">
          <?php foreach ($addresses as $address): ?>
            <div class="admin-note">
              <strong class="small"><?= e($address['label']) ?></strong>
              <p class="small muted" style="white-space:pre-line;margin:4px 0 0"><?= e(trim($address['address1'] . "\n" . ($address['address2'] ? $address['address2'] . "\n" : '') . $address['city'] . ', ' . $address['state'] . ' ' . $address['postcode'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Account</h3></div><span class="badge <?= $customer['status'] === 'active' ? 'badge-green' : 'badge-red' ?>"><?= e(ucfirst($customer['status'])) ?></span></div>
      <div class="admin-card-body small">
        <p style="margin:0 0 4px"><strong>Email:</strong> <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></p>
        <p style="margin:0 0 4px"><strong>Phone:</strong> <?= e($customer['phone'] ?: '—') ?></p>
        <p style="margin:0 0 4px"><strong>Company:</strong> <?= e($customer['company'] ?: '—') ?></p>
        <p style="margin:0 0 4px"><strong>Lifetime spend:</strong> <?= e(Money::format($spent)) ?></p>
        <p style="margin:0 0 10px"><strong>Last sign-in:</strong> <?= $customer['last_login_at'] ? e(fmt_datetime($customer['last_login_at'])) : 'Never' ?></p>
        <?php if ((int) $customer['id'] !== (int) auth()['id']): ?>
          <form action="<?= url('/admin/customers/' . (int) $customer['id'] . '/toggle') ?>" method="post" data-confirm="Change this account's access?">
            <?= csrf_field() ?>
            <button class="btn btn-sm <?= $customer['status'] === 'active' ? 'btn-danger' : 'btn-dark' ?>" type="submit"><?= $customer['status'] === 'active' ? 'Disable account' : 'Enable account' ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Dealer account</h3></div></div>
      <div class="admin-card-body small">
        <?php if (!$dealer): ?>
          <p class="muted">No dealer application on file.</p>
          <a class="btn btn-outline btn-sm" href="<?= url('/admin/dealers') ?>">View dealer applications</a>
        <?php else: ?>
          <p style="margin:0 0 4px"><strong><?= e($dealer['company']) ?></strong></p>
          <p style="margin:0 0 4px">Status: <span class="badge <?= e(badge_class($dealer['status'])) ?>"><?= e(ucfirst($dealer['status'])) ?></span></p>
          <?php if (!empty($dealer['tier'])): ?><p style="margin:0 0 4px">Tier: <?= e($dealer['tier']['name']) ?> (<?= e(rtrim(rtrim(number_format((float) $dealer['tier']['discount_percent'], 2, '.', ''), '0'), '.')) ?>%)</p><?php endif; ?>
          <a class="btn btn-outline btn-sm" href="<?= url('/admin/dealers/' . (int) $dealer['id']) ?>">Open dealer record</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
