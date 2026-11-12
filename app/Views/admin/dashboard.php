<?php
use App\Core\Money;
?>
<div class="admin-head">
  <div>
    <h2>Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?></h2>
    <p>Here is what is happening in the store today, <?= e(date('F j')) ?>.</p>
  </div>
  <div class="flex" style="gap:8px">
    <a class="btn btn-outline btn-sm" href="<?= url('/admin/products/create') ?>"><?= icon('plus') ?> Add product</a>
    <a class="btn btn-primary btn-sm" href="<?= url('/admin/orders') ?>">View orders</a>
  </div>
</div>

<?php if (!$readiness['ready']): ?>
  <div class="admin-alert">
    <?= icon('bell') ?>
    <div>
      <strong>Checkout is not ready to take orders.</strong>
      <ul><?php foreach ($readiness['issues'] as $issue): ?><li><?= e($issue) ?></li><?php endforeach; ?></ul>
      <a class="btn btn-xs btn-dark" href="<?= url('/admin/settings?tab=paypal') ?>">Review settings</a>
      <a class="btn btn-xs btn-outline" href="<?= url('/admin/shipping') ?>">Shipping</a>
      <a class="btn btn-xs btn-outline" href="<?= url('/admin/tax') ?>">Tax</a>
    </div>
  </div>
<?php endif; ?>

<div class="kpi-grid">
  <div class="kpi">
    <span>Revenue today</span>
    <strong><?= e(Money::format($revenueToday)) ?></strong>
    <small><?= (int) $ordersToday ?> order<?= (int) $ordersToday === 1 ? '' : 's' ?> placed today</small>
  </div>
  <div class="kpi">
    <span>Revenue this month</span>
    <strong><?= e(Money::format($revenueMonth)) ?></strong>
    <small>Paid orders only</small>
  </div>
  <div class="kpi">
    <span>Open orders</span>
    <strong><?= (int) $openOrders ?></strong>
    <small><?= (int) $awaitingPayment ?> awaiting payment</small>
  </div>
  <div class="kpi">
    <span>Customers</span>
    <strong><?= (int) $customers ?></strong>
    <small><?= (int) $pendingDealers ?> dealer application<?= (int) $pendingDealers === 1 ? '' : 's' ?> pending</small>
  </div>
</div>

<div class="admin-grid-2">
  <div class="admin-card">
    <div class="admin-card-head">
      <div><h3>Sales — last 14 days</h3><span>Paid order totals</span></div>
      <a class="small bold" href="<?= url('/admin/orders') ?>">All orders</a>
    </div>
    <div class="chart">
      <?php foreach ($salesSeries as $point): ?>
        <div class="bar-wrap" title="<?= e($point['label']) ?>: <?= e(Money::format($point['total'])) ?>">
          <div class="bar" style="height:<?= max(2, (int) round(($point['total'] / $salesMax) * 100)) ?>%"></div>
          <span><?= e($point['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><div><h3>Needs attention</h3><span>Quick queue</span></div></div>
    <div class="queue">
      <a href="<?= url('/admin/orders?status=paid') ?>"><span><?= icon('package') ?> Orders to process</span><strong><?= (int) $openOrders ?></strong></a>
      <a href="<?= url('/admin/dealers?status=pending') ?>"><span><?= icon('users') ?> Dealer applications</span><strong><?= (int) $pendingDealers ?></strong></a>
      <a href="<?= url('/admin/messages') ?>"><span><?= icon('mail') ?> New messages</span><strong><?= (int) $newMessages ?></strong></a>
      <a href="<?= url('/admin/reviews') ?>"><span><?= icon('star') ?> Reviews to moderate</span><strong><?= (int) $pendingReviews ?></strong></a>
      <a href="<?= url('/admin/inventory') ?>"><span><?= icon('box') ?> Low-stock products</span><strong><?= count($lowStock) ?></strong></a>
    </div>
  </div>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.5fr 1fr">
  <div class="admin-card">
    <div class="admin-card-head">
      <div><h3>Recent orders</h3><span>Newest first</span></div>
      <a class="small bold" href="<?= url('/admin/orders') ?>">View all</a>
    </div>
    <?php if ($recentOrders === []): ?>
      <div class="admin-empty"><?= icon('package') ?><p>No orders yet. They will appear here.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
              <tr>
                <td><a href="<?= url('/admin/orders/' . urlencode($order['order_number'])) ?>" class="bold"><?= e($order['order_number']) ?></a><div class="tiny muted"><?= e(fmt_datetime($order['created_at'])) ?></div></td>
                <td><?= e($order['email']) ?></td>
                <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
                <td><?= e(Money::format((float) $order['total'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Top sellers</h3><span>All time, paid orders</span></div></div>
      <?php if ($topProducts === []): ?>
        <div class="admin-empty"><p>Sales data appears after the first paid order.</p></div>
      <?php else: ?>
        <div class="mini-list">
          <?php foreach ($topProducts as $product): ?>
            <div><span><?= e($product['name']) ?><small><?= (int) $product['units'] ?> units</small></span><strong><?= e(Money::format((float) $product['revenue'])) ?></strong></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Low stock</h3><span>At or below threshold</span></div><a class="small bold" href="<?= url('/admin/inventory') ?>">Manage</a></div>
      <?php if ($lowStock === []): ?>
        <div class="admin-empty"><p>All tracked products are above their threshold.</p></div>
      <?php else: ?>
        <div class="mini-list">
          <?php foreach ($lowStock as $item): ?>
            <div><span><?= e($item['name']) ?><small><?= e($item['sku']) ?></small></span><strong class="<?= (int) $item['stock_qty'] <= 0 ? 'red' : '' ?>"><?= (int) $item['stock_qty'] ?> left</strong></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
