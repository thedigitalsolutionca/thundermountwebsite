<div class="page-head resource-page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Size guide', 'url' => '/size-guide'], ['label' => 'Mounting instructions']]]) ?>
    <span class="eyebrow">Installation resources</span>
    <h1>Start with the instructions for your exact model.</h1>
    <p class="resource-lead">A well-planned mount starts with the product model, the door or opener manufacturer’s requirements and a clear understanding of the structure above the ceiling.</p>
  </div>
</div>
<section class="section-tight" style="padding-top:4px">
  <div class="container resource-split resource-feature">
    <div>
      <span class="eyebrow">Before installation</span>
      <h2>Make these checks before you begin.</h2>
      <ul class="checklist">
        <li>Identify your Thunder Mounts model and its maximum length on the product page.</li>
        <li>Confirm whether the mount is for the opener motor or the overhead door track.</li>
        <li>Locate suitable supporting structure above the finished ceiling; do not assume drywall alone is an attachment point.</li>
        <li>Review the instructions supplied with your door and opener, and follow local requirements.</li>
      </ul>
      <div class="notice notice-warn mt-3"><?= icon('shield') ?><div><strong>Work safely.</strong> Garage doors, springs and openers can cause serious injury. These planning notes are not a step-by-step installation manual. Use the instructions for the exact model and a qualified installer where appropriate.</div></div>
    </div>
    <div class="resource-feature-image"><img src="<?= asset('img/tm-chrome-ring.jpg') ?>" alt="Close view of the finishing ring around a track mount at the ceiling" width="920" height="610" loading="lazy"></div>
  </div>
</section>
<section class="section section-gray">
  <div class="container resource-narrow">
    <span class="eyebrow">Need the model-specific guide?</span>
    <h2>Tell us what you’re installing.</h2>
    <p class="muted">The original installation documents are not yet hosted on this site. Rather than give you an incomplete or out-of-date set of steps, we’ll send the right instructions for your model. Include your SKU (for example, TMS-12 or TMGT-24) and opener brand in your message. If a ceiling photo would help, email it separately to thunder@thundermounts.com.</p>
    <div class="flex wrap mt-3">
      <a class="btn btn-primary" href="<?= url('/contact?subject=' . urlencode('Installation support')) ?>">Request instructions <?= icon('arrow-right') ?></a>
      <a class="btn btn-outline" href="<?= url('/size-guide') ?>">View size chart</a>
      <a class="btn btn-ghost" href="tel:<?= e(preg_replace('/\D+/', '', setting('store.phone', '805-338-6680'))) ?>">Call <?= e(setting('store.phone', '805-338-6680')) ?></a>
    </div>
  </div>
</section>
