<?php
/** @var array $servicePage */
$page = $servicePage;
$ctaPath = $page['path'] ?? '/contact?subject=' . urlencode($page['subject'] ?? $page['title']);
?>
<section class="service-hero section">
  <div class="container split">
    <div>
      <span class="eyebrow"><?= e($page['eyebrow']) ?></span>
      <h1><?= e($page['title']) ?></h1>
      <p class="lead"><?= e($page['intro']) ?></p>
      <a class="btn btn-primary btn-lg" href="<?= e(url($ctaPath)) ?>"><?= e($page['cta']) ?> <?= icon('arrow-right') ?></a>
    </div>
    <img src="<?= asset('img/' . $page['image']) ?>" alt="Thunder Mounts finished garage installation" width="1000" height="800" fetchpriority="high">
  </div>
</section>
<section class="section">
  <div class="container">
    <div class="grid grid-2">
      <?php foreach ($page['sections'] as [$heading, $body]): ?>
        <article class="card card-body service-detail">
          <h2><?= e($heading) ?></h2>
          <p class="muted"><?= e($body) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="service-enquiry">
      <span class="eyebrow">Let’s talk about your project</span>
      <h2><?= e(isset($page['subject']) && $page['subject'] === 'Powder coating quote' ? 'A finish that brings it all together.' : 'Let’s find your next step.') ?></h2>
      <p><?= e($page['note'] ?? 'Our team can help you choose the next step for your garage.') ?></p>
      <a class="btn btn-dark" href="<?= e(url($ctaPath)) ?>"><?= e($page['cta']) ?></a>
    </div>
  </div>
</section>
