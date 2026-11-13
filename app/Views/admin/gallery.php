<?php
use App\Core\Csrf;
/** @var array $items */
/** @var array $counts */
/** @var array $filters */
/** @var ?array $editItem */
$errs = errors();
$edit = $editItem;
$defaultMode = 'image';
if ($edit) {
    if ($edit['media_type'] === 'video') {
        $defaultMode = $edit['video_source'] === 'upload' ? 'video_upload' : 'video_url';
    } else {
        $defaultMode = 'image';
    }
}
$activeMode = (string) old('source_mode', $defaultMode);
if (!in_array($activeMode, ['image', 'video_upload', 'video_url'], true)) {
    $activeMode = 'image';
}
$value = static fn (string $key, string $default = '') => (string) old($key, (string) ($edit[$key] ?? $default));
?>
<div class="admin-head">
  <div>
    <h2>Gallery &amp; Portfolio</h2>
    <p>Upload installation photos, MP4/WebM videos, or YouTube &amp; Vimeo links for the public Portfolio &amp; Gallery page.</p>
  </div>
  <div class="flex wrap" style="gap:8px">
    <a class="btn btn-outline btn-sm" href="<?= url('/portfolio') ?>" target="_blank" rel="noopener"><?= icon('external') ?> View live Portfolio &amp; Gallery</a>
  </div>
</div>

<div class="admin-tabs">
  <a class="<?= $filters['type'] === '' && $filters['status'] === '' ? 'active' : '' ?>" href="<?= url('/admin/gallery') ?>">All <span><?= (int) ($counts['all'] ?? 0) ?></span></a>
  <a class="<?= $filters['type'] === 'image' ? 'active' : '' ?>" href="<?= url('/admin/gallery?type=image') ?>">Photos <span><?= (int) ($counts['image'] ?? 0) ?></span></a>
  <a class="<?= $filters['type'] === 'video' ? 'active' : '' ?>" href="<?= url('/admin/gallery?type=video') ?>">Videos <span><?= (int) ($counts['video'] ?? 0) ?></span></a>
  <a class="<?= $filters['status'] === 'hidden' ? 'active' : '' ?>" href="<?= url('/admin/gallery?status=hidden') ?>">Hidden <?php if (!empty($counts['hidden'])): ?><span><?= (int) $counts['hidden'] ?></span><?php endif; ?></a>
</div>

<form method="get" action="<?= url('/admin/gallery') ?>" class="admin-filters">
  <?php if ($filters['type'] !== ''): ?><input type="hidden" name="type" value="<?= e($filters['type']) ?>"><?php endif; ?>
  <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
  <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search title, caption or category">
  <button class="btn btn-dark btn-sm" type="submit">Filter</button>
  <?php if ($filters['q'] !== '' || $filters['type'] !== '' || $filters['status'] !== ''): ?>
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/gallery') ?>">Reset</a>
  <?php endif; ?>
</form>

