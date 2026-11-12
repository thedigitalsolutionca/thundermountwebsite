<?php
use App\Core\Money;
?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'My account', 'url' => '/account'], ['label' => 'Orders']]]) ?>
    <div class="account-layout">
      <?= \App\Core\View::partial('storefront/account/sidebar') ?>
      <div>
        <div class="section-head" style="margin-bottom:18px">
          <div>
            <span class="eyebrow">Order history</span>
            <h1 style="font-size:1.9rem;margin:0">Your orders</h1>
          </div>
          <span class="muted small"><?= (int) $paginator->total ?> order<?= (int) $paginator->total === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($orders === []): ?>
          <div class="empty card card-body">
            <?= icon('package') ?>
            <h4>No orders yet</h4>
            <p>Your orders and tracking details will appear here.</p>
            <a class="btn btn-primary btn-sm" href="<?= url('/shop') ?>">Browse products</a>
          </div>
        <?php else: ?>
          <div class="card">
            <div class="table-wrap">
              <table class="data-table">
                <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Status</th><th>Total</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                    <tr>
                      <td>
                        <strong><?= e($order['order_number']) ?></strong>
                        <?php if ($order['tracking_number']): ?><br><span class="tiny muted">Tracking: <?= e($order['tracking_number']) ?></span><?php endif; ?>
                      </td>
                      <td><?= e(fmt_date($order['created_at'])) ?></td>
                      <td><?= (int) \App\Core\Database::value('SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id = ?', [$order['id']]) ?></td>
                      <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
                      <td><?= e(Money::format((float) $order['total'])) ?></td>
                      <td><a class="btn btn-xs btn-outline" href="<?= url('/account/orders/' . urlencode($order['order_number'])) ?>">View</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="mt-3"><?= \App\Core\View::partial('storefront/partials/pagination', ['paginator' => $paginator]) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
