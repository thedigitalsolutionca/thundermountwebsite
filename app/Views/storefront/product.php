<?php
use App\Core\Money;
use App\Core\Str;

/** @var array $product */
/** @var array $reviews */
/** @var array $stats */
/** @var array $related */
$price = $product['price_info'];
$availability = $product['availability'];
$availabilityClass = match ($availability['key']) {
    'in_stock' => 'stock-in',
    'low_stock' => 'stock-low',
    'preorder' => 'stock-pre',
    default => 'stock-out',
};
$images = $product['images'] ?: [['path' => '/assets/img/tm-mount-1.jpg', 'alt' => $product['name']]];
$reviews = $reviews ?? [];
$stats = $stats ?? ['total' => 0, 'average' => 0];
$canBuy = $product['can_purchase'];
$productLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'sku' => $product['sku'],
    'mpn' => $product['model'],
    'brand' => ['@type' => 'Brand', 'name' => $product['brand']],
    'description' => Str::excerpt((string) $product['short_description'], 300),
    'image' => array_map(static fn ($img) => absolute_url($img['path']), $images),
    'url' => absolute_url($product['url']),
];
if ($availability['key'] !== 'quote') {
    $productLd['offers'] = [
        '@type' => 'Offer',
        'url' => absolute_url($product['url']),
        'priceCurrency' => 'USD',
        'price' => Money::fromCents($price['retail_cents']),
        'availability' => $availability['key'] === 'in_stock' || $availability['key'] === 'low_stock'
            ? 'https://schema.org/InStock'
            : ($availability['key'] === 'preorder' ? 'https://schema.org/PreOrder' : 'https://schema.org/OutOfStock'),
        'seller' => ['@type' => 'Organization', 'name' => 'Thunder Mounts'],
    ];
}
if ((int) $stats['total'] > 0) {
    $productLd['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => (string) $stats['average'],
        'reviewCount' => (int) $stats['total'],
    ];
}
?>
<script type="application/ld+json"><?= json_encode($productLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [
        ['label' => 'Shop', 'url' => '/shop'],
        ['label' => $product['category_name'], 'url' => '/shop?category=' . urlencode($product['category_slug'])],
        ['label' => $product['name']],
    ]]) ?>
  </div>
</div>

