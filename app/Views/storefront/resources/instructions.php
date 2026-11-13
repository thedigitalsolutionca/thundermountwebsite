<div class="page-head resource-page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Mounting instructions']]]) ?>
    <span class="eyebrow">Installation resources</span>
    <h1>Mounting instructions &amp; installation chart.</h1>
    <p class="resource-lead">Download the original Thunder Mounts garage door track instructions and review the installation chart before planning your project. Confirm the right guide for your model and hardware generation with our team.</p>
  </div>
</div>
<section class="section-tight" style="padding-top:4px">
  <div class="container instruction-library">
    <article class="card instruction-download">
      <div class="instruction-file-icon" aria-hidden="true"><?= icon('download') ?></div>
      <span class="eyebrow">Original installation document</span>
      <h2>Garage Door Track Mounting Instructions</h2>
      <p class="instruction-file-meta">2023 edition <span aria-hidden="true">·</span> Word document (.docx) <span aria-hidden="true">·</span> 2.2 MB</p>
      <p class="muted">The supplied manufacturer document covers the tools and parts, above-ceiling and below-ceiling track mounting, channel orientation, and assembly checks. Keep the complete document available while planning the installation.</p>
      <a class="btn btn-primary" href="<?= asset('documents/thunder-mounts-track-instructions-2023.docx') ?>" download="Thunder-Mounts-Track-Instructions-2023.docx" type="application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-instruction-download><?= icon('download') ?> Download Track Instructions</a>
      <p class="small muted mt-2">Opens in Microsoft Word or a compatible document reader. This is the track-mount guide, not a universal opener installation manual.</p>
      <div class="notice notice-warn mt-3"><?= icon('shield') ?><div><strong>Check your exact model.</strong> The document is dated 2023. Before starting, confirm that it applies to your current mount, hardware generation, door, and supporting structure. Garage door work can cause serious injury; use a qualified installer where appropriate.</div></div>
    </article>
    <figure class="instruction-chart">
      <a href="<?= asset('img/tm-installation-chart-2026.jpg') ?>" data-lightbox="installation-resources" data-lightbox-title="Thunder Mounts Installation Chart" data-lightbox-detail="Supplied chart dated May 25, 2026. Confirm model selection and fitment with Thunder Mounts." aria-label="Enlarge the Thunder Mounts installation chart">
        <img src="<?= asset('img/tm-installation-chart-2026.jpg') ?>" alt="Thunder Mounts installation chart comparing track and opener mounting lengths for garage ceiling heights" width="600" height="573" fetchpriority="high" data-installation-chart>
      </a>
      <figcaption><strong>Thunder Mounts Installation Chart</strong><span>May 25, 2026 · Select the image to enlarge.</span></figcaption>
      <div class="flex wrap">
        <a class="btn btn-outline btn-sm" href="<?= asset('img/tm-installation-chart-2026.jpg') ?>" download="Thunder-Mounts-Installation-Chart-2026.jpg"><?= icon('download') ?> Download Chart</a>
        <a class="btn btn-ghost btn-sm" href="<?= url('/size-guide') ?>">Sizing guide <?= icon('arrow-right') ?></a>
      </div>
    </figure>
  </div>
</section>
<section class="section section-gray">
  <div class="container resource-narrow">
    <span class="eyebrow">Before installation</span>
    <h2>Make these checks before you begin.</h2>
    <ul class="checklist">
      <li>Identify your Thunder Mounts model, hardware generation, and maximum length.</li>
      <li>Confirm whether the mount supports the opener motor or the overhead door track.</li>
      <li>Locate suitable supporting structure above the finished ceiling; drywall alone is not a structural attachment point.</li>
      <li>Read the complete manufacturer document and the instructions supplied with your garage door and opener. Follow applicable local requirements.</li>
    </ul>
    <h3 class="mt-3">Need opener instructions or help confirming the guide?</h3>
    <p class="muted">Include your model or SKU and opener brand when contacting us. For project photos, email <?= e(setting('store.email', 'thunder@thundermounts.com')) ?>. Our team can help confirm the appropriate installation resources for your setup.</p>
    <div class="flex wrap mt-3">
      <a class="btn btn-primary" href="<?= url('/contact?subject=' . urlencode('Installation support')) ?>">Ask About Your Model <?= icon('arrow-right') ?></a>
      <a class="btn btn-outline" href="tel:<?= e(preg_replace('/\D+/', '', setting('store.phone', '805-338-6680'))) ?>">Call <?= e(setting('store.phone', '805-338-6680')) ?></a>
    </div>
  </div>
</section>
