<?php
/** @var array $result */
/** @var array $categories */
$filters = $result['filters'];
$activeCategory = null;
foreach ($categories as $category) {
    if ($category['slug'] === $filters['category']) {
        $activeCategory = $category;
        break;
    }
}
$heading = $activeCategory ? $activeCategory['name'] : ($filters['q'] !== '' ? 'Search results' : 'All products');
$remove = static fn (string $key) => url(current_path() . query_string([], [$key, 'page']));
$availLabels = ['in_stock' => 'In stock', 'preorder' => 'Coming soon', 'quote' => 'Quote only'];
?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => $activeCategory ? 'Shop' : 'Shop', 'url' => '/shop'], ['label' => $heading]]]) ?>
    <div class="section-head" style="margin-bottom:0">
      <div>
        <span class="eyebrow"><?= $activeCategory ? 'Category' : 'Thunder Mounts catalog' ?></span>
        <h1 id="shop-title" style="margin-bottom:6px"><?= e($heading) ?></h1>
        <p class="muted" style="max-width:640px;margin:0">
          <?= e($activeCategory['description'] ?? 'Engineered mounts for garage doors and openers, plus openers, doors and accessories — with the specifications you need before you buy.') ?>
        </p>
      </div>
      <span class="muted small" id="shop-count" aria-live="polite"><?= (int) $result['total'] ?> product<?= (int) $result['total'] === 1 ? '' : 's' ?></span>
    </div>
  </div>
</div>

