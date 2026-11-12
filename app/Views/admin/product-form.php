<?php
use App\Core\Csrf;
use App\Core\Money;
$errs = errors();
$isEdit = $product !== null;
$value = static fn (string $key, string $default = '') => old($key, $product[$key] ?? $default);
$specsText = '';
if ($isEdit) {
    $decoded = json_decode((string) $product['specs'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $label => $val) {
            $specsText .= $label . ': ' . $val . "\n";
        }
    }
}
$boxText = '';
if ($isEdit) {
    $decoded = json_decode((string) $product['box_contents'], true);
    if (is_array($decoded)) {
        $boxText = implode("\n", $decoded);
    }
}
?>
<div class="admin-head">
  <div>
    <h2><?= $isEdit ? 'Edit product' : 'New product' ?></h2>
    <p><?= $isEdit ? e($product['name']) . ' · ' . e($product['sku']) : 'Add a product to the catalog.' ?></p>
  </div>
  <div class="flex" style="gap:8px">
    <?php if ($isEdit): ?><a class="btn btn-outline btn-sm" href="<?= url('/product/' . urlencode($product['slug'])) ?>" target="_blank" rel="noopener"><?= icon('external') ?> View</a><?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/products') ?>">Back to products</a>
  </div>
</div>

<?php if ($errs !== []): ?>
  <div class="admin-alert"><?= icon('bell') ?><div><strong>Please check the form.</strong><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>

<?php if ($isEdit && $images !== []): ?>
  <div class="admin-card">
    <div class="admin-card-head"><div><h3>Images</h3><span>The first image is used on cards and previews.</span></div></div>
    <div class="admin-image-grid">
      <?php foreach ($images as $index => $image): ?>
        <div class="admin-image">
          <img src="<?= e(upload_url($image['path'])) ?>" alt="<?= e($image['alt']) ?>">
          <?php if ($index === 0): ?><span class="badge badge-green">Primary</span><?php endif; ?>
          <div class="admin-image-actions">
            <?php if ($index > 0): ?>
              <form action="<?= url('/admin/products/' . (int) $product['id'] . '/images/' . (int) $image['id'] . '/primary') ?>" method="post">
                <?= csrf_field() ?><button class="btn btn-xs btn-ghost" type="submit">Make primary</button>
              </form>
            <?php endif; ?>
            <form action="<?= url('/admin/products/' . (int) $product['id'] . '/images/' . (int) $image['id'] . '/delete') ?>" method="post" data-confirm="Delete this image?">
              <?= csrf_field() ?><button class="btn btn-xs btn-danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($isEdit): ?>
  <form action="<?= url('/admin/products/' . (int) $product['id'] . '/images') ?>" method="post" enctype="multipart/form-data" class="admin-card admin-inline-form">
    <?= csrf_field() ?>
    <div>
      <strong>Upload images</strong>
      <p class="tiny muted" style="margin:2px 0 0">JPG, PNG or WebP, at least 200 × 200 px. You can select multiple files.</p>
    </div>
    <input class="input" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
    <button class="btn btn-dark btn-sm" type="submit">Upload</button>
  </form>
<?php endif; ?>

