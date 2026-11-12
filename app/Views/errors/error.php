<?php /** @var string $title */ /** @var string $message */ /** @var string $trace */ ?>
<section class="section">
  <div class="container" style="max-width:720px">
    <span class="eyebrow">Error <?= (int) ($status ?? 500) ?></span>
    <h1 style="font-size:2rem;margin-bottom:8px"><?= e($title ?? 'Something went wrong') ?></h1>
    <p class="muted"><?= e($message ?? 'An unexpected error occurred.') ?></p>
    <div class="flex wrap">
      <a class="btn btn-primary" href="<?= url('/') ?>">Go home</a>
      <a class="btn btn-outline" href="<?= url('/shop') ?>">Shop products</a>
      <a class="btn btn-ghost" href="<?= url('/contact') ?>">Contact us</a>
    </div>
    <?php if (!empty($trace)): ?>
      <pre style="margin-top:24px;padding:16px;background:#111;color:#eaeaea;overflow:auto;font-size:12px;border-radius:8px"><?= e($trace) ?></pre>
    <?php endif; ?>
    <p class="small muted mt-3">If this keeps happening, call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.</p>
  </div>
</section>
