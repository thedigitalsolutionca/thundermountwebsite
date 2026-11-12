<?php /** @var string $content */ ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Thunder Mounts') ?> | Thunder Mounts</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="blank-body">
  <header class="site-header">
    <div class="container inner">
      <a class="brand tm-brand" href="<?= url('/') ?>" aria-label="Thunder Mounts home">
        <img class="brand-logo" src="<?= asset('img/thundermounts-logo.jpg') ?>" alt="Thunder Mounts — Garage Systems" width="705" height="173">
      </a>
      <a class="btn btn-outline btn-sm" href="<?= url('/shop') ?>">Shop</a>
    </div>
  </header>
  <main id="main"><?= $content ?></main>
  <footer class="site-footer" style="margin-top:40px">
    <div class="container" style="padding:20px 0">
      <p class="small" style="color:#9AA1A9;margin:0">Thunder Mount Garage Systems LLC · 4212 E Los Angeles Ave, Simi Valley, CA 93063 · <a href="tel:8053386680" style="color:#fff">805-338-6680</a></p>
    </div>
  </footer>
</body>
</html>