<form action="<?= $isEdit ? url('/admin/products/' . (int) $product['id']) : url('/admin/products') ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="admin-grid-2" style="grid-template-columns:1.5fr 1fr;align-items:start">
    <div class="stack">
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Basics</h3><span>Customer-facing product details</span></div></div>
        <div class="admin-card-body">
          <div class="form-row">
            <div class="field"><label for="name">Product name</label><input class="input" id="name" name="name" value="<?= e($value('name')) ?>" required></div>
            <div class="field"><label for="sku">SKU</label><input class="input" id="sku" name="sku" value="<?= e($value('sku')) ?>" required></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="category_id">Category</label>
              <select class="select" id="category_id" name="category_id">
                <option value="">Uncategorised</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= (int) $category['id'] ?>"<?= selected((string) $category['id'], (string) $value('category_id')) ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field"><label for="brand">Brand</label><input class="input" id="brand" name="brand" value="<?= e($value('brand', 'Thunder Mounts')) ?>"></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="model">Model</label><input class="input" id="model" name="model" value="<?= e($value('model')) ?>"></div>
            <div class="field"><label for="slug">URL slug</label><input class="input" id="slug" name="slug" value="<?= e($value('slug')) ?>" placeholder="auto from name"></div>
          </div>
          <div class="field"><label for="short_description">Short description</label><textarea class="textarea" id="short_description" name="short_description" rows="2"><?= e($value('short_description')) ?></textarea><span class="hint">Shown on product cards and the top of the product page.</span></div>
          <div class="field"><label for="description">Full description</label><textarea class="textarea" id="description" name="description" rows="6"><?= e($value('description')) ?></textarea></div>
          <div class="field"><label for="compatibility">Compatibility</label><textarea class="textarea" id="compatibility" name="compatibility" rows="2"><?= e($value('compatibility')) ?></textarea></div>
          <div class="form-row">
            <div class="field"><label for="material">Material</label><input class="input" id="material" name="material" value="<?= e($value('material')) ?>"></div>
            <div class="field"><label for="finish">Finish</label><input class="input" id="finish" name="finish" value="<?= e($value('finish')) ?>"></div>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Specifications &amp; contents</h3><span>One item per line</span></div></div>
        <div class="admin-card-body">
          <div class="form-row">
            <div class="field">
              <label for="specs">Specifications</label>
              <textarea class="textarea" id="specs" name="specs" rows="6" placeholder="Length: 12 in.&#10;Material: 12-gauge steel"><?= e(old('specs', $specsText)) ?></textarea>
              <span class="hint">Use “Label: value” on each line.</span>
            </div>
            <div class="field">
              <label for="box_contents">What's in the box</label>
              <textarea class="textarea" id="box_contents" name="box_contents" rows="6" placeholder="One mount assembly&#10;Hardware pack"><?= e(old('box_contents', $boxText)) ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Search engine listing</h3><span>Optional overrides</span></div></div>
        <div class="admin-card-body">
          <div class="field"><label for="meta_title">Meta title</label><input class="input" id="meta_title" name="meta_title" maxlength="190" value="<?= e($value('meta_title')) ?>"></div>
          <div class="field"><label for="meta_description">Meta description</label><textarea class="textarea" id="meta_description" name="meta_description" rows="2" maxlength="300"><?= e($value('meta_description')) ?></textarea></div>
        </div>
      </div>
    </div>

    <div class="stack">
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Pricing</h3><span>USD</span></div></div>
        <div class="admin-card-body">
          <div class="field"><label for="price">Price</label><input class="input" id="price" name="price" type="number" step="0.01" min="0" value="<?= e($value('price', '0.00')) ?>" required></div>
          <div class="field"><label for="sale_price">Sale price</label><input class="input" id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="<?= e($value('sale_price')) ?>"><span class="hint">Leave blank for no sale.</span></div>
          <div class="field"><label for="dealer_price">Fixed dealer price</label><input class="input" id="dealer_price" name="dealer_price" type="number" step="0.01" min="0" value="<?= e($value('dealer_price')) ?>"><span class="hint">Optional. Tier percentage applies when blank.</span></div>
          <div class="field"><label for="cost">Cost</label><input class="input" id="cost" name="cost" type="number" step="0.01" min="0" value="<?= e($value('cost')) ?>"><span class="hint">Never shown to customers.</span></div>
          <label class="check"><input type="checkbox" name="is_taxable" value="1" <?= $isEdit ? ((int) $product['is_taxable'] === 1 ? 'checked' : '') : 'checked' ?>><span>Taxable</span></label>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Inventory</h3><span><?= $isEdit ? 'Use Inventory to adjust counts' : 'Starting values' ?></span></div></div>
        <div class="admin-card-body">
          <div class="field"><label for="stock_status">Availability</label>
            <select class="select" id="stock_status" name="stock_status">
              <?php foreach (['in_stock' => 'In stock', 'out_of_stock' => 'Out of stock', 'preorder' => 'Coming soon / preorder', 'quote' => 'Request a quote'] as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= selected($key, $value('stock_status', 'in_stock')) ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if (!$isEdit): ?>
            <div class="field"><label for="stock_qty">Opening stock</label><input class="input" id="stock_qty" name="stock_qty" type="number" min="0" value="<?= e($value('stock_qty', '0')) ?>"></div>
          <?php else: ?>
            <p class="small">Current stock: <strong><?= (int) $product['stock_qty'] ?></strong> <?= (int) $product['manage_stock'] === 1 ? '' : '(not tracked)' ?></p>
            <a class="btn btn-outline btn-sm" href="<?= url('/admin/inventory') ?>">Adjust stock</a>
          <?php endif; ?>
          <div class="field" style="margin-top:12px"><label for="low_stock_threshold">Low-stock threshold</label><input class="input" id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" value="<?= e($value('low_stock_threshold', '5')) ?>"></div>
          <label class="check"><input type="checkbox" name="manage_stock" value="1" <?= $isEdit ? ((int) $product['manage_stock'] === 1 ? 'checked' : '') : 'checked' ?>><span>Track stock quantity</span></label>
          <label class="check"><input type="checkbox" name="backorder_allowed" value="1" <?= $isEdit ? ((int) $product['backorder_allowed'] === 1 ? 'checked' : '') : '' ?>><span>Allow backorders</span></label>
          <div class="form-row" style="margin-top:6px">
            <div class="field"><label for="weight">Weight (lb)</label><input class="input" id="weight" name="weight" type="number" step="0.01" min="0" value="<?= e($value('weight')) ?>"></div>
            <div class="field"><label for="length">Length (in.)</label><input class="input" id="length" name="length" type="number" step="0.01" min="0" value="<?= e($value('length')) ?>"></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="width">Width (in.)</label><input class="input" id="width" name="width" type="number" step="0.01" min="0" value="<?= e($value('width')) ?>"></div>
            <div class="field"><label for="height">Height (in.)</label><input class="input" id="height" name="height" type="number" step="0.01" min="0" value="<?= e($value('height')) ?>"></div>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Visibility</h3></div></div>
        <div class="admin-card-body">
          <label class="check"><input type="checkbox" name="is_active" value="1" <?= $isEdit ? ((int) $product['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Published in the store</span></label>
          <label class="check"><input type="checkbox" name="is_featured" value="1" <?= $isEdit ? ((int) $product['is_featured'] === 1 ? 'checked' : '') : '' ?>><span>Featured on the homepage</span></label>
        </div>
      </div>

      <?php if (!$isEdit): ?>
        <div class="admin-card">
          <div class="admin-card-head"><div><h3>Images</h3><span>You can add more after saving</span></div></div>
          <div class="admin-card-body">
            <div class="field"><label for="images">Product images</label><input class="input" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></div>
          </div>
        </div>
      <?php endif; ?>

      <div class="admin-save">
        <button class="btn btn-primary btn-block btn-lg" type="submit"><?= $isEdit ? 'Save product' : 'Create product' ?></button>
        <a class="btn btn-ghost btn-block btn-sm" href="<?= url('/admin/products') ?>">Cancel</a>
        <?php if ($isEdit): ?>
          <button class="btn btn-danger btn-block btn-sm mt-2" type="submit" formaction="<?= url('/admin/products/' . (int) $product['id'] . '/delete') ?>" data-confirm="Delete this product? Products on past orders will be hidden instead.">Delete product</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>

<?php if ($isEdit && $movements !== []): ?>
  <div class="admin-card mt-3">
    <div class="admin-card-head"><div><h3>Inventory history</h3><span>Most recent movements</span></div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Change</th><th>Stock after</th><th>Reason</th><th>By</th></tr></thead>
        <tbody>
          <?php foreach ($movements as $movement): ?>
            <tr>
              <td class="small"><?= e(fmt_datetime($movement['created_at'])) ?></td>
              <td class="<?= (int) $movement['delta'] >= 0 ? 'green bold' : 'red bold' ?>"><?= (int) $movement['delta'] > 0 ? '+' : '' ?><?= (int) $movement['delta'] ?></td>
              <td><?= (int) $movement['stock_after'] ?></td>
              <td class="small"><?= e($movement['reason']) ?></td>
              <td class="small muted"><?= e(trim(($movement['first_name'] ?? '') . ' ' . ($movement['last_name'] ?? '')) ?: 'System') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php clear_input(); ?>