<section class="section-tight" style="padding-top:0">
  <div class="container">
    <div class="pdp">
      <div class="gallery">
        <div class="main">
          <img id="pdp-main" src="<?= e(upload_url($images[0]['path'])) ?>" alt="<?= e($images[0]['alt'] ?? $product['name']) ?>" width="900" height="900">
        </div>
        <?php if (count($images) > 1): ?>
          <div class="thumbs" role="group" aria-label="Product images">
            <?php foreach ($images as $index => $image): ?>
              <button type="button" class="<?= $index === 0 ? 'active' : '' ?>" data-gallery-thumb data-src="<?= e(upload_url($image['path'])) ?>" data-alt="<?= e($image['alt'] ?? $product['name']) ?>" aria-label="View image <?= $index + 1 ?>">
                <img src="<?= e(upload_url($image['path'])) ?>" alt="" loading="lazy" width="160" height="160">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="pdp-info">
        <span class="eyebrow"><?= e($product['category_name']) ?></span>
        <h1><?= e($product['name']) ?></h1>
        <p class="muted" style="margin-bottom:12px">
          Model <strong><?= e($product['model']) ?></strong> · SKU <?= e($product['sku']) ?>
          <?php if ((int) $stats['total'] > 0): ?>
            · <a href="#reviews"><?= star_rating((float) $stats['average']) ?> <span class="small">(<?= (int) $stats['total'] ?>)</span></a>
          <?php endif; ?>
        </p>

        <div class="mb-2">
          <?php if ($availability['key'] === 'quote'): ?>
            <span class="price">Request a quote</span>
          <?php else: ?>
            <span class="price <?= $price['is_dealer'] ? 'price-dealer' : '' ?>" style="font-size:1.9rem"><?= e(money($price['unit_cents'])) ?></span>
            <?php if ($price['is_dealer'] && $price['unit_cents'] < $price['retail_cents']): ?>
              <span class="price-old"><?= e(money($price['retail_cents'])) ?> retail</span>
              <span class="badge badge-green" style="margin-left:8px">Your dealer price</span>
            <?php elseif ($price['on_sale']): ?>
              <span class="price-old"><?= e(money($price['list_cents'])) ?></span>
              <span class="badge badge-red">Save <?= e(money($price['list_cents'] - $price['unit_cents'])) ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php if ($price['is_dealer'] && $price['unit_cents'] >= $price['retail_cents']): ?>
          <p class="small muted">Dealer account pricing is applied at checkout based on your assigned tier.</p>
        <?php endif; ?>
        <span class="stock <?= $availabilityClass ?>"><i class="dot"></i><?= e($availability['label']) ?><?= $availability['key'] === 'low_stock' ? ' — ' . e($availability['detail']) : '' ?></span>

        <p class="mt-2" style="font-size:1.02rem"><?= e($product['short_description']) ?></p>

        <?php if ($canBuy): ?>
          <form action="<?= url('/cart/add') ?>" method="post" data-add-to-cart class="buy-row" style="align-items:stretch">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <div class="qty" data-qty>
              <button type="button" data-qty-dec aria-label="Decrease quantity">−</button>
              <label class="sr-only" for="pdp-qty">Quantity</label>
              <input id="pdp-qty" type="number" name="qty" value="1" min="1" max="99" inputmode="numeric">
              <button type="button" data-qty-inc aria-label="Increase quantity">+</button>
            </div>
            <button class="btn btn-primary btn-lg" type="submit"<?= $price['is_dealer'] ? '' : '' ?>>Add to Cart</button>
          </form>
        <?php elseif ($availability['key'] === 'preorder'): ?>
          <div class="notice notice-info mt-2" id="notify">
            <?= icon('bell') ?>
            <div>
              <strong>Coming soon.</strong> Join the notification list and we will email you when this product is available to order.
            </div>
          </div>
          <form action="<?= url('/product/' . $product['slug'] . '/notify') ?>" method="post" class="flex wrap mt-2" style="max-width:480px">
            <?= csrf_field() ?>
            <label class="sr-only" for="notify-email">Email address</label>
            <input class="input" id="notify-email" type="email" name="email" placeholder="you@example.com" required value="<?= e(auth()['email'] ?? '') ?>" style="flex:1;min-width:200px">
            <button class="btn btn-dark" type="submit">Notify me</button>
          </form>
        <?php else: ?>
          <div class="buy-row">
            <a class="btn btn-primary btn-lg" href="<?= url('/contact?subject=' . urlencode('Quote request: ' . $product['name'])) ?>">Request a quote</a>
          </div>
        <?php endif; ?>

        <div class="trust-row">
          <div><?= icon('truck') ?> Ships from California</div>
          <div><?= icon('lock') ?> Secure PayPal checkout</div>
          <div><?= icon('download') ?> Online instructions</div>
        </div>

        <div class="divider"></div>
        <div class="small"><strong>Compatibility:</strong> <span class="muted"><?= e($product['compatibility']) ?></span></div>
        <div class="small mt-1"><strong>Shipping:</strong> <span class="muted">Calculated at checkout via UPS from California. Sales tax calculated where applicable.</span></div>
        <?php if ($price['is_dealer']): ?>
          <div class="small mt-1"><strong>Dealer pricing:</strong> <span class="muted">Shown for your approved dealer account. Sign in to keep it applied.</span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="tabs" role="tablist" aria-label="Product information">
      <button type="button" class="active" role="tab" aria-selected="true" aria-controls="tab-desc" id="tabbtn-desc" data-tab="desc">Description</button>
      <button type="button" role="tab" aria-selected="false" aria-controls="tab-specs" id="tabbtn-specs" data-tab="specs">Specifications</button>
      <button type="button" role="tab" aria-selected="false" aria-controls="tab-box" id="tabbtn-box" data-tab="box">What's in the box</button>
      <button type="button" role="tab" aria-selected="false" aria-controls="tab-ship" id="tabbtn-ship" data-tab="ship">Shipping &amp; returns</button>
      <button type="button" role="tab" aria-selected="false" aria-controls="tab-reviews" id="tabbtn-reviews" data-tab="reviews">Reviews (<?= (int) $stats['total'] ?>)</button>
    </div>

    <div class="tab-panel active" id="tab-desc" data-panel="desc" role="tabpanel" aria-labelledby="tabbtn-desc" style="max-width:860px;font-size:1.02rem">
      <p><?= e($product['description']) ?></p>
      <?php if (!empty($product['material']) || !empty($product['finish'])): ?>
        <p class="small muted"><?= e(trim(($product['material'] ?? '') . ($product['finish'] ? ' · ' . $product['finish'] : ''))) ?></p>
      <?php endif; ?>
    </div>

    <div class="tab-panel" id="tab-specs" data-panel="specs" role="tabpanel" aria-labelledby="tabbtn-specs" style="max-width:860px">
      <table class="spec-list">
        <?php foreach ($product['specs_array'] as $label => $value): ?>
          <tr><td><?= e($label) ?></td><td><?= e($value) ?></td></tr>
        <?php endforeach; ?>
        <tr><td>Brand</td><td><?= e($product['brand']) ?></td></tr>
        <tr><td>Model</td><td><?= e($product['model']) ?></td></tr>
        <tr><td>SKU</td><td><?= e($product['sku']) ?></td></tr>
      </table>
      <p class="small muted mt-2">Specifications are as listed for this model. Contact us if you need a dimension or load rating confirmed before ordering.</p>
    </div>

    <div class="tab-panel" id="tab-box" data-panel="box" role="tabpanel" aria-labelledby="tabbtn-box" style="max-width:860px">
      <ul class="checklist">
        <?php foreach ($product['box_array'] as $item): ?>
          <li><?= e($item) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="small muted">Openers and doors are not included with mounts.</p>
    </div>

    <div class="tab-panel" id="tab-ship" data-panel="ship" role="tabpanel" aria-labelledby="tabbtn-ship" style="max-width:860px">
      <p><strong>Shipping:</strong> Orders ship from California via UPS. Shipping is calculated at checkout based on your address and the method you choose. Prices exclude sales tax.</p>
      <p><strong>Returns:</strong> Contact us before returning anything so we can help and confirm the applicable terms. Call <a href="tel:8053386680">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com">thunder@thundermounts.com</a>. <a class="bold red" href="<?= url('/page/returns') ?>">Full returns information →</a></p>
    </div>

    <div class="tab-panel" id="tab-reviews" data-panel="reviews" role="tabpanel" aria-labelledby="tabbtn-reviews">
      <?= \App\Core\View::partial('storefront/partials/reviews', ['reviews' => $reviews, 'stats' => $stats, 'product' => $product]) ?>
    </div>
  </div>
</section>

<?php if ($related !== []): ?>
<section class="section section-gray">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">You may also need</span>
        <h2>Related products</h2>
      </div>
      <a class="btn btn-outline" href="<?= url('/shop?category=' . urlencode($product['category_slug'])) ?>">More in <?= e($product['category_name']) ?></a>
    </div>
    <div class="product-grid cols-4">
      <?php foreach ($related as $item): ?>
        <?= \App\Core\View::partial('storefront/partials/product-card', ['product' => $item]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
