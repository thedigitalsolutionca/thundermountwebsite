<?php use App\Core\Money; ?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Dealer Program']]]) ?>
    <div class="split-hero">
      <div>
        <span class="eyebrow">Trade accounts</span>
        <h1 style="font-size:clamp(2.2rem,4.5vw,3.6rem)">Stock the mount that raises the standard.</h1>
        <p class="lead">Thunder Mounts dealer accounts give garage door companies, installers and distributors trade pricing, priority support and a product line customers can see the difference in.</p>
        <div class="flex wrap">
          <a class="btn btn-primary btn-lg" href="<?= url(auth() ? '/dealer/apply' : '/register') ?>">Apply for a dealer account</a>
          <a class="btn btn-outline btn-lg" href="<?= url('/dealer/login') ?>">Dealer sign-in</a>
        </div>
        <p class="small muted mt-2">Applications are reviewed during business hours. Approved accounts get pricing automatically at checkout.</p>
      </div>
      <div class="hero-media">
        <img src="<?= asset('img/tm-install-white.jpg') ?>" alt="Thunder Mounts installation" width="900" height="700" loading="lazy">
      </div>
    </div>
  </div>
</div>

<section class="section-tight">
  <div class="container">
    <div class="grid grid-3">
      <div class="card card-body">
        <?= icon('tag', 'feature-icon') ?>
        <h3>Trade pricing</h3>
        <p class="muted small">Discounts by tier, applied automatically to every order placed while signed in. No codes to remember.</p>
      </div>
      <div class="card card-body">
        <?= icon('wrench', 'feature-icon') ?>
        <h3>Technical support</h3>
        <p class="muted small">Talk to the people who build the mounts. We help with sizing, ceiling conditions and opener compatibility.</p>
      </div>
      <div class="card card-body">
        <?= icon('truck', 'feature-icon') ?>
        <h3>Straightforward ordering</h3>
        <p class="muted small">Order online with shipping calculated at checkout, or arrange larger orders with us directly.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-gray">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">How it works</span>
        <h2>Approval in three steps</h2>
      </div>
    </div>
    <div class="grid grid-3">
      <div class="step-card"><span>01</span><h3>Create your account</h3><p class="muted small">Register with the email you want tied to the trade account.</p></div>
      <div class="step-card"><span>02</span><h3>Send your business details</h3><p class="muted small">Company, address, business type and resale number. Attach your resale certificate if you have it.</p></div>
      <div class="step-card"><span>03</span><h3>Get approved and order</h3><p class="muted small">We review the application, assign a tier and email you when pricing is live.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:900px">
    <span class="eyebrow">Trade tiers</span>
    <h2>Pricing that reflects your volume</h2>
    <?php if ($tiers === []): ?>
      <div class="notice notice-info"><?= icon('tag') ?><div><strong>Dealer tiers are configured by Thunder Mounts.</strong> Apply and we will confirm the pricing that fits your business.</div></div>
    <?php else: ?>
      <div class="table-wrap card">
        <table class="data-table">
          <thead><tr><th>Tier</th><th>Trade discount</th><th>Minimum order</th><th>Free shipping</th></tr></thead>
          <tbody>
            <?php foreach ($tiers as $tier): ?>
              <tr>
                <td><strong><?= e($tier['name']) ?></strong><?php if ($tier['notes']): ?><div class="tiny muted"><?= e($tier['notes']) ?></div><?php endif; ?></td>
                <td><?= e(rtrim(rtrim(number_format((float) $tier['discount_percent'], 2, '.', ''), '0'), '.')) ?>% off</td>
                <td><?= (float) $tier['min_order_total'] > 0 ? e(Money::format((float) $tier['min_order_total'])) : 'None' ?></td>
                <td><?= $tier['free_shipping_min'] !== null ? 'Over ' . e(Money::format((float) $tier['free_shipping_min'])) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <p class="small muted mt-2">Tier assignment and terms are confirmed by Thunder Mounts. Discounts apply to product prices and are shown before payment.</p>
  </div>
</section>

<section class="section section-gray">
  <div class="container" style="max-width:820px">
    <span class="eyebrow">Questions</span>
    <h2>Dealer FAQ</h2>
    <details class="faq" open><summary>Who can apply?</summary><p>Garage door companies, installers, remodelers and retailers that resell or install garage doors and openers. We review each application.</p></details>
    <details class="faq"><summary>Do I need a resale number?</summary><p>It helps us confirm your business and tax status. You can submit what you have and we will follow up if anything else is needed.</p></details>
    <details class="faq"><summary>How soon can I order at dealer pricing?</summary><p>As soon as your account is approved and a tier is assigned. Pricing is applied automatically while you are signed in.</p></details>
    <details class="faq"><summary>Can I combine dealer pricing with discount codes?</summary><p>Some codes are dealer-only and some are customer-only. If a code cannot be combined, the cart tells you before you pay.</p></details>
    <details class="faq"><summary>Can I order by purchase order?</summary><p>Call <a href="tel:8053386680">805-338-6680</a> to discuss larger or repeat orders. Online checkout supports PayPal, cards and wallets.</p></details>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band">
      <div>
        <h2 style="color:#fff;margin-bottom:6px">Ready to stock Thunder Mounts?</h2>
        <p style="color:#C9CED4;margin:0">Apply online. We will confirm your account and pricing.</p>
      </div>
      <div class="flex wrap">
        <a class="btn btn-primary" href="<?= url(auth() ? '/dealer/apply' : '/register') ?>">Apply now</a>
        <a class="btn btn-outline" href="<?= url('/contact') ?>" style="border-color:#555;color:#fff">Talk to us</a>
      </div>
    </div>
  </div>
</section>
