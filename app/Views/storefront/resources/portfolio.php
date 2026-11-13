<?php
if (!isset($items)) {
    $gallerySvc = new \App\Services\GalleryService();
    $items = $gallerySvc->publicItems();
    $galleryCategories = $gallerySvc->categories($items);
}
$galleryCategories = $galleryCategories ?? [];
$rawFilter = strtolower(trim((string) ($activeFilter ?? ($_GET['type'] ?? 'all'))));
$currentType = match ($rawFilter) {
    'photos', 'photo', 'image', 'images' => 'image',
    'videos', 'video' => 'video',
    default => 'all',
};
$photoCount = 0;
$videoCount = 0;
foreach ($items as $it) {
    if (!empty($it['is_video'])) {
        $videoCount++;
    } else {
        $photoCount++;
    }
}
$totalCount = count($items);
$isAdmin = \App\Core\Auth::isAdmin();
?>
<div class="page-head portfolio-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Portfolio & Gallery']]]) ?>
    <div class="flex between wrap" style="gap:16px;align-items:flex-end">
      <div>
        <span class="eyebrow">Installed in the real world</span>
        <h1>Good work looks good from every angle.</h1>
        <p class="resource-lead">A closer look at garage door tracks, opener mounts and finished installations — real project photographs and walkthrough videos from Thunder Mounts.</p>
      </div>
      <?php if ($isAdmin): ?>
        <a class="btn btn-outline btn-sm" href="<?= url('/admin/gallery') ?>"><?= icon('image') ?> Manage gallery</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<section class="section-tight" style="padding-top:4px">
  <div class="container">
    <div class="portfolio-toolbar" data-portfolio-toolbar>
      <div class="portfolio-tabs" role="tablist" aria-label="Filter gallery by media type">
        <a href="<?= url('/portfolio') ?>" role="tab" aria-selected="<?= $currentType === 'all' ? 'true' : 'false' ?>" class="portfolio-tab <?= $currentType === 'all' ? 'active' : '' ?>" data-portfolio-filter="all">
          All <span class="count"><?= (int) $totalCount ?></span>
        </a>
        <a href="<?= url('/portfolio?type=photos') ?>" role="tab" aria-selected="<?= $currentType === 'image' ? 'true' : 'false' ?>" class="portfolio-tab <?= $currentType === 'image' ? 'active' : '' ?>" data-portfolio-filter="image">
          <?= icon('image') ?> Photos <span class="count"><?= (int) $photoCount ?></span>
        </a>
        <a href="<?= url('/portfolio?type=videos') ?>" role="tab" aria-selected="<?= $currentType === 'video' ? 'true' : 'false' ?>" class="portfolio-tab <?= $currentType === 'video' ? 'active' : '' ?>" data-portfolio-filter="video">
          <?= icon('video') ?> Videos <span class="count"><?= (int) $videoCount ?></span>
        </a>
      </div>

      <?php if (count($galleryCategories) > 1): ?>
        <div class="portfolio-cats" role="group" aria-label="Filter by project category">
          <button type="button" class="chip active" data-portfolio-cat="all">All categories</button>
          <?php foreach ($galleryCategories as $cat): ?>
            <button type="button" class="chip" data-portfolio-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="portfolio-grid" data-portfolio-grid>
      <?php foreach ($items as $index => $project): ?>
        <?php
        $itemType = !empty($project['is_video']) ? 'video' : 'image';
        $hiddenByFilter = $currentType !== 'all' && $currentType !== $itemType;
        ?>
        <figure class="portfolio-tile <?= !empty($project['is_video']) ? 'is-video' : 'is-image' ?>" data-media-type="<?= e($itemType) ?>" data-media-category="<?= e($project['category'] ?? '') ?>" <?= $hiddenByFilter ? 'hidden' : '' ?>>
          <a href="<?= e($project['lightbox_href']) ?>"
             data-lightbox="portfolio"
             data-lightbox-type="<?= e($project['lightbox_type']) ?>"
             data-lightbox-poster="<?= e($project['thumb_url']) ?>"
             data-lightbox-title="<?= e($project['title']) ?>"
             data-lightbox-detail="<?= e($project['caption']) ?>"
             aria-label="<?= !empty($project['is_video']) ? 'Play video: ' : 'View larger photo: ' ?><?= e($project['title']) ?>">
            <?php if ($project['thumb_url'] !== ''): ?>
              <img src="<?= e($project['thumb_url']) ?>" alt="<?= e($project['alt_text'] ?: $project['title']) ?>" loading="<?= $index < 2 ? 'eager' : 'lazy' ?>" width="920" height="690">
            <?php elseif (!empty($project['is_video']) && ($project['video_source'] ?? '') === 'upload'): ?>
              <video src="<?= e(upload_url($project['media_path'])) ?>#t=0.5" muted playsinline preload="metadata"></video>
            <?php else: ?>
              <div class="portfolio-video-slate">
                <?= icon('zap') ?>
                <span><?= e($project['source_label']) ?></span>
              </div>
            <?php endif; ?>

            <div class="portfolio-badges">
              <?php if (!empty($project['is_featured'])): ?><span class="badge badge-dark">Featured</span><?php endif; ?>
              <?php if (!empty($project['is_video'])): ?><span class="badge badge-red"><?= icon('video') ?> <?= e($project['source_label']) ?></span><?php endif; ?>
            </div>

            <?php if (!empty($project['is_video'])): ?>
              <span class="portfolio-play" aria-hidden="true"><?= icon('play') ?></span>
            <?php else: ?>
              <span class="portfolio-zoom" aria-hidden="true"><?= icon('zoom') ?></span>
            <?php endif; ?>
          </a>
          <figcaption>
            <?php if (!empty($project['category'])): ?><em class="portfolio-cat-tag"><?= e($project['category']) ?></em><?php endif; ?>
            <strong><?= e($project['title']) ?></strong>
            <?php if (!empty($project['caption'])): ?><span><?= e($project['caption']) ?></span><?php endif; ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>

    <div class="empty card card-body mt-2" data-portfolio-empty <?= ($currentType === 'video' && $videoCount === 0) || ($currentType === 'image' && $photoCount === 0) || $totalCount === 0 ? '' : 'hidden' ?>>
      <?= icon($currentType === 'video' ? 'video' : 'image') ?>
      <h4>No <?= $currentType === 'video' ? 'videos' : 'media items' ?> in this view yet</h4>
      <p class="small muted">Browse all project installations or check back soon as new installation walkthroughs are published.</p>
      <div class="flex wrap" style="justify-content:center;gap:8px">
        <a class="btn btn-outline btn-sm" href="<?= url('/portfolio') ?>" data-portfolio-reset>Show all items</a>
        <?php if ($isAdmin): ?>
          <a class="btn btn-primary btn-sm" href="<?= url('/admin/gallery') ?>">Upload media in Admin</a>
        <?php endif; ?>
      </div>
    </div>

    <p class="small muted mt-2">Select any photo or video to open it in the viewer — no new tab required. Every installation is different; confirm product fitment and structural requirements for your own project.</p>
  </div>
</section>

<section class="section section-gray">
  <div class="container">
    <div class="cta-band">
      <div><span class="eyebrow">Your project</span><h2 style="color:#fff;margin-bottom:6px">Have a garage in mind?</h2><p style="color:#C9CED4;margin:0">Tell us about the door, opener and ceiling. We’ll help you plan the right mount.</p></div>
      <div class="flex wrap"><a class="btn btn-primary" href="<?= url('/book-online') ?>">Request a conversation</a><a class="btn btn-outline-light" href="<?= url('/size-guide') ?>">See the size guide</a></div>
    </div>
  </div>
</section>
