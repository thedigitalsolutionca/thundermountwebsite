<?php
use App\Core\Auth;
$accountUser = Auth::user();
$dealer = (new \App\Services\DealerService())->forUser((int) $accountUser['id']);
?>
<aside class="account-side">
  <div class="account-who">
    <span class="avatar"><?= e(\App\Core\Str::initials(($accountUser['first_name'] ?? '') . ' ' . ($accountUser['last_name'] ?? ''))) ?></span>
    <div>
      <strong><?= e(trim(($accountUser['first_name'] ?? '') . ' ' . ($accountUser['last_name'] ?? ''))) ?></strong>
      <span class="small muted"><?= e($accountUser['email']) ?></span>
    </div>
  </div>
  <nav aria-label="Account navigation">
    <a href="<?= url('/account') ?>" class="<?= current_path() === '/account' ? 'active' : '' ?>"><?= icon('home') ?> Overview</a>
    <a href="<?= url('/account/orders') ?>" class="<?= is_active('/account/orders') ?>"><?= icon('package') ?> Orders</a>
    <a href="<?= url('/account/addresses') ?>" class="<?= is_active('/account/addresses') ?>"><?= icon('map-pin') ?> Addresses</a>
    <a href="<?= url('/account/profile') ?>" class="<?= is_active('/account/profile') ?>"><?= icon('user') ?> Settings</a>
    <?php if (dealer_program_enabled()): ?>
      <?php if ($dealer): ?>
        <a href="<?= url('/dealer/account') ?>" class="<?= is_active('/dealer') ?>"><?= icon('users') ?> Dealer portal
          <?php if ($dealer['status'] !== 'approved'): ?><span class="badge <?= e(badge_class($dealer['status'])) ?>" style="margin-left:auto"><?= e(ucfirst($dealer['status'])) ?></span><?php endif; ?>
        </a>
      <?php elseif (dealer_applications_enabled()): ?>
        <a href="<?= url('/dealer/apply') ?>"><?= icon('tag') ?> Become a dealer</a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (Auth::isAdmin()): ?>
      <a href="<?= url('/admin') ?>"><?= icon('settings') ?> Admin dashboard</a>
    <?php endif; ?>
  </nav>
  <form action="<?= url('/logout') ?>" method="post" style="margin-top:12px">
    <?= csrf_field() ?>
    <button type="submit" class="side-signout"><?= icon('logout') ?> Sign out</button>
  </form>
</aside>
