<?php /** @var array $page */ ?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => $page['title']]]]) ?>
    <h1 style="margin-bottom:6px"><?= e($page['title']) ?></h1>
    <?php if (!empty($page['updated_at'])): ?><p class="small muted" style="margin:0">Last updated <?= e(fmt_date($page['updated_at'])) ?></p><?php endif; ?>
  </div>
</div>
<section class="section-tight" style="padding-top:8px">
  <div class="container">
    <div class="prose">
      <?= $page['content'] ?>
    </div>
    <div class="card card-body mt-3" style="max-width:820px">
      <div class="flex between wrap">
        <div>
          <strong>Still have a question?</strong>
          <p class="small muted" style="margin:4px 0 0">Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.</p>
        </div>
        <a class="btn btn-outline btn-sm" href="<?= url('/contact') ?>">Contact us</a>
      </div>
    </div>
  </div>
</section>
