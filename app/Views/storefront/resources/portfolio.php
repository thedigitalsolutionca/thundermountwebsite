<?php
$projects = [
    ['image' => 'tm-track-glass.jpg', 'alt' => 'Black steel track supports above a glass-panel garage door', 'title' => 'Full-view door, considered support', 'detail' => 'A close look at the overhead track and clean ceiling lines.'],
    ['image' => 'chi-door-malibu.jpg', 'alt' => 'Installers on ladders working in a Malibu garage', 'title' => 'The work behind the finish', 'detail' => 'Installation in progress at a Malibu, California garage.'],
    ['image' => 'tm-install-white.jpg', 'alt' => 'White garage with door tracks and a ceiling-mounted opener', 'title' => 'A finished white garage', 'detail' => 'Track, opener and room working together.'],
    ['image' => 'after.jpg', 'alt' => 'Overhead track support above fitted cabinets', 'title' => 'Clear overhead lines', 'detail' => 'A compact track-support detail above cabinetry.'],
    ['image' => 'tm-tms-install.jpg', 'alt' => 'Close view of an overhead opener mount in a garage', 'title' => 'Opener mounting detail', 'detail' => 'A close-up of the motor support and ceiling attachment.'],
    ['image' => 'tm-chrome-ring.jpg', 'alt' => 'Track support with a chrome finishing ring at the ceiling', 'title' => 'The finishing detail', 'detail' => 'The chrome ring around a track mount at the ceiling.'],
];
?>
<div class="page-head portfolio-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Portfolio']]]) ?>
    <span class="eyebrow">Installed in the real world</span>
    <h1>Good work looks good from every angle.</h1>
    <p class="resource-lead">A closer look at garage door tracks, opener mounts and the details that change how an installation feels. These are real project photographs, not renderings.</p>
  </div>
</div>
<section class="section-tight" style="padding-top:4px">
  <div class="container">
    <div class="portfolio-grid">
      <?php foreach ($projects as $index => $project): ?>
        <figure class="portfolio-tile">
          <a href="<?= asset('img/' . $project['image']) ?>" data-lightbox="portfolio" data-lightbox-title="<?= e($project['title']) ?>" data-lightbox-detail="<?= e($project['detail']) ?>" aria-label="View larger photo: <?= e($project['title']) ?>">
            <img src="<?= asset('img/' . $project['image']) ?>" alt="<?= e($project['alt']) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" width="920" height="690">
          </a>
          <figcaption><strong><?= e($project['title']) ?></strong><span><?= e($project['detail']) ?></span></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
    <p class="small muted mt-2">Select any photo to view it larger — no new tab required. Every installation is different; confirm product fitment and structural requirements for your own project.</p>
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
