<div class="page-head resource-page-head journal-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Garage journal']]]) ?>
    <span class="eyebrow">Ideas &amp; practical advice</span>
    <h1>The garage journal.</h1>
    <p class="resource-lead">Straightforward notes on choosing a mount, measuring a drop and thinking through the finished installation. Written for planning — not as a substitute for a product manual.</p>
  </div>
</div>
<section class="section-tight" style="padding-top:4px">
  <div class="container">
    <div class="journal-grid">
      <?php foreach ($posts as $post): ?>
        <article class="journal-card">
          <a class="journal-image" href="<?= url('/blog/' . $post['slug']) ?>" aria-label="Read <?= e($post['title']) ?>"><img src="<?= e(asset(substr($post['image'], strlen('/assets/')))) ?>" alt="<?= e($post['image_alt']) ?>" loading="lazy" width="750" height="500"></a>
          <div class="journal-copy">
            <span class="eyebrow"><?= e($post['category']) ?></span>
            <h2><a href="<?= url('/blog/' . $post['slug']) ?>"><?= e($post['title']) ?></a></h2>
            <p><?= e($post['summary']) ?></p>
            <a class="bold red" href="<?= url('/blog/' . $post['slug']) ?>">Read the guide <?= icon('arrow-right') ?></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="journal-note"><span class="eyebrow">Looking for the older blog?</span><p class="muted">These are new, concise guides for this site. The complete archive of past articles and their original images has not yet been migrated. For a specific older article, <a href="<?= url('/contact') ?>" class="bold red">ask our team</a>.</p></div>
  </div>
</section>
