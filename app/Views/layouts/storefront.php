<?php
/** @var string $content */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Settings;

$storeName = setting('store.name', 'Thunder Mounts');
$tagline = setting('store.tagline', 'Value. Symmetry. Safety. VSS™');
$phone = setting('store.phone', '805-338-6680');
$email = setting('store.email', 'thunder@thundermounts.com');
$address = setting('store.address', '4212 E Los Angeles Ave, Simi Valley, CA 93063');
$hours = setting('store.hours', '');
$categories = \App\Services\CatalogService::instance()->categories();
$cartCount = cart()->count();
$cartSummary = cart()->summary();
$title = $pageTitle ?? setting('seo.title', $storeName . ' | Garage Door & Opener Mounting Systems');
if ($title && !str_contains((string) $title, $storeName)) {
    $title .= ' | ' . $storeName;
}
$metaDescription = $metaDescription ?? setting('seo.description', '');
$canonical = $canonical ?? absolute_url(current_path());
$ogImage = absolute_url($ogImage ?? (string) setting('seo.og_image', '/assets/img/hero-garage.jpg'));
$user = $currentUser ?? Auth::user();
$announcement = setting('store.announcement', '');
$announcementEnabled = (string) setting('store.announcement_enabled', '1') === '1';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta name="robots" content="<?= e($robots ?? 'index,follow') ?>">
  <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
  <meta property="og:site_name" content="<?= e($storeName) ?>">
  <meta property="og:title" content="<?= e($ogTitle ?? $title) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#C82026">
  <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <script>
    window.TM = {
      base: <?= json_encode(url('/')) ?>,
      csrf: <?= json_encode(Csrf::token()) ?>,
      cartCount: <?= (int) $cartCount ?>,
      currency: 'USD'
    };
  </script>
  <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $storeName,
        'legalName' => setting('store.legal_name', 'Thunder Mount Garage Systems LLC'),
        'url' => absolute_url('/'),
        'image' => $ogImage,
        'telephone' => '+' . preg_replace('/\D+/', '', $phone),
        'email' => $email,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '4212 E Los Angeles Ave',
            'addressLocality' => 'Simi Valley',
            'addressRegion' => 'CA',
            'postalCode' => '93063',
            'addressCountry' => 'US',
        ],
        'openingHoursSpecification' => [
            ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'], 'opens' => '09:00', 'closes' => '16:00'],
            ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '10:00', 'closes' => '14:00'],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
  </script>
  <?= $headExtra ?? '' ?>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <?php if ($announcementEnabled && $announcement !== ''): ?>
    <div class="announce" id="announce-bar">
      <div class="container">
        <?= e($announcement) ?>
        <?php if (setting('store.announcement_link')): ?><a href="<?= e(url((string) setting('store.announcement_link'))) ?>">Learn more</a><?php endif; ?>
        <button class="announce-close" type="button" aria-label="Dismiss announcement" data-announce-close>&times;</button>
      </div>
    </div>
  <?php endif; ?>

  <header class="site-header">
    <div class="container inner">
      <a class="brand tm-brand" href="<?= url('/') ?>" aria-label="Thunder Mounts home">
        <span class="tm-bolt" aria-hidden="true">
          <svg viewBox="0 0 40 48" fill="none"><path d="M24 2L4 28h12l-2 18 20-26H22l2-18z" fill="#C82026"/><path d="M24 2L4 28h12l-2 18 20-26H22l2-18z" stroke="#181818" stroke-width="1.5"/></svg>
        </span>
        <span class="tm-wordmark">THUNDER<span>MOUNTS<small>Garage Systems</small></span></span>
      </a>

      <nav class="nav-main" aria-label="Main navigation">
        <div class="has-drop">
          <a href="<?= url('/shop') ?>" class="<?= is_active('/shop') ?>">Shop <span aria-hidden="true">▾</span></a>
          <div class="drop">
            <a href="<?= url('/shop') ?>">All products</a>
            <?php foreach ($categories as $category): ?>
              <a href="<?= url('/shop?category=' . urlencode($category['slug'])) ?>"><?= e($category['name']) ?> <span class="muted small">(<?= (int) $category['product_count'] ?>)</span></a>
            <?php endforeach; ?>
          </div>
        </div>
        <a href="<?= url('/dealer-program') ?>" class="<?= is_active('/dealer') ?>">Dealer Program</a>
        <a href="<?= url('/page/installation') ?>" class="<?= is_active('/page/installation') ?>">Installation</a>
        <a href="<?= url('/page/about') ?>" class="<?= is_active('/page/about') ?>">About</a>
        <a href="<?= url('/contact') ?>" class="<?= is_active('/contact') ?>">Contact</a>
      </nav>

      <div class="header-actions">
        <a class="btn btn-ghost btn-sm header-phone" href="tel:<?= e(preg_replace('/\D+/', '', $phone)) ?>"><?= icon('phone') ?> <span><?= e($phone) ?></span></a>
        <button class="icon-btn" type="button" data-open-search aria-label="Search products"><?= icon('search') ?></button>
        <a class="icon-btn account-link" href="<?= $user ? url('/account') : url('/login') ?>" aria-label="<?= $user ? 'Your account' : 'Sign in' ?>"><?= icon('user') ?></a>
        <button class="icon-btn" type="button" data-open-cart aria-label="Open cart" aria-controls="cart-drawer">
          <?= icon('cart') ?>
          <span class="count-badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= (int) $cartCount ?></span>
        </button>
        <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false"><?= icon('menu') ?></button>
      </div>
    </div>
    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
      <a href="<?= url('/shop') ?>">Shop all</a>
      <?php foreach ($categories as $category): ?>
        <a href="<?= url('/shop?category=' . urlencode($category['slug'])) ?>" class="muted">↳ <?= e($category['name']) ?></a>
      <?php endforeach; ?>
      <a href="<?= url('/dealer-program') ?>">Dealer Program</a>
      <a href="<?= url('/page/installation') ?>">Installation &amp; sizing</a>
      <a href="<?= url('/page/about') ?>">About</a>
      <a href="<?= url('/contact') ?>">Contact</a>
      <a href="<?= $user ? url('/account') : url('/login') ?>"><?= $user ? 'My account' : 'Sign in / register' ?></a>
      <div class="mobile-cta">
        <a class="btn btn-primary" href="<?= url('/shop') ?>">Shop Products</a>
        <a class="btn btn-outline" href="<?= url('/dealer/apply') ?>">Become a Dealer</a>
      </div>
    </nav>
  </header>

  <?php if (!empty($flashMessages)): ?>
    <div class="container flash-stack" role="status" aria-live="polite">
      <?php foreach ($flashMessages as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>">
          <?= icon($flash['type'] === 'success' ? 'check' : ($flash['type'] === 'error' ? 'bell' : 'shield')) ?>
          <span><?= e($flash['message']) ?></span>
          <button type="button" class="flash-close" aria-label="Dismiss" data-flash-close>&times;</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <main id="main"><?= $content ?></main>

  <footer class="site-footer">
    <div class="container">
      <?php if ((string) setting('store.newsletter_enabled', '1') === '1'): ?>
        <div class="newsletter">
          <div>
            <h2 style="color:#fff;margin-bottom:6px">Product news and installer resources.</h2>
            <p style="color:#C9CED4;margin:0">Occasional updates about new products, installation guidance and the dealer program. No spam.</p>
          </div>
          <form data-newsletter-form novalidate>
            <label class="sr-only" for="newsletter-email">Email address</label>
            <input class="input" id="newsletter-email" name="email" type="email" placeholder="you@company.com" required autocomplete="email">
            <button class="btn btn-primary" type="submit">Subscribe</button>
          </form>
        </div>
      <?php endif; ?>

      <div class="cols">
        <div class="footer-brand">
          <a class="brand tm-brand tm-brand-light" href="<?= url('/') ?>" aria-label="Thunder Mounts home">
            <span class="tm-bolt" aria-hidden="true"><svg viewBox="0 0 40 48" fill="none"><path d="M24 2L4 28h12l-2 18 20-26H22l2-18z" fill="#C82026"/></svg></span>
            <span class="tm-wordmark">THUNDER<span>MOUNTS<small>Garage Systems</small></span></span>
          </a>
          <p style="margin-top:16px"><?= e($tagline) ?></p>
          <p class="small">
            <a href="tel:<?= e(preg_replace('/\D+/', '', $phone)) ?>" style="display:inline;color:#fff;font-weight:700"><?= e($phone) ?></a><br>
            <a href="mailto:<?= e($email) ?>" style="display:inline"><?= e($email) ?></a><br>
            <?= e($address) ?><br>
            <span class="muted" style="color:var(--steel)"><?= e($hours) ?></span>
          </p>
        </div>
        <div>
          <h4>Shop</h4>
          <?php foreach ($categories as $category): ?>
            <a href="<?= url('/shop?category=' . urlencode($category['slug'])) ?>"><?= e($category['name']) ?></a>
          <?php endforeach; ?>
          <a href="<?= url('/shop') ?>">All products</a>
        </div>
        <div>
          <h4>Company</h4>
          <a href="<?= url('/page/about') ?>">About Thunder Mounts</a>
          <a href="<?= url('/dealer-program') ?>">Dealer program</a>
          <a href="<?= url('/page/installation') ?>">Installation &amp; sizing</a>
          <a href="<?= url('/page/faq') ?>">FAQ</a>
          <a href="<?= url('/contact') ?>">Contact</a>
        </div>
        <div>
          <h4>Support</h4>
          <?php foreach (($footerPages ?? []) as $page): ?>
            <a href="<?= url('/page/' . urlencode($page['slug'])) ?>"><?= e(html_entity_decode($page['title'])) ?></a>
          <?php endforeach; ?>
          <a href="<?= url('/order-lookup') ?>">Track an order</a>
          <a href="<?= $user ? url('/account') : url('/login') ?>"><?= $user ? 'My account' : 'Sign in' ?></a>
          <a href="<?= url('/dealer/login') ?>">Dealer login</a>
        </div>
      </div>

      <div class="legal">
        <span>&copy; <?= date('Y') ?> <?= e(setting('store.legal_name', 'Thunder Mount Garage Systems LLC')) ?>. All rights reserved.</span>
        <span>
          <a href="<?= url('/page/privacy') ?>">Privacy</a>
          <a href="<?= url('/page/terms') ?>">Terms</a>
          <a href="<?= url('/sitemap.xml') ?>">Sitemap</a>
        </span>
      </div>
    </div>
  </footer>

  <!-- Cart drawer -->
  <div class="drawer-wrap" id="cart-drawer" aria-hidden="true">
    <div class="backdrop" data-close-cart></div>
    <aside class="drawer" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title">
      <div class="drawer-head">
        <h3 id="cart-drawer-title" style="margin:0">Your cart <span class="muted small" data-cart-count-label>(<?= (int) $cartCount ?>)</span></h3>
        <button class="close-x" type="button" data-close-cart aria-label="Close cart">&times;</button>
      </div>
      <div class="drawer-body" data-cart-drawer-body>
        <?php if ($cartSummary['items'] === []): ?>
          <div class="empty">
            <?= icon('cart') ?>
            <h4>Your cart is empty</h4>
            <p class="small">Browse mounts, openers and accessories.</p>
            <a class="btn btn-primary btn-sm" href="<?= url('/shop') ?>">Shop products</a>
          </div>
        <?php else: ?>
          <?php foreach ($cartSummary['items'] as $item): ?>
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
      </div>
      <div class="drawer-foot">
        <div class="totals-row"><span>Subtotal</span><strong data-cart-subtotal><?= e(money($cartSummary['subtotal_cents'])) ?></strong></div>
        <p class="tiny muted" style="margin:6px 0 12px">Shipping and tax are calculated at checkout.</p>
        <a class="btn btn-primary btn-block" href="<?= url('/cart') ?>">View cart &amp; checkout</a>
        <a class="btn btn-ghost btn-block btn-sm" href="<?= url('/shop') ?>" data-close-cart>Continue shopping</a>
      </div>
    </aside>
  </div>

  <!-- Search dialog -->
  <div class="modal-wrap" id="search-modal" aria-hidden="true">
    <div class="modal-backdrop" data-close-search></div>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="search-title">
      <div class="modal-head">
        <h3 id="search-title" style="margin:0">Search products</h3>
        <button class="close-x" type="button" data-close-search aria-label="Close search">&times;</button>
      </div>
      <div class="modal-body">
        <form action="<?= url('/shop') ?>" method="get" role="search" class="search-box">
          <label class="sr-only" for="site-search">Search by name, model or SKU</label>
          <?= icon('search') ?>
          <input class="input" id="site-search" name="q" type="search" placeholder="Search by name, model or SKU…" autocomplete="off" data-search-input>
        </form>
        <div class="mt-2" data-search-results aria-live="polite"></div>
        <p class="tiny muted mt-2">Try “TMS-12”, “track mount” or “LiftMaster”.</p>
      </div>
    </div>
  </div>

  <div class="toast-wrap" data-toast-wrap aria-live="polite"></div>
  <script src="<?= asset('js/app.js') ?>" defer></script>
  <?= $scripts ?? '' ?>
</body>
</html>
