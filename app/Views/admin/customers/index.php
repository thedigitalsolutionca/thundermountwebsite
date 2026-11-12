<?php use App\Core\Money; ?>
<div class="admin-head">
  <div><h2>Customers</h2><p><?= (int) $paginator->total ?> account<?= (int) $paginator->total === 1 ? '' : 's' ?>.</p></div>
</div>
<form method="get" action="<?= url('/admin/customers') ?>" class="admin-filters">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or company">
  <select class="select" name="role">
    <option value="">All roles</option>
    <option value="customer"<?= selected('customer', $role) ?>>Customer</option>
    <option value="dealer"<?= selected('dealer', $role) ?>>Dealer</option>
    <option value="admin"<?= selected('admin', $role) ?>>Admin</option>
  </select>
  <button class="btn btn-dark btn-sm" type="submit">Filter</button>
  <a class="btn btn-ghost btn-sm" href="<?= url('/admin/customers') ?>">Reset</a>
</form>
<div class="admin-card">
  <?php if ($customers === []): ?><div class="admin-empty"><?= icon('users') ?><p>No customers match this filter.</p></div><?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Customer</th><th>Role</th><th>Orders</th><th>Spent</th><th>Status</th><th>Joined</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($customers as $customer): ?>
            <tr>
              <td>
                <a class="bold" href="<?= url('/admin/customers/' . (int) $customer['id']) ?>"><?= e(trim($customer['first_name'] . ' ' . $customer['last_name']) ?: $customer['email']) ?></a>
                <div class="tiny muted"><?= e($customer['email']) ?><?= $customer['company'] ? ' · ' . e($customer['company']) : '' ?></div>
              </td>
              <td><span class="badge <?= $customer['role'] === 'dealer' ? 'badge-blue' : ($customer['role'] === 'admin' ? 'badge-red' : '') ?>"><?= e(ucfirst($customer['role'])) ?></span></td>
              <td><?= (int) $customer['order_count'] ?></td>
              <td><?= e(Money::format((float) $customer['total_spent'])) ?></td>
              <td><?= $customer['status'] === 'active' ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Disabled</span>' ?></td>
              <td class="small muted"><?= e(fmt_date($customer['created_at'])) ?></td>
              <td><a class="btn btn-xs btn-outline" href="<?= url('/admin/customers/' . (int) $customer['id']) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<div class="mt-3"><?= \App\Core\View::partial('storefront/partials/pagination', ['paginator' => $paginator]) ?></div>
