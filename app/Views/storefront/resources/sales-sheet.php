<div class="page-head print-actions">
  <div class="container flex between wrap">
    <div><?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Resources', 'url' => '/size-guide'], ['label' => 'Sales sheet']]]) ?><h1 style="margin:0">Thunder Mounts sales sheet</h1><p class="muted" style="margin:6px 0 0">A quick, print-friendly product overview for the next conversation.</p></div>
    <button class="btn btn-dark" type="button" data-print><?= icon('download') ?> Print / save as PDF</button>
  </div>
</div>
<section class="section-tight" style="padding-top:0">
  <div class="container">
    <div class="print-sheet">
      <header class="sheet-header">
        <span class="sheet-mark"><?= icon('zap') ?> THUNDER <b>MOUNTS</b></span>
        <span>Value · Symmetry · Safety</span>
      </header>
      <div class="sheet-hero">
        <div><span class="eyebrow">Garage mounting systems</span><h2>A better finish starts overhead.</h2><p>Purpose-built supports for garage door openers and overhead tracks — designed and made in California.</p></div>
        <img src="<?= asset('img/tm-track-glass.jpg') ?>" alt="Track mounts supporting a full-view garage door" width="700" height="440">
      </div>
      <div class="sheet-content">
        <div class="sheet-heading"><span class="eyebrow">The range</span><h3>Built for two different jobs.</h3></div>
        <div class="sheet-models">
          <div><strong>Opener mounts</strong><span>TMS · TMX · TML</span><p>Attach at compatible opener motor flanges. Models are listed at 12, 19, 24 or 29 inch maximum length.</p></div>
          <div><strong>Marantec-style</strong><span>TMMRT</span><p>For compatible Marantec-style overhead openers. 12, 19 and 24 inch maximum-length models.</p></div>
          <div><strong>Overhead track</strong><span>TMGT sets</span><p>Support the garage door track. One set per door, with 12, 19, 24 or 29 inch maximum lengths.</p></div>
        </div>
        <div class="sheet-points"><span><b>01</b> Cleaner overhead lines</span><span><b>02</b> A symmetrical finish</span><span><b>03</b> Support for installers</span></div>
        <div class="sheet-foot">
          <div><h3>Plan your installation with us.</h3><p>Measure from the finished ceiling to the opener motor flange bolts or track support bolt holes. Confirm the structure and equipment before ordering. The door and opener are not included with a mount.</p></div>
          <div><strong>Thunder Mounts Garage Systems</strong><span>4212 E Los Angeles Ave · Simi Valley, CA 93063</span><span><?= e(setting('store.phone', '805-338-6680')) ?></span><span><?= e(setting('store.email', 'thunder@thundermounts.com')) ?></span><span><?= e(absolute_url('/')) ?></span></div>
        </div>
      </div>
      <div class="sheet-disclaimer">Use the model-specific instructions and door/opener manufacturer guidance. A qualified installer should assess the structure and perform work on high-tension door components.</div>
    </div>
    <div class="flex wrap print-actions mt-3"><a class="btn btn-primary" href="<?= url('/shop') ?>">View current products</a><a class="btn btn-outline" href="<?= url('/dealer-program') ?>">Explore dealer program</a><a class="btn btn-ghost" href="<?= url('/contact') ?>">Ask a question</a></div>
  </div>
</section>
