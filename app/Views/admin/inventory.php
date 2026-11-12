<?php
use App\Core\Money;
?>
<div class="admin-head">
  <div><h2>Inventory</h2><p>Adjust stock and review movements. Every change is logged.</p></div>
</div>

<div class="admin-filters">
  <a class="btn btn-sm <?= $filter === 'all' ? 'btn-dark' : 'btn-outline' ?>" href="<?= url('/admin/inventory') ?>">All products</a>
  <a class="btn btn-sm <?= $filter === 'low' ? 'btn-dark' : 'btn-outline' ?>" href="<?= url('/admin/inventory?filter=low') ?>">Low stock</a>
  <a class="btn btn-sm <?= $filter === 'out' ? 'btn-dark' : 'btn-outline' ?>" href="<?= url('/admin/inventory?filter=out') ?>">Out of stock</a>
  <a class="btn btn-sm <?= $filter === 'untracked' ? 'btn-dark' : 'btn-outline' ?>" href="<?= url('/admin/inventory?filter=untracked') ?>">Not tracked</a>
</div>

<div class="admin-card">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Product</th><th>Category</th><th>Tracked</th><th>Stock</th><th>Status</th><th style="width:280px">Adjust</th></tr></thead>
      <tbody>
        <?php foreach ($products as $product): ?>
          <tr>
            <td><a class="bold" href="<?= url('/admin/products/' . (int) $product['id'] . '/edit') ?>"><?= e($product['name']) ?></a><div class="tiny muted"><?= e($product['sku']) ?></div></td>
            <td class="small"><?= e($product['category_name'] ?? '—') ?></td>
            <td class="small"><?= (int) $product['manage_stock'] === 1 ? 'Yes' : 'No' ?></td>
            <td>
              <strong class="<?= (int) $product['manage_stock'] === 1 && (int) $product['stock_qty'] <= (int) $product['low_stock_threshold'] ? 'red' : '' ?>"><?= (int) $product['stock_qty'] ?></strong>
              <?php if ((int) $product['manage_stock'] === 1): ?><div class="tiny muted">low at <?= (int) $product['low_stock_threshold'] ?></div><?php endif; ?>
            </td>
            <td><span class="badge <?= e(match ($product['stock_status']) { 'in_stock' => 'badge-green', 'preorder' => 'badge-amber', default => 'badge' } ) ?>"><?= e(status_label($product['stock_status'])) ?></span></td>
            <td>
              <form action="<?= url('/admin/inventory/adjust') ?>" method="post" class="admin-adjust">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <select class="select" name="mode" aria-label="Adjustment mode for <?= e($product['name']) ?>">
                  <option value="delta">Add / remove</option>
                  <option value="set">Set count</option>
                </select>
                <input class="input" type="number" name="<?= 'delta' ?>" placeholder="±" style="width:70px" aria-label="Change amount">
                <input class="input" type="number" name="quantity" placeholder="Qty" style="width:70px" aria-label="New count">
                <input class="input" type="text" name="reason" placeholder="Reason" style="width:110px" aria-label="Reason">
                <button class="btn btn-xs btn-dark" type="submit">Apply</button>
              </form>
              <span class="tiny muted">Use one field: ± change or new count.</span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($products === []): ?><div class="admin-empty"><p>No products match this filter.</p></div><?php endif; ?>
</div>

<div class="admin-card mt-3">
  <div class="admin-card-head"><div><h3>Recent stock movements</h3><span>Latest 15</span></div></div>
  <?php if ($recent === []): ?>
    <div class="admin-empty"><p>No stock movements recorded yet.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>When</th><th>Product</th><th>Change</th><th>After</th><th>Reason</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $movement): ?>
            <tr>
              <td class="small"><?= e(fmt_datetime($movement['created_at'])) ?></td>
              <td><a class="bold" href="<?= url('/admin/products/' . (int) $movement['product_id'] . '/edit') ?>"><?= e($movement['name']) ?></a><div class="tiny muted"><?= e($movement['sku']) ?></div></td>
              <td class="<?= (int) $movement['delta'] >= 0 ? 'green bold' : 'red bold' ?>"><?= (int) $movement['delta'] > 0 ? '+' : '' ?><?= (int) $movement['delta'] ?></td>
              <td><?= (int) $movement['stock_after'] ?></td>
              <td class="small"><?= e($movement['reason']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
