<?php
use App\Core\Money;
$user = auth();
?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'My account']]]) ?>
    <div class="account-layout">
      <?= \App\Core\View::partial('storefront/account/sidebar') ?>
      <div>
        <div class="section-head" style="margin-bottom:18px">
          <div>
            <span class="eyebrow">Account overview</span>
            <h1 style="font-size:1.9rem;margin-bottom:4px">Hello, <?= e($user['first_name'] ?: 'there') ?>.</h1>
            <p class="muted" style="margin:0">Track orders, manage addresses and keep your account details current.</p>
          </div>
          <a class="btn btn-primary" href="<?= url('/shop') ?>">Shop products</a>
        </div>

        <?php if (dealer_program_enabled()): ?>
          <?php if ($dealer && $dealer['status'] === 'pending'): ?>
            <div class="notice notice-warn">
              <?= icon('clock') ?>
              <div><strong>Dealer application under review.</strong> We will email you when your trade account is approved. <a href="<?= url('/dealer/account') ?>" class="bold">View application</a></div>
            </div>
          <?php elseif ($dealer && $dealer['status'] === 'approved' && dealer_pricing_enabled()): ?>
            <div class="notice notice-success">
              <?= icon('check') ?>
              <div><strong>Approved dealer.</strong> Your dealer pricing is applied automatically while signed in. <a href="<?= url('/shop') ?>" class="bold">Shop with dealer pricing</a></div>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="stat-grid">
          <div class="card card-body">
            <span class="small muted">Orders placed</span>
            <strong style="font-size:1.7rem"><?= (int) $orderCount ?></strong>
            <a class="small bold" href="<?= url('/account/orders') ?>">View orders →</a>
          </div>
          <div class="card card-body">
            <span class="small muted">Total spent</span>
            <strong style="font-size:1.7rem"><?= e(Money::format($totalSpent)) ?></strong>
            <span class="small muted">Paid orders</span>
          </div>
          <div class="card card-body">
            <span class="small muted">Saved addresses</span>
            <strong style="font-size:1.7rem"><?= count($addresses) ?></strong>
            <a class="small bold" href="<?= url('/account/addresses') ?>">Manage →</a>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-head">
            <h2 style="font-size:1.15rem;margin:0">Recent orders</h2>
            <a class="small bold" href="<?= url('/account/orders') ?>">All orders</a>
          </div>
          <?php if ($orders === []): ?>
            <div class="empty">
              <?= icon('package') ?>
              <h4>No orders yet</h4>
              <p>When you place an order it will appear here with tracking details.</p>
              <a class="btn btn-primary btn-sm" href="<?= url('/shop') ?>">Browse products</a>
            </div>
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

        <div class="grid grid-2">
          <div class="card card-body">
            <h3 style="margin-top:0;font-size:1rem">Contact details</h3>
            <p class="small" style="margin:0 0 4px"><strong><?= e(trim($user['first_name'] . ' ' . $user['last_name'])) ?></strong></p>
            <p class="small muted" style="margin:0 0 4px"><?= e($user['email']) ?></p>
            <p class="small muted" style="margin:0 0 12px"><?= e($user['phone'] ?: 'No phone on file') ?></p>
            <a class="btn btn-outline btn-sm" href="<?= url('/account/profile') ?>">Edit details</a>
          </div>
          <div class="card card-body">
            <h3 style="margin-top:0;font-size:1rem">Default delivery address</h3>
            <?php $default = $addresses[0] ?? null; ?>
            <?php if ($default): ?>
              <p class="small muted" style="white-space:pre-line;margin:0 0 12px"><?= e(trim($default['address1'] . "\n" . ($default['address2'] ? $default['address2'] . "\n" : '') . $default['city'] . ', ' . $default['state'] . ' ' . $default['postcode'])) ?></p>
            <?php else: ?>
              <p class="small muted">No address saved yet.</p>
            <?php endif; ?>
            <a class="btn btn-outline btn-sm" href="<?= url('/account/addresses') ?>">Manage addresses</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