<section class="section-tight">
  <div class="container shop-layout">
    <button class="btn btn-outline btn-sm filters-toggle" type="button" data-filters-toggle aria-expanded="false" aria-controls="shop-filters"><?= icon('grid') ?> Filters &amp; categories</button>

    <aside class="filters" id="shop-filters" aria-label="Product filters">
      <form method="get" action="<?= url('/shop') ?>">
        <div class="field">
          <label for="filter-q">Search</label>
          <div class="search-box">
            <?= icon('search') ?>
            <input class="input" id="filter-q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, model or SKU">
          </div>
        </div>

        <?php if ($filters['category'] !== ''): ?><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><?php endif; ?>
        <?php if ($filters['length'] !== ''): ?><input type="hidden" name="length" value="<?= e($filters['length']) ?>"><?php endif; ?>
        <?php if ($filters['availability'] !== ''): ?><input type="hidden" name="availability" value="<?= e($filters['availability']) ?>"><?php endif; ?>
        <?php if ($filters['sort'] !== ''): ?><input type="hidden" name="sort" value="<?= e($filters['sort']) ?>"><?php endif; ?>

        <div class="filter-group">
          <h4>Category</h4>
          <div class="flist">
            <a class="<?= $filters['category'] === '' ? 'active' : '' ?>" href="<?= e(url('/shop' . query_string([], ['category', 'page']))) ?>">All products <span><?= array_sum(array_map(static fn ($c) => (int) $c['product_count'], $categories)) ?></span></a>
            <?php foreach ($categories as $category): ?>
              <a class="<?= $filters['category'] === $category['slug'] ? 'active' : '' ?>" href="<?= e(url('/shop' . query_string(['category' => $category['slug']], ['page']))) ?>"><?= e($category['name']) ?> <span><?= (int) $category['product_count'] ?></span></a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="filter-group">
          <h4>Availability</h4>
          <div class="flist">
            <a class="<?= $filters['availability'] === '' ? 'active' : '' ?>" href="<?= e(url('/shop' . query_string([], ['availability', 'page']))) ?>">Any</a>
            <?php foreach ($availLabels as $value => $label): ?>
              <a class="<?= $filters['availability'] === $value ? 'active' : '' ?>" href="<?= e(url('/shop' . query_string(['availability' => $value], ['page']))) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="filter-group">
          <h4>Maximum length</h4>
          <div class="flex wrap">
            <?php foreach (['12', '19', '24', '29'] as $length): ?>
              <?php $isOn = $filters['length'] === $length; ?>
              <a class="chip <?= $isOn ? 'active' : '' ?>" href="<?= e(url('/shop' . query_string($isOn ? ['length' => null] : ['length' => $length], ['page']))) ?>" aria-pressed="<?= $isOn ? 'true' : 'false' ?>"><?= $length ?> in.</a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="filter-group">
          <h4>Price</h4>
          <div class="form-row" style="gap:0 8px">
            <div class="field"><label class="sr-only" for="min_price">Minimum price</label><input class="input" id="min_price" name="min_price" type="number" min="0" step="1" placeholder="Min" value="<?= e($filters['min_price']) ?>"></div>
            <div class="field"><label class="sr-only" for="max_price">Maximum price</label><input class="input" id="max_price" name="max_price" type="number" min="0" step="1" placeholder="Max" value="<?= e($filters['max_price']) ?>"></div>
          </div>
        </div>

        <button class="btn btn-dark btn-block" type="submit">Apply filters</button>
        <?php if ($filters['q'] !== '' || $filters['category'] !== '' || $filters['length'] !== '' || $filters['availability'] !== '' || $filters['min_price'] !== null): ?>
          <a class="btn btn-ghost btn-block btn-sm" href="<?= url('/shop') ?>">Clear all filters</a>
        <?php endif; ?>
      </form>

      <div class="panel mt-3 small">
        <strong>Need help choosing?</strong>
        <p class="muted mb-0">Measure ceiling to opener bolts or track bolt holes. Read the <a href="<?= url('/size-guide') ?>" class="bold red">sizing guide</a> or call <a href="tel:8053386680" class="bold">805-338-6680</a>.</p>
      </div>
    </aside>

    <div>
      <div class="toolbar">
        <div class="flex wrap small muted">
          <?php if ($filters['category'] !== ''): ?>
            <a class="filter-chip" href="<?= e($remove('category')) ?>"><?= e($activeCategory['name'] ?? 'Category') ?> <span aria-hidden="true">×</span><span class="sr-only">Remove filter</span></a>
          <?php endif; ?>
          <?php if ($filters['q'] !== ''): ?>
            <a class="filter-chip" href="<?= e($remove('q')) ?>">“<?= e($filters['q']) ?>” <span aria-hidden="true">×</span><span class="sr-only">Remove search, <?= e($filters['q']) ?></span></a>
          <?php endif; ?>
          <?php if ($filters['length'] !== ''): ?>
            <a class="filter-chip" href="<?= e($remove('length')) ?>">Max <?= e($filters['length']) ?> in. <span aria-hidden="true">×</span><span class="sr-only">Remove length filter</span></a>
          <?php endif; ?>
          <?php if ($filters['availability'] !== ''): ?>
            <a class="filter-chip" href="<?= e($remove('availability')) ?>"><?= e($availLabels[$filters['availability']] ?? $filters['availability']) ?> <span aria-hidden="true">×</span><span class="sr-only">Remove availability filter</span></a>
          <?php endif; ?>
        </div>
        <form method="get" action="<?= url('/shop') ?>" class="sort-form">
          <?php foreach (['q', 'category', 'length', 'availability', 'min_price', 'max_price'] as $key): ?>
            <?php if (!empty($filters[$key])): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($filters[$key]) ?>"><?php endif; ?>
          <?php endforeach; ?>
          <label class="small muted" for="sort">Sort</label>
          <select class="select" id="sort" name="sort" data-auto-submit>
            <option value="featured"<?= selected('featured', $filters['sort']) ?>>Featured</option>
            <option value="price-asc"<?= selected('price-asc', $filters['sort']) ?>>Price: low to high</option>
            <option value="price-desc"<?= selected('price-desc', $filters['sort']) ?>>Price: high to low</option>
            <option value="name-asc"<?= selected('name-asc', $filters['sort']) ?>>Name: A–Z</option>
            <option value="name-desc"<?= selected('name-desc', $filters['sort']) ?>>Name: Z–A</option>
            <option value="newest"<?= selected('newest', $filters['sort']) ?>>Newest</option>
          </select>
          <noscript><button class="btn btn-outline btn-sm" type="submit">Sort</button></noscript>
        </form>
      </div>

      <?php if ($result['items'] === []): ?>
        <div class="empty card card-body">
          <?= icon('search') ?>
          <h4>No products match these filters</h4>
          <p>Try a different category, clear the search, or call us and we will help you find the right mount.</p>
          <a class="btn btn-outline btn-sm" href="<?= url('/shop') ?>">Clear filters</a>
        </div>
      <?php else: ?>
        <div class="product-grid" id="shop-grid">
          <?php foreach ($result['items'] as $product): ?>
            <?= \App\Core\View::partial('storefront/partials/product-card', ['product' => $product]) ?>
          <?php endforeach; ?>
        </div>
        <div class="mt-3">
          <?= \App\Core\View::partial('storefront/partials/pagination', ['paginator' => $result['paginator']]) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
