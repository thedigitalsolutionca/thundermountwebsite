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
    ['label' => 'Gallery & Portfolio', 'url' => '/admin/gallery', 'icon' => 'image', 'match' => '/admin/gallery'],
    ['label' => 'Pages', 'url' => '/admin/pages', 'icon' => 'file-text', 'match' => '/admin/pages'],
    ['label' => 'Shipping', 'url' => '/admin/shipping', 'icon' => 'truck', 'match' => '/admin/shipping'],
    ['label' => 'Tax', 'url' => '/admin/tax', 'icon' => 'percent', 'match' => '/admin/tax'],
    ['label' => 'Email delivery', 'url' => '/admin/emails', 'icon' => 'mail', 'match' => '/admin/emails'],
    ['label' => 'Data management', 'url' => '/admin/data', 'icon' => 'settings', 'match' => '/admin/data'],
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
        <a class="brand tm-brand tm-brand-light" href="<?= url('/admin') ?>" aria-label="Thunder Mounts admin">
          <img class="brand-logo brand-logo-pill" src="<?= asset('img/thundermounts-logo.jpg') ?>" alt="Thunder Mounts — Garage Systems" width="705" height="173">
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

      <?php $flashes = array_filter($flashMessages ?? [], 'is_array'); ?>
      <?php if ($flashes !== []): ?>
      <div class="toast-wrap admin-flash" data-toast-wrap role="status" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
          <?php $flashType = in_array($flash['type'] ?? '', ['success', 'error', 'info'], true) ? $flash['type'] : 'info'; ?>
          <div class="toast flash flash-<?= e($flashType) ?>" role="<?= $flashType === 'error' ? 'alert' : 'status' ?>" data-flash-auto>
            <?= icon($flashType === 'success' ? 'check' : ($flashType === 'error' ? 'bell' : 'shield')) ?>
            <div class="flash-copy">
              <strong><?= $flashType === 'success' ? 'Success' : ($flashType === 'error' ? 'Please check' : 'Notice') ?></strong>
              <span><?= e($flash['message'] ?? '') ?></span>
            </div>
            <button type="button" class="flash-close" aria-label="Dismiss" data-flash-close>&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <main class="admin-content"><?= $content ?></main>
    </div>
  </div>
  <div class="admin-backdrop" data-admin-menu hidden></div>

  <!-- Confirm dialog (replaces native confirm()) -->
  <div class="modal-wrap" id="confirm-modal" aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message">
    <div class="modal-backdrop" data-close-confirm></div>
    <div class="modal modal-confirm">
      <div class="modal-head">
        <h3 id="confirm-modal-title" style="margin:0">Please confirm</h3>
        <button class="close-x" type="button" data-close-confirm aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <p id="confirm-modal-message" class="confirm-message" style="margin:0"></p>
      </div>
      <div class="modal-foot">
        <button class="btn btn-outline" type="button" data-close-confirm>Cancel</button>
        <button class="btn btn-danger confirm-accept" type="button" data-confirm-accept>Confirm</button>
      </div>
    </div>
  </div>

  <script src="<?= asset('js/app.js') ?>" defer></script>
  <script src="<?= asset('js/admin.js') ?>" defer></script>
  <?= $scripts ?? '' ?>
</body>
</html>
