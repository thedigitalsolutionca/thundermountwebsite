<?php
use App\Core\Csrf;
$errs = errors();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin sign-in · Thunder Mounts</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="admin-login-body">
  <main class="admin-login">
    <div class="admin-login-card">
      <a class="brand tm-brand" href="<?= url('/') ?>" style="margin-bottom:22px" aria-label="Thunder Mounts home">
        <img class="brand-logo" src="<?= asset('img/thundermounts-logo.jpg') ?>" alt="Thunder Mounts — Garage Systems" width="705" height="173">
      </a>
      <span class="eyebrow">Store administration</span>
      <h1 style="font-size:1.7rem;margin:4px 0 6px">Sign in to manage the store</h1>
      <p class="small muted">Admin access only. Customer and dealer accounts sign in through the storefront.</p>

      <?php if (isset($errs['email'])): ?><div class="notice notice-error"><?= icon('lock') ?><div><?= e($errs['email']) ?></div></div><?php endif; ?>

      <form action="<?= url('/admin/login') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <div class="field">
          <label for="admin-email">Email address</label>
          <input class="input" id="admin-email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="username" autofocus>
        </div>
        <div class="field">
          <label for="admin-password">Password</label>
          <input class="input" id="admin-password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
      </form>
      <p class="tiny muted" style="margin:18px 0 0;text-align:center"><a href="<?= url('/') ?>">← Back to Thunder Mounts</a></p>
    </div>
  </main>
</body>
</html>
<?php clear_input(); ?>
