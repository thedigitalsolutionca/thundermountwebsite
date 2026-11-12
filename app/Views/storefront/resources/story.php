<section class="resource-intro">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'About', 'url' => '/page/about'], ['label' => 'Our story']]]) ?>
    <div class="resource-split">
      <div>
        <span class="eyebrow">The inventor · Gustavo Estravit</span>
        <h1>One home project. A better way to mount.</h1>
        <p class="lead">In 1991, while building his first home, Gustavo Estravit saw room for a cleaner, more consistent approach to supporting garage doors and openers. That question became Thunder Mounts.</p>
        <div class="flex wrap">
          <a class="btn btn-primary" href="<?= url('/portfolio') ?>">See the installations <?= icon('arrow-right') ?></a>
          <a class="btn btn-outline-light" href="<?= url('/page/about') ?>">About the company</a>
        </div>
      </div>
      <figure class="resource-intro-photo">
        <img src="<?= asset('img/chi-door-malibu.jpg') ?>" alt="Installers working in a Malibu garage" width="920" height="610" fetchpriority="high">
        <figcaption>Real installation work in Malibu, California.</figcaption>
      </figure>
    </div>
  </div>
</section>

<section class="section">
  <div class="container resource-narrow">
    <span class="eyebrow">The idea</span>
    <h2>Better from the inside out.</h2>
    <p class="resource-lead">The inside of a garage deserves the same care as the door you see from the street. Gustavo began developing an alternative to improvised punched angle iron: a purpose-built system that looks intentional when the work is done.</p>
    <p class="muted">Early prototypes grew into a range of overhead opener and track mounts. The aim remains straightforward: give installers a clean, symmetrical option while planning around the structure, the equipment and the people who use the garage.</p>
    <div class="story-timeline" aria-label="Thunder Mounts milestones">
      <div><strong>1991</strong><p>The first concept takes shape during Gustavo’s home project.</p></div>
      <div><strong>2007</strong><p>Thunder Mounts appears in Professional Door Dealer magazine.</p></div>
      <div><strong>2010</strong><p>Thunder Mounts Garage Systems begins offering mounting products.</p></div>
      <div><strong>Today</strong><p>The team builds and supports mounting systems from Simi Valley, California.</p></div>
    </div>
  </div>
</section>

<section class="section section-gray">
  <div class="container resource-split resource-feature">
    <div class="resource-feature-image"><img src="<?= asset('img/tm-track-glass.jpg') ?>" alt="Symmetrical black track supports over a glass garage door" loading="lazy" width="930" height="620"></div>
    <div>
      <span class="eyebrow">Value · Symmetry · Safety</span>
      <h2>Designed for what happens overhead.</h2>
      <p class="muted">Thunder Mounts makes separate systems for the opener motor and the door track. Which one you need depends on your equipment, measurements and supporting structure — not just the look you want.</p>
      <p class="muted">Our team helps homeowners and professional installers choose the right starting point. Installation should always follow the instructions for the specific product and the door or opener manufacturer.</p>
      <div class="flex wrap mt-3">
        <a class="btn btn-dark" href="<?= url('/size-guide') ?>">Explore sizing</a>
        <a class="btn btn-outline" href="<?= url('/contact?subject=' . urlencode('Fitment question')) ?>">Ask a fitment question</a>
      </div>
    </div>
  </div>
</section>
