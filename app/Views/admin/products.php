<?php
use App\Core\Money;
?>
<div class="admin-head">
  <div><h2>Products</h2><p><?= (int) $paginator->total ?> product<?= (int) $paginator->total === 1 ? '' : 's' ?> in the catalog.</p></div>
  <a class="btn btn-primary btn-sm" href="<?= url('/admin/products/create') ?>"><?= icon('plus') ?> New product</a>
</div>

<form method="get" action="<?= url('/admin/products') ?>" class="admin-filters">
  <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search name, SKU or model">
  <select class="select" name="category">
    <option value="">All categories</option>
    <?php foreach ($categories as $category): ?>
      <option value="<?= (int) $category['id'] ?>"<?= selected((string) $category['id'], $filters['category']) ?>><?= e($category['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="select" name="status">
    <option value="">Any status</option>
    <option value="active"<?= selected('active', $filters['status']) ?>>Published</option>
    <option value="inactive"<?= selected('inactive', $filters['status']) ?>>Hidden</option>
  </select>
  <select class="select" name="stock">
    <option value="">Any stock</option>
    <option value="low"<?= selected('low', $filters['stock']) ?>>Low stock</option>
    <option value="out"<?= selected('out', $filters['stock']) ?>>Out of stock</option>
  </select>
  <button class="btn btn-dark btn-sm" type="submit">Filter</button>
  <a class="btn btn-ghost btn-sm" href="<?= url('/admin/products') ?>">Reset</a>
</form>

<div class="admin-card">
  <?php if ($products === []): ?>
    <div class="admin-empty"><?= icon('tag') ?><p>No products match these filters.</p><a class="btn btn-outline btn-sm" href="<?= url('/admin/products') ?>">Clear filters</a></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th style="width:60px">Image</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th style="width:150px"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): ?>
            <tr>
              <td><img class="admin-thumb" src="<?= e(upload_url($product['image'])) ?>" alt="" width="48" height="48"></td>
              <td>
                <a class="bold" href="<?= url('/admin/products/' . (int) $product['id'] . '/edit') ?>"><?= e($product['name']) ?></a>
                <div class="tiny muted"><?= e($product['sku']) ?><?= $product['model'] ? ' · ' . e($product['model']) : '' ?></div>
              </td>
              <td class="small"><?= e($product['category_name'] ?? '—') ?></td>
              <td>
                <strong><?= e(Money::format((float) $product['price'])) ?></strong>
                <?php if ((float) $product['sale_price'] > 0): ?><div class="tiny muted">Sale <?= e(Money::format((float) $product['sale_price'])) ?></div><?php endif; ?>
              </td>
              <td>
                <?php if ((int) $product['manage_stock'] === 1): ?>
                  <span class="<?= (int) $product['stock_qty'] <= (int) $product['low_stock_threshold'] ? 'red bold' : '' ?>"><?= (int) $product['stock_qty'] ?></span>
                <?php else: ?>
                  <span class="tiny muted">Not tracked</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int) $product['is_active'] === 1): ?><span class="badge badge-green">Published</span><?php else: ?><span class="badge">Hidden</span><?php endif; ?>
                <?php if ((int) $product['is_featured'] === 1): ?><div class="tiny muted">Featured</div><?php endif; ?>
              </td>
              <td>
                <div class="flex" style="gap:4px;justify-content:flex-end">
                  <a class="btn btn-xs btn-outline" href="<?= url('/product/' . urlencode($product['slug'])) ?>" target="_blank" rel="noopener">View</a>
                  <a class="btn btn-xs btn-outline" href="<?= url('/admin/products/' . (int) $product['id'] . '/edit') ?>">Edit</a>
                  <form action="<?= url('/admin/products/' . (int) $product['id'] . '/toggle') ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="btn btn-xs btn-ghost" type="submit"><?= (int) $product['is_active'] === 1 ? 'Hide' : 'Publish' ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<div class="mt-3"><?= \App\Core\View::partial('storefront/partials/pagination', ['paginator' => $paginator]) ?></div>
