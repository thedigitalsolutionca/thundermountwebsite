<?php
/** @var array $featured */
/** @var array $categories */
/** @var array $reviews */
?>
<section class="hero">
  <div class="container inner">
    <div class="hero-copy">
      <span class="eyebrow" style="color:#FF8A8F">Built for today. Ready for tomorrow.</span>
      <h1>The new installation standard for <span>garage doors</span> and openers.</h1>
      <p class="lead">Thunder Mounts replaces outdated angle iron with engineered mounting systems that deliver Value, Symmetry and Safety. Purpose-built in America for the loads a garage really carries.</p>
      <div class="cta-row">
        <a class="btn btn-primary btn-lg" href="<?= url('/shop') ?>">Shop Products <?= icon('arrow-right') ?></a>
        <a class="btn btn-outline-light btn-lg" href="<?= url('/dealer-program') ?>">Become a Dealer</a>
      </div>
      <div class="hero-stats">
        <div><strong>4 lengths</strong><span>12 · 19 · 24 · 29 in.</span></div>
        <div><strong>5 series</strong><span>TMS · TMX · TML · TMMRT · TMGT</span></div>
        <div><strong>Made in USA</strong><span>Simi Valley, California</span></div>
      </div>
    </div>
    <div class="hero-media">
      <img src="<?= asset('img/hero-garage.jpg') ?>" alt="Two Thunder Mounts sets installed in a white garage" width="1200" height="900" fetchpriority="high">
      <span class="photo-cap">Actual Thunder Mounts installation</span>
    </div>
  </div>
</section>

