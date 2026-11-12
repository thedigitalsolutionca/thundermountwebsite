<div class="page-head resource-page-head">
  <div class="container resource-narrow">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Garage journal', 'url' => '/blog'], ['label' => $post['title']]]]) ?>
    <span class="eyebrow"><?= e($post['category']) ?> · Garage journal</span>
    <h1><?= e($post['title']) ?></h1>
    <p class="resource-lead"><?= e($post['summary']) ?></p>
  </div>
</div>
<article class="section-tight" style="padding-top:0">
  <div class="container resource-narrow">
    <figure class="article-photo"><img src="<?= e(asset(substr($post['image'], strlen('/assets/')))) ?>" alt="<?= e($post['image_alt']) ?>" width="900" height="600" fetchpriority="high"></figure>
    <div class="prose">
      <?php foreach ($post['sections'] as $section): ?>
        <h2><?= e($section['heading']) ?></h2>
        <?php foreach ($section['paragraphs'] as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?>
      <?php endforeach; ?>
    </div>
    <div class="article-next">
      <div><span class="eyebrow">Keep planning</span><h2 style="font-size:1.3rem">The next useful step</h2></div>
      <a class="btn btn-primary" href="<?= url($post['link']) ?>"><?= e($post['link_label']) ?> <?= icon('arrow-right') ?></a>
    </div>
  </div>
</article>
<section class="section section-gray">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">More from the journal</span><h2>Keep exploring.</h2></div><a class="btn btn-outline" href="<?= url('/blog') ?>">All guides</a></div>
    <div class="resource-cards">
      <?php foreach ($posts as $other): ?>
        <?php if ($other['slug'] !== $post['slug']): ?>
          <a class="card card-body article-related" href="<?= url('/blog/' . $other['slug']) ?>"><span class="eyebrow"><?= e($other['category']) ?></span><h3><?= e($other['title']) ?></h3><p class="muted small"><?= e($other['summary']) ?></p><span class="bold red">Read article →</span></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
