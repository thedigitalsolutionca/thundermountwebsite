<?php
/** @var array $crumbs */
$items = array_values(array_filter($crumbs ?? []));
if ($items === []) {
    return;
}
?>
<nav class="crumbs" aria-label="Breadcrumb">
  <a href="<?= url('/') ?>">Home</a>
  <?php foreach ($items as $index => $crumb): ?>
    <span aria-hidden="true">/</span>
    <?php if (!empty($crumb['url']) && $index < count($items) - 1): ?>
      <a href="<?= e(url($crumb['url'])) ?>"><?= e($crumb['label']) ?></a>
    <?php else: ?>
      <span style="margin:0;opacity:1" aria-current="page"><?= e($crumb['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
