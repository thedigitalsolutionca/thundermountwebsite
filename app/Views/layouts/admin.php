<?php
/** @var string $content */
use App\Core\Auth;
use App\Core\Csrf;

$admin = Auth::user();
$nav = [
    ['label' => 'Dashboard', 'url' => '/admin', 'icon' => 'grid', 'match' => '/admin'],
    ['label' => 'Orders', 'url' => '/admin/orders', 'icon' => 'package', 'match' => '/admin/orders'],
    ['label' => 'Products', 'url' => '/admin/products', 'icon' => 'tag', 'match' => '/admin/products'],
    ['label' => 'Categories', 'url' => '/admin/categories', 'icon' => 'grid', 'match' => '/admin/categories'],
    ['label' => 'Inventory', 'url' => '/admin/inventory', 'icon' => 'box', 'match' => '/admin/inventory'],
    ['label' => 'Customers', 'url' => '/admin/customers', 'icon' => 'users', 'match' => '/admin/customers'],
    ['label' => 'Dealers', 'url' => '/admin/dealers', 'icon' => 'shield', 'match' => '/admin/dealers'],
    ['label' => 'Discounts', 'url' => '/admin/discounts', 'icon' => 'percent', 'match' => '/admin/discounts'],
    ['label' => 'Reviews', 'url' => '/admin/reviews', 'icon' => 'star', 'match' => '/admin/reviews'],
    ['label' => 'Messages', 'url' => '/admin/messages', 'icon' => 'mail', 'match' => '/admin/messages'],
    ['label' => 'Pages', 'url' => '/admin/pages', 'icon' => 'file-text', 'match' => '/admin/pages'],
    ['label' => 'Shipping', 'url' => '/admin/shipping', 'icon' => 'truck', 'match' => '/admin/shipping'],
    ['label' => 'Tax', 'url' => '/admin/tax', 'icon' => 'percent', 'match' => '/admin/tax'],
    ['label' => 'Settings', 'url' => '/admin/settings', 'icon' => 'settings', 'match' => '/admin/settings'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Admin') ?> · <?= e(setting('store.name', 'Thunder Mounts')) ?> Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <script>window.TM = {base: <?= json_encode(url('/')) ?>, csrf: <?= json_encode(Csrf::token()) ?>};</script>
</head>
<body class="admin-body">
  <div class="admin-shell">
    <aside class="admin-side" id="admin-side">
      <div class="admin-brand">
        <a class="brand tm-brand tm-brand-light" href="<?= url('/admin') ?>">
          <span class="tm-bolt" aria-hidden="true"><svg viewBox="0 0 40 48" fill="none"><path d="M24 2L4 28h12l-2 18 20-26H22l2-18z" fill="#C82026"/></svg></span>
          <span class="tm-wordmark">THUNDER<span>MOUNTS<small>Admin</small></span></span>
        </a>
        <button class="admin-side-close" type="button" data-admin-menu aria-label="Close menu">&times;</button>
      </div>
      <nav class="admin-nav" aria-label="Admin navigation">
        <?php foreach ($nav as $item): ?>
          <?php $active = $item['label'] === 'Dashboard' ? current_path() === '/admin' : is_active($item['url']); ?>
          <a href="<?= url($item['url']) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($item['icon']) ?><span><?= e($item['label']) ?></span></a>
        <?php endforeach; ?>
      </nav>
      <div class="admin-side-foot">
        <a href="<?= url('/') ?>" target="_blank" rel="noopener"><?= icon('external') ?> View store</a>
        <form action="<?= url('/admin/logout') ?>" method="post">
          <?= csrf_field() ?>
          <button type="submit"><?= icon('logout') ?> Sign out</button>
        </form>
      </div>
    </aside>

    <div class="admin-main">
      <header class="admin-top">
        <div class="flex" style="gap:10px;align-items:center">
          <button class="admin-menu-btn" type="button" data-admin-menu aria-label="Open menu"><?= icon('menu') ?></button>
          <div>
            <h1><?= e($pageTitle ?? 'Admin') ?></h1>
            <span class="admin-crumb">Thunder Mounts admin</span>
          </div>
        </div>
        <div class="flex" style="gap:12px;align-items:center">
          <a class="btn btn-outline btn-sm" href="<?= url('/') ?>" target="_blank" rel="noopener"><?= icon('external') ?> <span class="hide-sm">Store</span></a>
          <span class="admin-user" title="<?= e($admin['email'] ?? '') ?>"><?= e(trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''))) ?: 'Admin' ?></span>
        </div>
      </header>

      <?php $flashes = $flashMessages ?? []; ?>
      <?php if ($flashes !== []): ?>
        <div class="admin-flash" role="status" aria-live="polite">
          <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>">
              <?= icon($flash['type'] === 'success' ? 'check' : 'bell') ?>
              <span><?= e($flash['message']) ?></span>
              <button type="button" class="flash-close" aria-label="Dismiss" data-flash-close>&times;</button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <main class="admin-content"><?= $content ?></main>
    </div>
  </div>
  <div class="admin-backdrop" data-admin-menu hidden></div>
  <script src="<?= asset('js/app.js') ?>" defer></script>
  <script src="<?= asset('js/admin.js') ?>" defer></script>
  <?= $scripts ?? '' ?>
</body>
</html>
