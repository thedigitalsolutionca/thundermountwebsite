<?php
/** @var array $summary */
if ($summary['items'] === []): ?>
  <div class="empty">
    <?= icon('cart') ?>
    <h4>Your cart is empty</h4>
    <p class="small">Browse mounts, openers and accessories.</p>
    <a class="btn btn-primary btn-sm" href="<?= url('/shop') ?>">Shop products</a>
  </div>
<?php else: ?>
  <?php foreach ($summary['items'] as $item): ?>
    <div class="cart-line">
      <img src="<?= e(upload_url($item['image'])) ?>" alt="">
      <div>
        <div class="name"><a href="<?= e(url($item['product']['url'])) ?>"><?= e($item['product']['name']) ?></a></div>
        <div class="meta"><?= e($item['product']['sku']) ?> · Qty <?= (int) $item['qty'] ?></div>
        <?php if (!$item['available']): ?><div class="meta" style="color:var(--red)">Unavailable — remove to check out</div><?php endif; ?>
      </div>
      <strong><?= e(money($item['line_cents'])) ?></strong>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
