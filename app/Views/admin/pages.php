<?php
use App\Core\Csrf;
$errs = errors();
$edit = $editPage;
$value = static fn (string $key, string $default = '') => old($key, $edit[$key] ?? $default);
?>
<div class="admin-head">
  <div><h2>Pages</h2><p>Policy, installation and company pages. Content accepts HTML.</p></div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/pages') ?>"><?= icon('plus') ?> New page</a>
</div>

<div class="admin-grid-2" style="grid-template-columns:1fr 1.4fr;align-items:start">
  <div class="admin-card">
    <div class="admin-card-head"><div><h3>All pages</h3><span><?= count($pages) ?> total</span></div></div>
    <div class="admin-list">
      <?php foreach ($pages as $page): ?>
        <a class="admin-list-item <?= $edit && (int) $edit['id'] === (int) $page['id'] ? 'active' : '' ?>" href="<?= url('/admin/pages?edit=' . (int) $page['id']) ?>">
          <span>
            <strong><?= e($page['title']) ?></strong>
            <small>/page/<?= e($page['slug']) ?><?= (int) $page['show_in_footer'] ? ' · footer' : '' ?></small>
          </span>
          <span class="badge <?= (int) $page['is_active'] ? 'badge-green' : '' ?>"><?= (int) $page['is_active'] ? 'Live' : 'Hidden' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><div><h3><?= $edit ? 'Edit page' : 'New page' ?></h3></div><?php if ($edit): ?><a class="small bold" href="<?= url('/page/' . urlencode($edit['slug'])) ?>" target="_blank" rel="noopener">View live →</a><?php endif; ?></div>
    <div class="admin-card-body">
      <?php if ($errs !== []): ?><div class="admin-alert"><?= icon('bell') ?><div><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div><?php endif; ?>
      <form action="<?= $edit ? url('/admin/pages/' . (int) $edit['id']) : url('/admin/pages') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="field"><label for="page-title">Title</label><input class="input" id="page-title" name="title" value="<?= e($value('title')) ?>" required></div>
          <div class="field"><label for="page-slug">Slug</label><input class="input" id="page-slug" name="slug" value="<?= e($value('slug')) ?>" placeholder="auto from title"></div>
        </div>
        <div class="field"><label for="page-content">Content (HTML allowed)</label><textarea class="textarea" id="page-content" name="content" rows="18" required style="font-family:ui-monospace,Menlo,monospace;font-size:13px"><?= e($value('content')) ?></textarea></div>
        <div class="form-row">
          <div class="field"><label for="page-meta-title">Meta title</label><input class="input" id="page-meta-title" name="meta_title" value="<?= e($value('meta_title')) ?>"></div>
          <div class="field"><label for="page-sort">Sort order</label><input class="input" id="page-sort" name="sort_order" type="number" value="<?= e($value('sort_order', '0')) ?>"></div>
        </div>
        <div class="field"><label for="page-meta-description">Meta description</label><textarea class="textarea" id="page-meta-description" name="meta_description" rows="2" maxlength="300"><?= e($value('meta_description')) ?></textarea></div>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= $edit ? ((int) $edit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>><span>Published</span></label>
        <label class="check"><input type="checkbox" name="show_in_footer" value="1" <?= $edit ? ((int) $edit['show_in_footer'] === 1 ? 'checked' : '') : '' ?>><span>Show in footer support links</span></label>
        <div class="flex wrap mt-2">
          <button class="btn btn-primary" type="submit"><?= $edit ? 'Save page' : 'Create page' ?></button>
          <?php if ($edit): ?>
            <button class="btn btn-danger" type="submit" formaction="<?= url('/admin/pages/' . (int) $edit['id'] . '/delete') ?>" data-confirm="Delete this page?">Delete</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>
<?php clear_input(); ?>