<div class="admin-grid-2 admin-gallery-layout">
  <div class="admin-card">
    <div class="admin-card-head">
      <div>
        <h3>Media library</h3>
        <span><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> shown · lower sort order appears first</span>
      </div>
    </div>
    <?php if ($items === []): ?>
      <div class="admin-empty">
        <?= icon('image') ?>
        <p>No media items match this filter. Add a photo or video using the form.</p>
      </div>
    <?php else: ?>
      <div class="admin-media-list">
        <?php foreach ($items as $item): ?>
          <article class="admin-media-item <?= (int) $item['is_active'] === 0 ? 'is-hidden' : '' ?> <?= $edit && (int) $edit['id'] === (int) $item['id'] ? 'is-editing' : '' ?>">
            <div class="admin-media-thumb">
              <?php if ($item['thumb_url'] !== ''): ?>
                <img src="<?= e($item['thumb_url']) ?>" alt="<?= e($item['alt_text'] ?: $item['title']) ?>" loading="lazy" width="240" height="160">
              <?php elseif ($item['is_video'] && $item['video_source'] === 'upload'): ?>
                <video src="<?= e(upload_url($item['media_path'])) ?>#t=0.5" muted playsinline preload="metadata"></video>
              <?php else: ?>
                <div class="admin-media-placeholder"><?= icon('video') ?></div>
              <?php endif; ?>
              <?php if ($item['is_video']): ?>
                <span class="admin-media-play" aria-hidden="true"><?= icon('play') ?></span>
              <?php endif; ?>
              <span class="admin-media-kind badge <?= $item['is_video'] ? 'badge-dark' : 'badge-gray' ?>"><?= e($item['source_label']) ?></span>
            </div>

            <div class="admin-media-body">
              <div class="flex wrap" style="gap:6px;align-items:center">
                <strong><?= e($item['title']) ?></strong>
                <?php if ((int) $item['is_featured'] === 1): ?><span class="badge badge-amber">Featured</span><?php endif; ?>
                <?php if ((int) $item['is_active'] === 1): ?>
                  <span class="badge badge-green">Live</span>
                <?php else: ?>
                  <span class="badge badge-gray">Hidden</span>
                <?php endif; ?>
              </div>
              <?php if ($item['caption'] !== ''): ?>
                <p class="small muted" style="margin:4px 0 6px"><?= e($item['caption']) ?></p>
              <?php endif; ?>
              <div class="tiny muted">
                Category: <strong><?= e($item['category']) ?></strong> · Order: <?= (int) $item['sort_order'] ?>
                <?php if ($item['is_video']): ?> · <span title="<?= e($item['media_path']) ?>"><?= e(mb_substr($item['media_path'], 0, 48)) ?><?= mb_strlen($item['media_path']) > 48 ? '…' : '' ?></span><?php endif; ?>
              </div>

              <div class="admin-media-actions">
                <form action="<?= url('/admin/gallery/' . (int) $item['id'] . '/move') ?>" method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="direction" value="up">
                  <button class="btn btn-xs btn-ghost" type="submit" title="Move earlier" aria-label="Move <?= e($item['title']) ?> up"><?= icon('arrow-up') ?></button>
                </form>
                <form action="<?= url('/admin/gallery/' . (int) $item['id'] . '/move') ?>" method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="direction" value="down">
                  <button class="btn btn-xs btn-ghost" type="submit" title="Move later" aria-label="Move <?= e($item['title']) ?> down"><?= icon('arrow-down') ?></button>
                </form>
                <a class="btn btn-xs btn-outline" href="<?= url('/admin/gallery?edit=' . (int) $item['id']) ?>"><?= icon('edit') ?> Edit</a>
                <form action="<?= url('/admin/gallery/' . (int) $item['id'] . '/toggle') ?>" method="post">
                  <?= csrf_field() ?>
                  <button class="btn btn-xs btn-outline" type="submit"><?= (int) $item['is_active'] === 1 ? 'Hide' : 'Publish' ?></button>
                </form>
                <form action="<?= url('/admin/gallery/' . (int) $item['id'] . '/delete') ?>" method="post" data-confirm="Delete “<?= e($item['title']) ?>” from the Portfolio &amp; Gallery?">
                  <?= csrf_field() ?>
                  <button class="btn btn-xs btn-danger" type="submit">Delete</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="admin-card admin-sticky-card">
    <div class="admin-card-head">
      <div>
        <h3><?= $edit ? 'Edit media item' : 'Add photo or video' ?></h3>
        <span><?= $edit ? 'Update details or replace media' : 'Publishes straight to /portfolio and /gallery' ?></span>
      </div>
      <?php if ($edit): ?><a class="small bold" href="<?= url('/admin/gallery') ?>">Cancel edit</a><?php endif; ?>
    </div>
    <div class="admin-card-body">
      <?php if ($errs !== []): ?>
        <div class="admin-alert"><?= icon('bell') ?><div><strong>Please check the form.</strong><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div>
      <?php endif; ?>

      <form action="<?= $edit ? url('/admin/gallery/' . (int) $edit['id']) : url('/admin/gallery') ?>" method="post" enctype="multipart/form-data" data-gallery-form>
        <?= csrf_field() ?>

        <div class="field">
          <label>Media type</label>
          <div class="media-mode-switch" role="radiogroup" aria-label="Media source type">
            <label class="media-mode-pill <?= $activeMode === 'image' ? 'active' : '' ?>">
              <input type="radio" name="source_mode" value="image" <?= $activeMode === 'image' ? 'checked' : '' ?> data-gallery-mode="image">
              <?= icon('image') ?> <span>Photo</span>
            </label>
            <label class="media-mode-pill <?= $activeMode === 'video_upload' ? 'active' : '' ?>">
              <input type="radio" name="source_mode" value="video_upload" <?= $activeMode === 'video_upload' ? 'checked' : '' ?> data-gallery-mode="video_upload">
              <?= icon('video') ?> <span>Upload Video</span>
            </label>
            <label class="media-mode-pill <?= $activeMode === 'video_url' ? 'active' : '' ?>">
              <input type="radio" name="source_mode" value="video_url" <?= $activeMode === 'video_url' ? 'checked' : '' ?> data-gallery-mode="video_url">
              <?= icon('external') ?> <span>YouTube / Vimeo</span>
            </label>
          </div>
        </div>

        <div data-gallery-panel="image" <?= $activeMode === 'image' ? '' : 'hidden' ?>>
          <div class="field">
            <label for="image_file"><?= $edit && !$edit['is_video'] ? 'Replace photo (optional)' : 'Upload photo' ?></label>
            <input class="input" id="image_file" name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
            <span class="hint">JPG, PNG or WebP, at least 200 × 200 px, up to 10 MB. Automatically resized for fast page loads.</span>
          </div>
          <?php if ($edit && !$edit['is_video']): ?>
            <input type="hidden" name="existing_image_path" value="<?= e($edit['media_path']) ?>">
            <div class="admin-note small" style="margin-bottom:12px">Current file: <code><?= e($edit['media_path']) ?></code></div>
          <?php endif; ?>
        </div>

        <div data-gallery-panel="video_upload" <?= $activeMode === 'video_upload' ? '' : 'hidden' ?>>
          <div class="field">
            <label for="video_file"><?= $edit && $edit['video_source'] === 'upload' ? 'Replace video file (optional)' : 'Upload video file' ?></label>
            <input class="input" id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">
            <span class="hint">MP4 or WebM recommended (up to 50 MB). Plays directly inside the storefront lightbox.</span>
          </div>
          <?php if ($edit && $edit['video_source'] === 'upload'): ?>
            <div class="admin-note small" style="margin-bottom:12px">Current video: <code><?= e($edit['media_path']) ?></code></div>
          <?php endif; ?>
        </div>

        <div data-gallery-panel="video_url" <?= $activeMode === 'video_url' ? '' : 'hidden' ?>>
          <div class="field">
            <label for="video_url">YouTube, Vimeo, or direct MP4/WebM URL</label>
            <input class="input" id="video_url" name="video_url" type="url" value="<?= e(old('video_url', ($edit && $edit['is_video'] && $edit['video_source'] !== 'upload') ? $edit['media_path'] : '')) ?>" placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/...">
            <span class="hint">Supports YouTube watch/share/shorts links, Vimeo links, or direct HTTPS <code>.mp4</code> / <code>.webm</code> URLs.</span>
          </div>
        </div>

        <div data-gallery-panel="poster" <?= $activeMode === 'image' ? 'hidden' : '' ?>>
          <div class="field">
            <label for="poster_file">Custom video cover image <span class="muted">(optional)</span></label>
            <input class="input" id="poster_file" name="poster_file" type="file" accept="image/jpeg,image/png,image/webp">
            <span class="hint">Optional thumbnail image (JPG, PNG or WebP). YouTube thumbnails are fetched automatically if left blank.</span>
          </div>
          <?php if ($edit && $edit['poster_path'] !== ''): ?>
            <label class="check" style="margin-bottom:10px">
              <input type="checkbox" name="remove_poster" value="1">
              <span>Remove current custom cover image</span>
            </label>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="gallery-title">Title</label>
          <input class="input" id="gallery-title" name="title" value="<?= e($value('title')) ?>" required maxlength="190" placeholder="e.g. Full-view glass door installation">
        </div>

        <div class="field">
          <label for="gallery-caption">Caption / project detail <span class="muted">(optional)</span></label>
          <textarea class="textarea" id="gallery-caption" name="caption" rows="2" maxlength="500" placeholder="Short description shown below the card and in the viewer"><?= e($value('caption')) ?></textarea>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="gallery-category">Category</label>
            <input class="input" id="gallery-category" name="category" list="gallery-categories" value="<?= e($value('category', 'Installations')) ?>" maxlength="80">
            <datalist id="gallery-categories">
              <option value="Installations"></option>
              <option value="Track Mounts"></option>
              <option value="Opener Mounts"></option>
              <option value="Powder Coating"></option>
              <option value="Hardware Details"></option>
              <option value="Before &amp; After"></option>
            </datalist>
          </div>
          <div class="field">
            <label for="gallery-sort">Sort order</label>
            <input class="input" id="gallery-sort" name="sort_order" type="number" value="<?= e($value('sort_order', '10')) ?>">
          </div>
        </div>

        <div class="field">
          <label for="gallery-alt">Alt text <span class="muted">(accessibility — defaults to title)</span></label>
          <input class="input" id="gallery-alt" name="alt_text" value="<?= e($value('alt_text')) ?>" maxlength="255" placeholder="Describe what is shown in the photo or video">
        </div>

        <div class="flex wrap" style="gap:16px;margin:8px 0 14px">
          <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= $edit ? ((int) $edit['is_active'] === 1 ? 'checked' : '') : 'checked' ?>>
            <span>Published on storefront</span>
          </label>
          <label class="check">
            <input type="checkbox" name="is_featured" value="1" <?= $edit ? ((int) $edit['is_featured'] === 1 ? 'checked' : '') : '' ?>>
            <span>Featured badge</span>
          </label>
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= $edit ? 'Save changes' : 'Add to Portfolio & Gallery' ?></button>
      </form>
    </div>
  </div>
</div>
<?php clear_input(); ?>
