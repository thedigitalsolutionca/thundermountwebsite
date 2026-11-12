<div class="page-head resource-page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Shop', 'url' => '/shop'], ['label' => 'Size guide']]]) ?>
    <span class="eyebrow">Step one · measure the drop</span>
    <h1>The right length starts with the right measurement.</h1>
    <p class="resource-lead">Opener mounts and track mounts solve different problems. Find the attachment point first, then measure the distance from the finished ceiling to that point.</p>
  </div>
</div>

<section class="section-tight" style="padding-top:4px">
  <div class="container resource-split size-guide-layout">
    <div class="card card-body size-guide-steps">
      <span class="eyebrow">How to measure</span>
      <ol class="numbered-steps">
        <li><strong>For an opener mount</strong><span>Measure from the finished ceiling to the attachment bolts at the opener motor flanges. Note the opener brand and model.</span></li>
        <li><strong>For a track mount</strong><span>Measure from the finished ceiling to the overhead track’s support bolt holes. TMGT sets are for the track, not the opener.</span></li>
        <li><strong>Check what is overhead</strong><span>Confirm the supporting structure and ceiling build-up with your installer. Drywall thickness and the location of wiring matter.</span></li>
        <li><strong>Compare maximum lengths</strong><span>Use the 12, 19, 24 or 29 inch maximum length listed for the model. Ask us if you are between sizes or unsure about compatibility.</span></li>
      </ol>
      <div class="flex wrap mt-3"><a class="btn btn-primary" href="<?= url('/shop') ?>">Shop the mounts</a><a class="btn btn-outline" href="<?= url('/contact?subject=' . urlencode('Fitment question')) ?>">Ask for help</a></div>
    </div>
    <figure class="size-chart">
      <img src="<?= asset('img/tm-installation-chart-2026.jpg') ?>" alt="Thunder Mounts illustration comparing track and opener mount lengths above a seven-foot garage door" width="600" height="573" loading="lazy">
      <figcaption>Supplied Thunder Mounts installation chart dated May 25, 2026. The chart uses TMGTS / TMGTX / TMGTL track labels; compare with current TMGT product listings and confirm fitment before ordering.</figcaption>
    </figure>
  </div>
</section>

<section class="section section-gray">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">Choose a starting point</span><h2>Two mounting jobs. Four maximum lengths.</h2><p class="muted">The product page is the source for each model’s exact maximum length and intended opener style.</p></div></div>
    <div class="resource-cards">
      <div class="card card-body"><span class="eyebrow">Opener motor</span><h3>TMS · TMX · TML</h3><p class="muted small">Mounts that attach at the opener motor flanges. Available across 12, 19, 24 and 29 inch maximum-length models.</p><a class="bold red" href="<?= url('/shop?category=opener-mounts') ?>">Explore opener mounts →</a></div>
      <div class="card card-body"><span class="eyebrow">Marantec-style opener</span><h3>TMMRT</h3><p class="muted small">Models for Marantec-style overhead openers in 12, 19 and 24 inch maximum lengths. Confirm your exact opener before ordering.</p><a class="bold red" href="<?= url('/shop?category=marantec-mounts') ?>">Explore TMMRT mounts →</a></div>
      <div class="card card-body"><span class="eyebrow">Overhead door track</span><h3>TMGT sets</h3><p class="muted small">Track support sets for one door, with 12, 19, 24 and 29 inch maximum lengths. Door and opener sold separately.</p><a class="bold red" href="<?= url('/shop?category=track-mounts') ?>">Explore track mounts →</a></div>
    </div>
  </div>
</section>
<section class="section-tight">
  <div class="container"><div class="notice notice-info" style="max-width:930px;margin:auto"><?= icon('shield') ?><div><strong>Safety first.</strong> This guide helps with product selection; it is not a substitute for model-specific installation instructions or the door and opener manufacturers’ requirements. Garage door systems contain high-tension components. Have a qualified installer check the structure and perform any work you are not trained to do. <a href="<?= url('/resources/instructions') ?>" class="bold">Read installation guidance →</a></div></div></div>
</section>
