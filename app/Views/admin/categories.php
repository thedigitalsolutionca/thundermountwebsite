<?php
use App\Core\Csrf;
$errs = errors();
$edit = $editCategory;
$value = static fn (string $key, string $default = '') => old($key, $edit[$key] ?? $default);
?>
<div class="admin-head">
  <div><h2>Categories</h2><p>Categories power the shop filters, homepage tiles and navigation.</p></div>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.5fr 1fr;align-items:start">
  <div class="admin-card">
    <div class="admin-card-head"><div><h3>All categories</h3><span><?= count($categories) ?> total</span></div></div>
    <?php if ($categories === []): ?>
      <div class="admin-empty"><?= icon('grid') ?><p>No categories yet. Add one using the form.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Category</th><th>Slug</th><th>Products</th><th>Order</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($categories as $category): ?>
              <tr>
                <td><strong><?= e($category['name']) ?></strong></td>
                <td class="small muted"><?= e($category['slug']) ?></td>
                <td><?= (int) $category['product_count'] ?></td>
                <td><?= (int) $category['sort_order'] ?></td>
                <td><?= (int) $category['is_active'] === 1 ? '<span class="badge badge-green">Active</span>' : '<span class="badge">Hidden</span>' ?></td>
                <td>
                  <div class="flex" style="gap:4px;justify-content:flex-end">
                    <a class="btn btn-xs btn-outline" href="<?= url('/admin/categories?edit=' . (int) $category['id']) ?>">Edit</a>
                    <form action="<?= url('/admin/categories/' . (int) $category['id'] . '/delete') ?>" method="post" data-confirm="Delete this category?">
                      <?= csrf_field() ?><button class="btn btn-xs btn-danger" type="submit">Delete</button>
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

  <div class="admin-card">
    <div class="admin-card-head"><div><h3><?= $edit ? 'Edit category' : 'New category' ?></h3></div><?php if ($edit): ?><a class="small bold" href="<?= url('/admin/categories') ?>">Cancel edit</a><?php endif; ?></div>
    <div class="admin-card-body">
      <?php if ($errs !== []): ?><div class="admin-alert"><?= icon('bell') ?><div><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div><?php endif; ?>
      <form action="<?= $edit ? url('/admin/categories/' . (int) $edit['id']) : url('/admin/categories') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field"><label for="cat-name">Name</label><input class="input" id="cat-name" name="name" value="<?= e($value('name')) ?>" required></div>
        <div class="field"><label for="cat-slug">URL slug</label><input class="input" id="cat-slug" name="slug" value="<?= e($value('slug')) ?>" placeholder="auto from name"></div>
        <div class="field"><label for="cat-description">Description</label><textarea class="textarea" id="cat-description" name="description" rows="3"><?= e($value('description')) ?></textarea></div>
        <div class="field"><label for="cat-image">Image path</label><input class="input" id="cat-image" name="image" value="<?= e($value('image')) ?>" placeholder="/assets/img/tm-product.jpg"></div>
        <div class="field"><label for="cat-image-file">Or upload an image</label><input class="input" id="cat-image-file" type="file" name="image_file" accept="image/jpeg,image/png,image/webp"></div>
        <div class="form-row">
          <div class="field"><label for="cat-sort">Sort order</label><input class="input" id="cat-sort" name="sort_order" type="number" value="<?= e($value('sort_order', '0')) ?>"></div>
          <div class="field" style="display:flex;align-items:flex-end"><label class="check"><input type="checkbox" name="is_active" value="1" <?= $edit ? ((int) $edit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Active</span></label></div>
        </div>
        <div class="field"><label for="cat-meta-title">Meta title</label><input class="input" id="cat-meta-title" name="meta_title" value="<?= e($value('meta_title')) ?>"></div>
        <div class="field"><label for="cat-meta-description">Meta description</label><textarea class="textarea" id="cat-meta-description" name="meta_description" rows="2" maxlength="300"><?= e($value('meta_description')) ?></textarea></div>
        <button class="btn btn-primary btn-block" type="submit"><?= $edit ? 'Save category' : 'Create category' ?></button>
      </form>
    </div>
  </div>
</div>
<?php clear_input(); ?>
