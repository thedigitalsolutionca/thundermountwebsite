<?php
/** @var \App\Core\Paginator $paginator */
if ($paginator->pages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Pagination">
  <?php if ($paginator->hasPrevious()): ?>
    <a class="page-link" href="<?= e(page_url($paginator->current - 1)) ?>" rel="prev" aria-label="Previous page"><?= icon('arrow-left') ?></a>
  <?php else: ?>
    <span class="page-link disabled" aria-disabled="true"><?= icon('arrow-left') ?></span>
  <?php endif; ?>

  <?php foreach ($paginator->links() as $link): ?>
    <?php if ($link === '…'): ?>
      <span class="page-ellipsis">…</span>
    <?php elseif ((int) $link === $paginator->current): ?>
      <span class="page-link current" aria-current="page"><?= (int) $link ?></span>
    <?php else: ?>
      <a class="page-link" href="<?= e(page_url((int) $link)) ?>"><?= (int) $link ?></a>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if ($paginator->hasNext()): ?>
    <a class="page-link" href="<?= e(page_url($paginator->current + 1)) ?>" rel="next" aria-label="Next page"><?= icon('arrow-right') ?></a>
  <?php else: ?>
    <span class="page-link disabled" aria-disabled="true"><?= icon('arrow-right') ?></span>
  <?php endif; ?>
</nav>