<div class="vss-strip">
  <div class="container inner">
    <div class="item"><span class="num">V</span><div><strong>Value</strong><span>An engineered mount that elevates the whole installation.</span></div></div>
    <div class="item"><span class="num">S</span><div><strong>Symmetry</strong><span>Clean, balanced lines instead of improvised angle iron.</span></div></div>
    <div class="item"><span class="num">S</span><div><strong>Safety</strong><span>Purpose-built strength for doors and openers.</span></div></div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Shop by category</span>
        <h2>Find the right mount for the job</h2>
        <p class="muted">Mounts attach at the opener motor flanges or support the overhead door track. Not sure which length? Our sizing guide walks you through it.</p>
      </div>
      <a class="btn btn-outline" href="<?= url('/shop') ?>">View all products <?= icon('arrow-right') ?></a>
    </div>
    <div class="cat-grid">
      <?php foreach ($categories as $category): ?>
        <a class="cat-card" href="<?= url('/shop?category=' . urlencode($category['slug'])) ?>">
          <img src="<?= e(upload_url($category['image'])) ?>" alt="" loading="lazy" width="500" height="500">
          <span class="cap">
            <strong><?= e($category['name']) ?></strong>
            <span><?= (int) $category['product_count'] ?> product<?= (int) $category['product_count'] === 1 ? '' : 's' ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-gray">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Featured products</span>
        <h2>Installer favorites</h2>
        <p class="muted">The mounts our customers reach for most, from a single door to a full crew.</p>
      </div>
      <a class="btn btn-outline" href="<?= url('/shop') ?>">Shop all</a>
    </div>
    <div class="product-grid cols-4">
      <?php foreach ($featured as $product): ?>
        <?= \App\Core\View::partial('storefront/partials/product-card', ['product' => $product]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Before &amp; after</span>
        <h2>The difference is easy to see.</h2>
        <p class="muted">Actual installation photos from Thunder Mounts. Conventional angle iron on the left; Thunder Mounts on the right.</p>
      </div>
    </div>
    <div class="compare">
      <figure>
        <img src="<?= asset('img/before.jpg') ?>" alt="Garage opener hung with conventional angle iron" loading="lazy" width="800" height="600">
        <figcaption>Before — angle iron</figcaption>
      </figure>
      <figure class="after">
        <img src="<?= asset('img/after.jpg') ?>" alt="Thunder Mounts installed symmetrically in a garage" loading="lazy" width="800" height="600">
        <figcaption>After — Thunder Mounts</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Why Thunder Mounts</span>
        <h2 style="color:#fff">Engineered for strength, clean aesthetics and reliable results</h2>
        <p style="color:#C9CED4">A mounting system designed for the way garages are actually built and used.</p>
      </div>
    </div>
    <div class="grid grid-3">
      <div class="benefit">
        <div class="ico"><?= icon('wrench') ?></div>
        <h3>Built to replace angle iron</h3>
        <p>For years, installations relied on angle iron — common but outdated. Thunder Mounts is a modern solution tailored for today’s garages.</p>
      </div>
      <div class="benefit">
        <div class="ico"><?= icon('grid') ?></div>
        <h3>Symmetry on every job</h3>
        <p>Consistent, balanced mounting for openers and door track so every installation looks intentional.</p>
      </div>
      <div class="benefit">
        <div class="ico"><?= icon('shield') ?></div>
        <h3>Safety first</h3>
        <p>Purpose-designed mounts for the loads garage doors and openers place on the ceiling.</p>
      </div>
      <div class="benefit">
        <div class="ico"><?= icon('box') ?></div>
        <h3>Sets for one door</h3>
        <p>TMGT track sets include the chrome ring, mounting hardware and online installation instructions.</p>
      </div>
      <div class="benefit">
        <div class="ico"><?= icon('truck') ?></div>
        <h3>Ships from California</h3>
        <p>Orders ship via UPS from our Simi Valley base, with tracking sent when your order leaves us.</p>
      </div>
      <div class="benefit">
        <div class="ico"><?= icon('users') ?></div>
        <h3>Dealer program</h3>
        <p>Installers and distributors can apply for a dealer account with trade pricing and dealer ordering.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="split">
      <div>
        <span class="eyebrow">Installation &amp; compatibility</span>
        <h2>Choose the length. Mount at the flange or the track.</h2>
        <p class="muted">Opener mounts (TMS, TMX, TML, TMMRT) attach at the opener motor flanges. Track mount sets (TMGT) support the overhead door track and ship as a set for one door. Mounts are not sold with openers or doors attached.</p>
        <ul class="checklist">
          <li>Four maximum lengths: 12, 19, 24 and 29 inches</li>
          <li>Online installation instructions included</li>
          <li>Marantec-style mounts available (TMMRT series)</li>
          <li>Contact us to confirm fitment for your exact opener model</li>
        </ul>
        <div class="flex wrap">
          <a class="btn btn-dark" href="<?= url('/page/installation') ?>">Installation &amp; sizing <?= icon('arrow-right') ?></a>
          <a class="btn btn-outline" href="<?= url('/contact?subject=' . urlencode('Fitment question')) ?>">Ask about fitment</a>
        </div>
      </div>
      <div>
        <img src="<?= asset('img/tm-track-glass.jpg') ?>" alt="Thunder Mounts supporting the track of a glass garage door" loading="lazy" width="1200" height="800">
      </div>
    </div>
  </div>
</section>

<?php if ($reviews !== []): ?>
<section class="section section-gray">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">What customers say</span>
        <h2>Reviews</h2>
        <p class="muted">Verified customer reviews, published after moderation.</p>
      </div>
    </div>
    <div class="grid grid-3">
      <?php foreach ($reviews as $review): ?>
        <div class="review-card">
          <?= star_rating((float) $review['rating']) ?>
          <blockquote>“<?= e($review['body']) ?>”</blockquote>
          <div class="who"><strong><?= e($review['name']) ?></strong> · <?= e($review['product_name'] ?? '') ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="grid grid-2" style="align-items:stretch">
      <div class="card card-body" style="padding:40px;background:var(--gray-50)">
        <span class="eyebrow">Dealer program</span>
        <h2 style="margin-bottom:8px">Buy for the whole crew.</h2>
        <p class="muted">Installers and distributors can apply for a dealer account. Approved dealers sign in to see trade pricing, place orders and track them in one place.</p>
        <div class="flex wrap" style="margin-top:auto">
          <a class="btn btn-primary" href="<?= url('/dealer/apply') ?>">Apply for a dealer account</a>
          <a class="btn btn-outline" href="<?= url('/dealer/login') ?>">Dealer login</a>
        </div>
      </div>
      <div class="card card-body" style="padding:40px">
        <span class="eyebrow">Need help choosing?</span>
        <h2 style="margin-bottom:8px">Measure once, order right.</h2>
        <p class="muted">Measure from the ceiling to the opener attachment bolts, or from the ceiling to the door track bolt holes. If you are between sizes, call us and we will help.</p>
        <div class="flex wrap" style="margin-top:auto">
          <a class="btn btn-dark" href="tel:<?= e(preg_replace('/\D+/', '', setting('store.phone', '805-338-6680'))) ?>"><?= icon('phone') ?> <?= e(setting('store.phone', '805-338-6680')) ?></a>
          <a class="btn btn-outline" href="<?= url('/page/installation') ?>">Sizing guide</a>
        </div>
      </div>
    </div>
  </div>
</section>
