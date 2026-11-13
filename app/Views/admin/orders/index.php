<?php
use App\Core\Money;
?>
<div class="admin-head">
  <div><h2>Orders</h2><p><?= (int) $paginator->total ?> order<?= (int) $paginator->total === 1 ? '' : 's' ?> match this view.</p></div>
  <a class="btn btn-ghost btn-sm" href="<?= url('/admin/data?type=orders') ?>">Clean up eligible cancelled orders</a>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/orders/export' . query_string()) ?>"><?= icon('download') ?> Export CSV</a>
</div>

<div class="admin-tabs">
  <a class="<?= $filters['status'] === '' ? 'active' : '' ?>" href="<?= url('/admin/orders') ?>">All</a>
  <?php foreach (['pending' => 'Pending', 'paid' => 'Paid', 'processing' => 'Processing', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $key => $label): ?>
    <a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= url('/admin/orders?status=' . $key) ?>"><?= e($label) ?><?php if (!empty($counts[$key])): ?> <span><?= (int) $counts[$key] ?></span><?php endif; ?></a>
  <?php endforeach; ?>
</div>

<form method="get" action="<?= url('/admin/orders') ?>" class="admin-filters">
  <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
  <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Order number, email or note">
  <select class="select" name="payment_status">
    <option value="">Any payment</option>
    <?php foreach (['unpaid' => 'Unpaid', 'paid' => 'Paid', 'partially_refunded' => 'Partially refunded', 'refunded' => 'Refunded', 'failed' => 'Failed'] as $key => $label): ?>
      <option value="<?= $key ?>"<?= selected($key, $filters['payment_status']) ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="small muted" for="from">From</label><input class="input" id="from" type="date" name="from" value="<?= e($filters['from']) ?>">
  <label class="small muted" for="to">To</label><input class="input" id="to" type="date" name="to" value="<?= e($filters['to']) ?>">
  <button class="btn btn-dark btn-sm" type="submit">Filter</button>
  <a class="btn btn-ghost btn-sm" href="<?= url('/admin/orders') ?>">Reset</a>
</form>

<div class="admin-card">
  <?php if ($orders === []): ?>
    <div class="admin-empty"><?= icon('package') ?><p>No orders match these filters.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Payment</th><th>Status</th><th>Total</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td><a class="bold" href="<?= url('/admin/orders/' . urlencode($order['order_number'])) ?>"><?= e($order['order_number']) ?></a><?php if ($order['tracking_number']): ?><div class="tiny muted"><?= e($order['tracking_carrier']) ?> <?= e($order['tracking_number']) ?></div><?php endif; ?></td>
              <td><span class="small"><?= e($order['email']) ?></span></td>
              <td class="small"><?= e(fmt_datetime($order['created_at'])) ?></td>
              <td><span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span></td>
              <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
              <td><strong><?= e(Money::format((float) $order['total'])) ?></strong></td>
              <td><a class="btn btn-xs btn-outline" href="<?= url('/admin/orders/' . urlencode($order['order_number'])) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<div class="mt-3"><?= \App\Core\View::partial('storefront/partials/pagination', ['paginator' => $paginator]) ?></div>
