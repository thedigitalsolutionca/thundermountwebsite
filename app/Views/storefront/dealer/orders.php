<?php use App\Core\Money; ?>
<div class="section-tight">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Dealer portal', 'url' => '/dealer/account'], ['label' => 'Orders']]]) ?>
    <div class="section-head" style="margin-bottom:18px">
      <div><span class="eyebrow"><?= e($dealer['company']) ?></span><h1 style="font-size:1.9rem;margin:0">Trade orders</h1></div>
      <a class="btn btn-outline btn-sm" href="<?= url('/dealer/account') ?>">Back to portal</a>
    </div>
    <?php if ($orders === []): ?>
      <div class="empty card card-body"><?= icon('package') ?><h4>No trade orders yet</h4><p>Dealer orders will appear here once you place one.</p><a class="btn btn-primary btn-sm" href="<?= url('/shop') ?>">Shop products</a></div>
    <?php else: ?>
      <div class="card">
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Order</th><th>Date</th><th>Payment</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><strong><?= e($order['order_number']) ?></strong><?php if ($order['tracking_number']): ?><div class="tiny muted"><?= e($order['tracking_number']) ?></div><?php endif; ?></td>
                  <td><?= e(fmt_date($order['created_at'])) ?></td>
                  <td><span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span></td>
                  <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
                  <td><?= e(Money::format((float) $order['total'])) ?></td>
                  <td><a class="btn btn-xs btn-outline" href="<?= url('/account/orders/' . urlencode($order['order_number'])) ?>">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
