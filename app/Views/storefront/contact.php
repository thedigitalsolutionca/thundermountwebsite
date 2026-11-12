<?php
use App\Core\Csrf;
use App\Core\Settings;
$errs = errors();
$user = auth();
$phone = setting('store.phone', '805-338-6680');
$email = setting('store.email', 'thunder@thundermounts.com');
?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Contact']]]) ?>
    <span class="eyebrow">Talk to Thunder Mounts</span>
    <h1 style="margin-bottom:6px">We can help you get it right</h1>
    <p class="muted" style="margin:0;max-width:660px">Fitment questions, order status, dealer accounts or installation advice — reach the team in Simi Valley.</p>
  </div>
</div>

<section class="section-tight" style="padding-top:12px">
  <div class="container">
    <div class="grid" style="grid-template-columns:1fr 1.1fr;gap:24px;align-items:start">
      <div class="stack">
        <div class="card card-body contact-card">
          <?= icon('phone') ?>
          <div>
            <span class="eyebrow">Call us</span>
            <a class="contact-link" href="tel:<?= e(preg_replace('/\D+/', '', $phone)) ?>"><?= e($phone) ?></a>
            <p class="small muted" style="margin:4px 0 0"><?= e(setting('store.hours', 'Mon–Fri 9:00–4:00 · Sat 10:00–2:00')) ?></p>
          </div>
        </div>
        <div class="card card-body contact-card">
          <?= icon('mail') ?>
          <div>
            <span class="eyebrow">Email</span>
            <a class="contact-link" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            <p class="small muted" style="margin:4px 0 0">We reply during business hours.</p>
          </div>
        </div>
        <div class="card card-body contact-card">
          <?= icon('map-pin') ?>
          <div>
            <span class="eyebrow">Visit / ship from</span>
            <strong><?= e(setting('store.address', '4212 E Los Angeles Ave, Simi Valley, CA 93063')) ?></strong>
            <p class="small" style="margin:6px 0 0"><a class="bold red" href="https://maps.google.com/?q=<?= rawurlencode(setting('store.address', '4212 E Los Angeles Ave, Simi Valley, CA 93063')) ?>" target="_blank" rel="noopener">Get directions →</a></p>
          </div>
        </div>
        <div class="card card-body">
          <h2 style="font-size:1rem;margin-top:0">Before you call about fitment</h2>
          <p class="small muted" style="margin:0 0 8px">Have these handy and we can usually size the mount on the first call:</p>
          <ul class="checklist small" style="margin:0">
            <li>Opener brand and model</li>
            <li>Distance from ceiling to the opener bolt holes</li>
            <li>Door weight and height</li>
            <li>A photo of the ceiling and opener</li>
          </ul>
          <p class="small" style="margin:10px 0 0"><a class="bold" href="<?= url('/page/installation') ?>">Read the installation &amp; sizing guide →</a></p>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h2 style="font-size:1.15rem;margin:0">Send a message</h2><span class="small muted">All fields marked required</span></div>
        <div class="card-body">
          <form action="<?= url('/contact') ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <div class="form-row">
              <div class="field">
                <label for="contact-name">Your name</label>
                <input class="input <?= isset($errs['name']) ? 'invalid' : '' ?>" id="contact-name" name="name" value="<?= e(old('name', trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?>" required autocomplete="name">
                <?php if (isset($errs['name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['name']) ?></span><?php endif; ?>
              </div>
              <div class="field">
                <label for="contact-email">Email</label>
                <input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="contact-email" name="email" type="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" required autocomplete="email">
                <?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?>
              </div>
            </div>
            <div class="form-row">
              <div class="field">
                <label for="contact-phone">Phone <span class="muted">(optional)</span></label>
                <input class="input" id="contact-phone" name="phone" type="tel" value="<?= e(old('phone', $user['phone'] ?? '')) ?>" autocomplete="tel">
              </div>
              <div class="field">
                <label for="contact-subject">Subject</label>
                <select class="select <?= isset($errs['subject']) ? 'invalid' : '' ?>" id="contact-subject" name="subject" required>
                  <?php
                  $subjects = ['Fitment question', 'Order status', 'Shipping question', 'Returns', 'Dealer account', 'Installation support', 'Product availability', 'Something else'];
                  $currentSubject = old('subject', $prefillSubject ?? '');
                  ?>
                  <option value="">Choose a subject</option>
                  <?php foreach ($subjects as $subject): ?>
                    <option value="<?= e($subject) ?>"<?= selected($subject, $currentSubject) ?>><?= e($subject) ?></option>
                  <?php endforeach; ?>
                  <?php if ($currentSubject !== '' && !in_array($currentSubject, $subjects, true)): ?>
                    <option value="<?= e($currentSubject) ?>" selected><?= e($currentSubject) ?></option>
                  <?php endif; ?>
                </select>
                <?php if (isset($errs['subject'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['subject']) ?></span><?php endif; ?>
              </div>
            </div>
            <div class="field">
              <label for="contact-message">How can we help?</label>
              <textarea class="textarea <?= isset($errs['message']) ? 'invalid' : '' ?>" id="contact-message" name="message" rows="6" required minlength="10" maxlength="5000"><?= e(old('message')) ?></textarea>
              <?php if (isset($errs['message'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['message']) ?></span><?php endif; ?>
            </div>
            <button class="btn btn-primary btn-lg" type="submit">Send message</button>
            <p class="tiny muted" style="margin:10px 0 0">We use your details only to answer your enquiry. See the <a href="<?= url('/page/privacy') ?>" class="bold">privacy policy</a>.</p>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php clear_input(); ?>
