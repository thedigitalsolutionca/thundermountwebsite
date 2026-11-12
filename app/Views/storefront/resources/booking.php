<?php
$errs = errors();
$user = auth();
$defaultName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>
<div class="page-head resource-page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Request an appointment']]]) ?>
    <span class="eyebrow">Talk through your project</span>
    <h1>Let’s find a time to talk.</h1>
    <p class="resource-lead">Request a conversation about garage door installation design or a Thunder Mounts installation. Tell us when you’re available; our team will confirm the appointment with you.</p>
  </div>
</div>
<section class="section-tight" style="padding-top:4px">
  <div class="container booking-layout">
    <div class="booking-context">
      <div class="booking-photo"><img src="<?= asset('img/chi-door-malibu.jpg') ?>" alt="Installation team at work in a garage" width="920" height="600" loading="lazy"></div>
      <div class="card card-body"><span class="eyebrow">What to expect</span><h2 style="font-size:1.2rem">A request, not an instant booking.</h2><p class="small muted">We’ll review the details and get back to you during business hours to agree on a time. Nothing is reserved until we confirm it. No payment is taken to make this request.</p><p class="small" style="margin:0">Prefer to call? <a class="bold red" href="tel:<?= e(preg_replace('/\D+/', '', setting('store.phone', '805-338-6680'))) ?>"><?= e(setting('store.phone', '805-338-6680')) ?></a></p></div>
    </div>
    <div class="card booking-form">
      <div class="card-head"><h2 style="font-size:1.15rem;margin:0">Request a time</h2><span class="small muted">Pacific time · All fields marked required</span></div>
      <div class="card-body">
        <form method="post" action="<?= url('/book-online') ?>">
          <?= csrf_field() ?>
          <div class="field"><label for="booking-service">I’d like to discuss</label><select class="select <?= isset($errs['service']) ? 'invalid' : '' ?>" id="booking-service" name="service" required><option value="">Choose a service</option><option value="design"<?= selected('design', old('service')) ?>>Garage door installation design</option><option value="installation"<?= selected('installation', old('service')) ?>>Thunder Mount installation request</option></select><?php if (isset($errs['service'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['service']) ?></span><?php endif; ?></div>
          <div class="form-row">
            <div class="field"><label for="booking-date">Preferred date</label><input class="input <?= isset($errs['preferred_date']) ? 'invalid' : '' ?>" type="date" id="booking-date" name="preferred_date" value="<?= e(old('preferred_date')) ?>" min="<?= e((new DateTimeImmutable('today', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d')) ?>" required><span class="hint">We’ll confirm availability. Sundays are closed.</span><?php if (isset($errs['preferred_date'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['preferred_date']) ?></span><?php endif; ?></div>
            <div class="field"><label for="booking-window">Preferred time</label><select class="select <?= isset($errs['time_window']) ? 'invalid' : '' ?>" id="booking-window" name="time_window" required><option value="">Choose a window</option><option value="morning"<?= selected('morning', old('time_window')) ?>>Morning (Pacific)</option><option value="afternoon"<?= selected('afternoon', old('time_window')) ?>>Afternoon (Pacific)</option><option value="flexible"<?= selected('flexible', old('time_window')) ?>>I’m flexible</option></select><?php if (isset($errs['time_window'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['time_window']) ?></span><?php endif; ?></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="booking-name">Name</label><input class="input <?= isset($errs['name']) ? 'invalid' : '' ?>" id="booking-name" name="name" value="<?= e(old('name', $defaultName)) ?>" autocomplete="name" required><?php if (isset($errs['name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['name']) ?></span><?php endif; ?></div>
            <div class="field"><label for="booking-phone">Phone number</label><input class="input <?= isset($errs['phone']) ? 'invalid' : '' ?>" id="booking-phone" name="phone" type="tel" value="<?= e(old('phone', $user['phone'] ?? '')) ?>" autocomplete="tel" required><?php if (isset($errs['phone'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['phone']) ?></span><?php endif; ?></div>
          </div>
          <div class="field"><label for="booking-email">Email address</label><input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="booking-email" name="email" type="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" autocomplete="email" required><?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?></div>
          <div class="field"><label for="booking-notes">Your project <span class="muted">(optional)</span></label><textarea class="textarea <?= isset($errs['notes']) ? 'invalid' : '' ?>" id="booking-notes" name="notes" rows="4" maxlength="1000" placeholder="Door and opener model, project location, measurements or questions…"><?= e(old('notes')) ?></textarea><?php if (isset($errs['notes'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['notes']) ?></span><?php endif; ?></div>
          <button class="btn btn-primary btn-lg" type="submit">Send appointment request <?= icon('arrow-right') ?></button>
          <p class="small muted mt-2" style="margin-bottom:0">We’ll only use your details to respond to this request. <a class="bold" href="<?= url('/page/privacy') ?>">Privacy policy</a>.</p>
        </form>
      </div>
    </div>
  </div>
</section>
