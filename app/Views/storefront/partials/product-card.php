<?php
/** @var array $product */
$price = $product['price_info'];
$availability = $product['availability'];
$image = $product['images'][0]['path'] ?? '/assets/img/tm-mount-1.jpg';
$availabilityClass = match ($availability['key']) {
    'in_stock' => 'stock-in',
    'low_stock' => 'stock-low',
    'preorder' => 'stock-pre',
    default => 'stock-out',
};
?>
<article class="product-card">
  <div class="media">
    <a href="<?= e(url($product['url'])) ?>" aria-label="<?= e($product['name']) ?>">
      <img src="<?= e(upload_url($image)) ?>" alt="<?= e($product['images'][0]['alt'] ?? $product['name']) ?>" loading="lazy" width="600" height="600">
    </a>
    <div class="ribbons">
      <?php if ($product['is_featured']): ?><span class="badge badge-dark">Featured</span><?php endif; ?>
      <?php if ($price['on_sale']): ?><span class="badge badge-red">Sale</span><?php endif; ?>
      <?php if ($availability['key'] === 'preorder'): ?><span class="badge badge-amber">Coming soon</span><?php endif; ?>
      <?php if ($availability['key'] === 'quote'): ?><span class="badge badge-blue">Quote</span><?php endif; ?>
      <?php if ($price['is_dealer']): ?><span class="badge badge-green">Dealer price</span><?php endif; ?>
    </div>
  </div>
  <div class="body">
    <span class="cat"><?= e($product['category_name'] ?? '') ?></span>
    <h3 class="name"><a href="<?= e(url($product['url'])) ?>"><?= e($product['name']) ?></a></h3>
    <span class="sku">Model <?= e($product['model']) ?> · SKU <?= e($product['sku']) ?></span>
    <div class="price-row">
      <?php if ($availability['key'] === 'quote'): ?>
        <span class="price">Request a quote</span>
      <?php else: ?>
        <span class="price <?= $price['is_dealer'] ? 'price-dealer' : '' ?>"><?= e(money($price['unit_cents'])) ?></span>
        <?php if ($price['is_dealer'] && $price['unit_cents'] < $price['retail_cents']): ?>
          <span class="price-old"><?= e(money($price['retail_cents'])) ?> retail</span>
        <?php elseif ($price['on_sale']): ?>
          <span class="price-old"><?= e(money($price['list_cents'])) ?></span>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <span class="stock <?= $availabilityClass ?>"><i class="dot"></i><?= e($availability['label']) ?></span>
  </div>
  <div class="foot">
    <?php if ($product['can_purchase']): ?>
      <form action="<?= url('/cart/add') ?>" method="post" data-add-to-cart>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="qty" value="1">
        <button class="btn btn-primary btn-sm btn-block" type="submit">Add to Cart</button>
      </form>
    <?php elseif ($availability['key'] === 'preorder'): ?>
      <a class="btn btn-outline btn-sm" href="<?= e(url($product['url'])) ?>#notify">Notify me</a>
    <?php elseif ($availability['key'] === 'quote'): ?>
      <a class="btn btn-outline btn-sm" href="<?= url('/contact?subject=' . urlencode('Quote request: ' . $product['name'])) ?>">Request a quote</a>
    <?php else: ?>
      <a class="btn btn-outline btn-sm" href="<?= url('/contact?subject=' . urlencode('Availability: ' . $product['name'])) ?>">Check availability</a>
    <?php endif; ?>
  </div>
</article>
