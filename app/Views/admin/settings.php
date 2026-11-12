<?php
use App\Core\Csrf;
$errs = errors();
$tabs = ['store' => 'Store', 'seo' => 'SEO', 'checkout' => 'Checkout', 'paypal' => 'PayPal', 'mail' => 'Email', 'preferences' => 'Preferences', 'users' => 'Admins'];
$get = static fn (string $key, string $default = '') => e((string) setting($key, $default));
?>
<div class="admin-head">
  <div><h2>Settings</h2><p>Store details, payments, email and admin access.</p></div>
</div>

<div class="admin-tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="<?= $tab === $key ? 'active' : '' ?>" href="<?= url('/admin/settings?tab=' . $key) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($errs !== []): ?>
  <div class="admin-alert"><?= icon('bell') ?><div><strong>Some values need attention.</strong><ul><?php foreach ($errs as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>

<?php if ($tab === 'store'): ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="store">
    <div class="admin-card-head"><div><h3>Store details</h3><span>Used across the storefront, emails and invoices.</span></div></div>
    <div class="admin-card-body">
      <div class="form-row">
        <div class="field"><label for="store-name">Store name</label><input class="input" id="store-name" name="settings[store.name]" value="<?= $get('store.name') ?>" required></div>
        <div class="field"><label for="legal-name">Legal name</label><input class="input" id="legal-name" name="settings[store.legal_name]" value="<?= $get('store.legal_name') ?>"></div>
      </div>
      <div class="field"><label for="tagline">Tagline</label><input class="input" id="tagline" name="settings[store.tagline]" value="<?= $get('store.tagline') ?>"></div>
      <div class="form-row">
        <div class="field"><label for="phone">Phone</label><input class="input" id="phone" name="settings[store.phone]" value="<?= $get('store.phone') ?>"></div>
        <div class="field"><label for="office-phone">Office phone</label><input class="input" id="office-phone" name="settings[store.office_phone]" value="<?= $get('store.office_phone') ?>"></div>
      </div>
      <div class="form-row">
        <div class="field"><label for="store-email">Public email</label><input class="input" id="store-email" type="email" name="settings[store.email]" value="<?= $get('store.email') ?>"></div>
        <div class="field"><label for="notify-email">Order notification email</label><input class="input" id="notify-email" type="email" name="settings[store.notification_email]" value="<?= $get('store.notification_email') ?>"><span class="hint">New orders and enquiries are sent here.</span></div>
      </div>
      <div class="field"><label for="address">Address</label><input class="input" id="address" name="settings[store.address]" value="<?= $get('store.address') ?>"></div>
      <div class="form-row">
        <div class="field"><label for="hours">Hours</label><input class="input" id="hours" name="settings[store.hours]" value="<?= $get('store.hours') ?>"></div>
        <div class="field"><label for="currency">Currency</label><input class="input" id="currency" name="settings[store.currency]" value="<?= $get('store.currency', 'USD') ?>" maxlength="3"></div>
      </div>
      <div class="divider"></div>
      <h4 style="margin:0 0 8px">Announcement bar</h4>
      <div class="field"><label for="announcement">Message</label><input class="input" id="announcement" name="settings[store.announcement]" value="<?= $get('store.announcement') ?>"></div>
      <div class="form-row">
        <div class="field"><label for="announcement-link">Link</label><input class="input" id="announcement-link" name="settings[store.announcement_link]" value="<?= $get('store.announcement_link') ?>" placeholder="/dealer/apply"></div>
        <div class="field" style="display:flex;align-items:flex-end;gap:16px">
          <label class="check"><input type="checkbox" name="settings[store.announcement_enabled]" value="1" <?= setting('store.announcement_enabled', '1') === '1' ? 'checked' : '' ?>><span>Show announcement</span></label>
          <label class="check"><input type="checkbox" name="settings[store.newsletter_enabled]" value="1" <?= setting('store.newsletter_enabled', '1') === '1' ? 'checked' : '' ?>><span>Footer newsletter</span></label>
        </div>
      </div>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save store settings</button></div>
  </form>

<?php elseif ($tab === 'seo'): ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="seo">
    <div class="admin-card-head"><div><h3>Search &amp; social</h3><span>Titles, descriptions and sharing image.</span></div></div>
    <div class="admin-card-body">
      <div class="field"><label for="seo-title">Default meta title</label><input class="input" id="seo-title" name="settings[seo.title]" value="<?= $get('seo.title') ?>" maxlength="190"></div>
      <div class="field"><label for="seo-description">Default meta description</label><textarea class="textarea" id="seo-description" name="settings[seo.description]" rows="3" maxlength="300"><?= $get('seo.description') ?></textarea></div>
      <div class="form-row">
        <div class="field"><label for="seo-image">Social sharing image path</label><input class="input" id="seo-image" name="settings[seo.og_image]" value="<?= $get('seo.og_image') ?>"><span class="hint">Example: /assets/img/hero-garage.jpg or /uploads/…</span></div>
        <div class="field"><label for="org-type">Organization schema type</label><select class="select" id="org-type" name="settings[seo.organization_type]"><option value="LocalBusiness"<?= selected('LocalBusiness', setting('seo.organization_type', 'LocalBusiness')) ?>>LocalBusiness</option><option value="Store"<?= selected('Store', setting('seo.organization_type', 'LocalBusiness')) ?>>Store</option><option value="HomeAndConstructionBusiness"<?= selected('HomeAndConstructionBusiness', setting('seo.organization_type', 'LocalBusiness')) ?>>HomeAndConstructionBusiness</option></select></div>
      </div>
      <div class="admin-note">Sitemap: <a href="<?= url('/sitemap.xml') ?>" target="_blank" rel="noopener">/sitemap.xml</a> · Robots: <a href="<?= url('/robots.txt') ?>" target="_blank" rel="noopener">/robots.txt</a></div>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save SEO settings</button></div>
  </form>

<?php elseif ($tab === 'checkout'): ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="checkout">
    <div class="admin-card-head"><div><h3>Checkout options</h3><span>PayPal is the primary payment method. Invoice ordering is optional.</span></div></div>
    <div class="admin-card-body">
      <label class="check"><input type="checkbox" name="settings[checkout.manual_payment_enabled]" value="1" <?= setting('checkout.manual_payment_enabled', '0') === '1' ? 'checked' : '' ?>><span>Allow customers to place an order and pay by invoice</span></label>
      <p class="tiny muted">Only enable this if you will follow up with payment instructions. Orders are not marked paid until you record payment.</p>
      <div class="field"><label for="manual-label">Invoice option label</label><input class="input" id="manual-label" name="settings[checkout.manual_payment_label]" value="<?= $get('checkout.manual_payment_label', 'Pay by invoice') ?>"></div>
      <div class="field"><label for="manual-instructions">Invoice instructions shown at checkout</label><textarea class="textarea" id="manual-instructions" name="settings[checkout.manual_payment_instructions]" rows="3"><?= $get('checkout.manual_payment_instructions') ?></textarea></div>
      <div class="field"><label for="terms-note">Terms note</label><textarea class="textarea" id="terms-note" name="settings[checkout.terms_note]" rows="2"><?= $get('checkout.terms_note') ?></textarea></div>
      <div class="admin-note"><strong>Checkout readiness:</strong> shipping methods and tax rates must be configured before customers can pay. <a href="<?= url('/admin/shipping') ?>">Shipping</a> · <a href="<?= url('/admin/tax') ?>">Tax</a></div>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save checkout settings</button></div>
  </form>

<?php elseif ($tab === 'paypal'): ?>
  <div class="admin-card">
    <div class="admin-card-head">
      <div><h3>PayPal</h3><span>Expanded Checkout — PayPal, cards, Venmo, Pay Later, Apple Pay and Google Pay where enabled on the account.</span></div>
      <span class="badge <?= $paypalConfigured ? 'badge-green' : 'badge-amber' ?>"><?= $paypalConfigured ? 'Credentials saved' : 'Not configured' ?></span>
    </div>
    <div class="admin-card-body">
      <div class="notice notice-info"><?= icon('lock') ?><div><strong>Where to find these:</strong> PayPal Developer Dashboard → Apps &amp; Credentials. Use sandbox credentials while testing. Enable Apple Pay and Google Pay in your PayPal account before they appear at checkout.</div></div>
      <form action="<?= url('/admin/settings') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="group" value="paypal">
        <label class="check"><input type="checkbox" name="settings[paypal.enabled]" value="1" <?= setting('paypal.enabled', '0') === '1' ? 'checked' : '' ?>><span>Enable PayPal at checkout</span></label>
        <div class="form-row" style="margin-top:12px">
          <div class="field"><label for="paypal-mode">Environment</label><select class="select" id="paypal-mode" name="settings[paypal.mode]"><option value="sandbox"<?= selected('sandbox', setting('paypal.mode', 'sandbox')) ?>>Sandbox (testing)</option><option value="live"<?= selected('live', setting('paypal.mode', 'sandbox')) ?>>Live (real payments)</option></select></div>
          <div class="field"><label for="paypal-brand">Brand name on PayPal</label><input class="input" id="paypal-brand" name="settings[paypal.brand_name]" value="<?= $get('paypal.brand_name', 'Thunder Mounts') ?>"></div>
        </div>
        <div class="field"><label for="paypal-client">Client ID</label><input class="input" id="paypal-client" name="settings[encrypted.paypal.client_id]" value="" autocomplete="off" placeholder="<?= $paypalConfigured ? 'Saved — leave blank to keep' : 'AaBbCc…' ?>"></div>
        <div class="field"><label for="paypal-secret">Client secret</label><input class="input" id="paypal-secret" type="password" name="settings[encrypted.paypal.client_secret]" value="" autocomplete="new-password" placeholder="<?= $paypalConfigured ? 'Saved — leave blank to keep' : 'Enter secret' ?>"></div>
        <div class="field"><label for="paypal-webhook">Webhook ID</label><input class="input" id="paypal-webhook" name="settings[encrypted.paypal.webhook_id]" value="" autocomplete="off" placeholder="<?= $webhookConfigured ? 'Saved — leave blank to keep' : 'Required for payment webhooks' ?>"><span class="hint">Create a webhook pointing to <code><?= e(absolute_url('/webhooks/paypal')) ?></code> for <code>PAYMENT.CAPTURE.COMPLETED</code>, <code>PAYMENT.CAPTURE.DENIED</code>, <code>PAYMENT.CAPTURE.REFUNDED</code>.</span></div>
        <label class="check"><input type="checkbox" name="paypal_clear_credentials" value="1"><span>Clear saved PayPal credentials</span></label>
        <div class="flex wrap mt-2">
          <button class="btn btn-primary" type="submit">Save PayPal settings</button>
          <button class="btn btn-outline" type="submit" formaction="<?= url('/admin/settings/paypal/test') ?>" formnovalidate>Test connection</button>
        </div>
      </form>
    </div>
  </div>

<?php elseif ($tab === 'mail'): ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="mail">
    <div class="admin-card-head"><div><h3>Transactional email</h3><span>Order confirmations, shipping notices, password resets and dealer emails.</span></div><span class="badge <?= $mailConfigured ? 'badge-green' : 'badge-amber' ?>"><?= $mailConfigured ? 'SMTP configured' : 'Using PHP mail()' ?></span></div>
    <div class="admin-card-body">
      <div class="form-row">
        <div class="field"><label for="transport">Transport</label><select class="select" id="transport" name="settings[mail.transport]"><option value="mail"<?= selected('mail', setting('mail.transport', 'mail')) ?>>PHP mail()</option><option value="smtp"<?= selected('smtp', setting('mail.transport', 'mail')) ?>>SMTP</option></select><span class="hint">SMTP is recommended on Hostinger.</span></div>
        <div class="field"><label for="from-address">From address</label><input class="input" id="from-address" type="email" name="settings[mail.from_address]" value="<?= $get('mail.from_address') ?>"></div>
      </div>
      <div class="form-row">
        <div class="field"><label for="mail-host">SMTP host</label><input class="input" id="mail-host" name="settings[encrypted.mail_host]" value="" placeholder="<?= $mailConfigured ? 'Saved — leave blank to keep' : 'smtp.hostinger.com' ?>"></div>
        <div class="field"><label for="mail-port">Port</label><input class="input" id="mail-port" name="settings[mail.port]" value="<?= $get('mail.port', '587') ?>" inputmode="numeric"></div>
      </div>
      <div class="form-row">
        <div class="field"><label for="encryption">Encryption</label><select class="select" id="encryption" name="settings[mail.encryption]"><option value="tls"<?= selected('tls', setting('mail.encryption', 'tls')) ?>>TLS (587)</option><option value="ssl"<?= selected('ssl', setting('mail.encryption', 'tls')) ?>>SSL (465)</option><option value="none"<?= selected('none', setting('mail.encryption', 'tls')) ?>>None</option></select></div>
        <div class="field"><label for="mail-username">SMTP username</label><input class="input" id="mail-username" name="settings[encrypted.mail_username]" value="" autocomplete="off" placeholder="<?= $mailConfigured ? 'Saved — leave blank to keep' : 'Usually the full email address' ?>"></div>
      </div>
      <div class="field" style="max-width:420px"><label for="mail-password">SMTP password</label><input class="input" id="mail-password" type="password" name="settings[encrypted.mail_password]" value="" autocomplete="new-password" placeholder="<?= $mailConfigured ? 'Saved — leave blank to keep' : 'Enter password' ?>"></div>
      <div class="flex wrap mt-2">
        <button class="btn btn-primary" type="submit">Save email settings</button>
        <button class="btn btn-outline" type="submit" formaction="<?= url('/admin/settings/email/test') ?>" formnovalidate>Send test email</button>
      </div>
    </div>
  </form>

<?php elseif ($tab === 'preferences'): ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="preferences">
    <div class="admin-card-head"><div><h3>Store preferences</h3></div></div>
    <div class="admin-card-body">
      <label class="check"><input type="checkbox" name="settings[reviews.auto_approve]" value="1" <?= setting('reviews.auto_approve', '0') === '1' ? 'checked' : '' ?>><span>Publish verified reviews immediately</span></label>
      <p class="tiny muted">Leave off to moderate every review before it appears.</p>
      <label class="check"><input type="checkbox" name="settings[dealer.auto_approve]" value="1" <?= setting('dealer.auto_approve', '0') === '1' ? 'checked' : '' ?>><span>Auto-approve dealer applications</span></label>
      <p class="tiny muted">Not recommended — dealer pricing should be reviewed. This setting is recorded for future use.</p>
      <div class="field" style="max-width:220px"><label for="low-stock">Default low-stock threshold</label><input class="input" id="low-stock" name="settings[inventory.low_stock_threshold]" value="<?= $get('inventory.low_stock_threshold', '5') ?>" inputmode="numeric"></div>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save preferences</button></div>
  </form>

<?php elseif ($tab === 'users'): ?>
  <div class="admin-grid-2" style="grid-template-columns:1fr 1fr;align-items:start">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Administrators</h3><span><?= count($admins) ?> account<?= count($admins) === 1 ? '' : 's' ?></span></div></div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Email</th><th>Last sign-in</th></tr></thead>
          <tbody>
            <?php foreach ($admins as $adminUser): ?>
              <tr>
                <td><?= e(trim($adminUser['first_name'] . ' ' . $adminUser['last_name']) ?: '—') ?></td>
                <td class="small"><?= e($adminUser['email']) ?></td>
                <td class="small muted"><?= $adminUser['last_login_at'] ? e(fmt_datetime($adminUser['last_login_at'])) : 'Never' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="admin-card-body"><p class="tiny muted" style="margin:0">Admins can manage products, orders, customers, dealers and settings. Share access carefully.</p></div>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Add administrator</h3></div></div>
      <div class="admin-card-body">
        <form action="<?= url('/admin/settings/users') ?>" method="post">
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="field"><label for="admin-first">First name</label><input class="input" id="admin-first" name="first_name" value="<?= e(old('first_name')) ?>"></div>
            <div class="field"><label for="admin-last">Last name</label><input class="input" id="admin-last" name="last_name" value="<?= e(old('last_name')) ?>"></div>
          </div>
          <div class="field"><label for="admin-new-email">Email</label><input class="input" id="admin-new-email" type="email" name="email" value="<?= e(old('email')) ?>" required></div>
          <div class="field"><label for="admin-new-password">Temporary password</label><input class="input" id="admin-new-password" type="password" name="password" required minlength="8" autocomplete="new-password"><span class="hint">At least 8 characters. Share it securely; they can change it after signing in.</span></div>
          <button class="btn btn-primary" type="submit">Create admin account</button>
        </form>
      </div>
    </div>
  </div>
  <?php clear_input(); ?>
<?php endif; ?>
