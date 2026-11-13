<section class="section">
  <div class="container" style="max-width:640px">
    <div class="card card-body" style="padding:32px;text-align:center">
      <div class="empty" style="padding:0">
        <?= icon('users') ?>
        <h1 style="font-size:1.8rem;margin-bottom:6px">Dealer account not set up yet</h1>
        <p class="muted">Apply for a trade account to unlock dealer pricing. Applications are reviewed during business hours.</p>
        <div class="flex" style="justify-content:center;flex-wrap:wrap">
          <?php if (dealer_applications_enabled()): ?>
            <a class="btn btn-primary" href="<?= url('/dealer/apply') ?>">Apply for a dealer account</a>
          <?php else: ?>
            <a class="btn btn-primary" href="<?= url('/contact') ?>">Contact Thunder Mounts</a>
          <?php endif; ?>
          <?php if (dealer_pages_enabled()): ?>
            <a class="btn btn-outline" href="<?= url('/dealer-program') ?>">Learn about the program</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
