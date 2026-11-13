<?php
use App\Core\Csrf;
$errs = errors();
$tabs = ['store' => 'Store', 'dealer' => 'Dealer Program', 'seo' => 'SEO', 'checkout' => 'Checkout', 'paypal' => 'PayPal', 'mail' => 'Email', 'preferences' => 'Preferences', 'chat' => 'AI Assistant', 'users' => 'Admins'];
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
        <div class="field"><label for="announcement-link">Link</label><input class="input" id="announcement-link" name="settings[store.announcement_link]" value="<?= $get('store.announcement_link') ?>" placeholder="/shop"></div>
        <div class="field field-checks-inline">
          <label class="check"><input type="checkbox" name="settings[store.announcement_enabled]" value="1" <?= setting('store.announcement_enabled', '1') === '1' ? 'checked' : '' ?>><span>Show announcement</span></label>
          <label class="check"><input type="checkbox" name="settings[store.newsletter_enabled]" value="1" <?= setting('store.newsletter_enabled', '1') === '1' ? 'checked' : '' ?>><span>Footer newsletter</span></label>
        </div>
      </div>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save store settings</button></div>
  </form>

<?php elseif ($tab === 'dealer'): ?>
  <?php $dCounts = $dealerCounts ?? ['approved' => 0, 'pending' => 0, 'inactive' => 0, 'rejected' => 0]; ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="dealer">
    <div class="admin-card-head">
      <div>
        <h3>Dealer program &amp; trade access</h3>
        <span>Close or reopen the dealer program, or fine-tune applications, public pages and trade pricing independently.</span>
      </div>
      <span class="badge <?= dealer_program_enabled() ? 'badge-green' : 'badge-red' ?>"><?= dealer_program_enabled() ? 'Program open' : 'Program closed' ?></span>
    </div>
    <div class="admin-card-body">
      <div class="dealer-toggle-box">
        <label class="check">
          <input type="checkbox" name="settings[dealer.enabled]" value="1" <?= setting('dealer.enabled', '1') === '1' ? 'checked' : '' ?>>
          <span><strong>Enable Dealer Program (master switch)</strong></span>
        </label>
        <p class="small muted" style="margin:6px 0 0 28px">
          Uncheck this to close the entire dealer program across the storefront. All public dealer pages (<code>/dealer-program</code>, <code>/dealer/apply</code>, <code>/dealer/login</code>), navigation &amp; footer links, account sidebar links, dealer pricing, dealer-only discount codes, sitemap entries and AI assistant references will turn off immediately. Historical dealer records and past orders remain safely preserved.
        </p>
      </div>

      <div class="divider"></div>
      <h4 style="margin:0 0 10px">Granular controls <span class="small muted" style="font-weight:400">(active when the master switch above is enabled)</span></h4>

      <label class="check">
        <input type="checkbox" name="settings[dealer.pages_enabled]" value="1" <?= setting('dealer.pages_enabled', '1') === '1' ? 'checked' : '' ?>>
        <span>Show public Dealer Program landing page &amp; storefront promotional links</span>
      </label>
      <p class="tiny muted" style="margin:2px 0 12px 28px">Controls <code>/dealer-program</code>, homepage trade cards, footer/resource menu links and sitemap inclusion. Turn off if you want invite-only or private trade accounts without advertising the program publicly.</p>

      <label class="check">
        <input type="checkbox" name="settings[dealer.applications_enabled]" value="1" <?= setting('dealer.applications_enabled', '1') === '1' ? 'checked' : '' ?>>
        <span>Accept new online dealer applications (<code>/dealer/apply</code>)</span>
      </label>
      <p class="tiny muted" style="margin:2px 0 12px 28px">Turn off to pause new applications while allowing existing approved dealers to continue signing in and ordering.</p>

      <label class="check">
        <input type="checkbox" name="settings[dealer.pricing_enabled]" value="1" <?= setting('dealer.pricing_enabled', '1') === '1' ? 'checked' : '' ?>>
        <span>Apply dealer tier pricing, fixed dealer prices &amp; dealer-only discount codes</span>
      </label>
      <p class="tiny muted" style="margin:2px 0 12px 28px">Turn off to temporarily charge standard store pricing on all orders without deactivating individual dealer accounts.</p>

      <label class="check">
        <input type="checkbox" name="settings[dealer.auto_approve]" value="1" <?= setting('dealer.auto_approve', '0') === '1' ? 'checked' : '' ?>>
        <span>Auto-approve new dealer applications into the lowest active tier</span>
      </label>
      <p class="tiny muted" style="margin:2px 0 14px 28px">Leave unchecked (recommended) to review each business application manually before granting trade discounts.</p>

      <div class="admin-note">
        <strong>Current trade accounts:</strong>
        <?= (int) ($dCounts['approved'] ?? 0) ?> approved ·
        <?= (int) ($dCounts['pending'] ?? 0) ?> pending ·
        <?= (int) ($dCounts['inactive'] ?? 0) ?> inactive ·
        <?= (int) ($dCounts['rejected'] ?? 0) ?> not approved.
        <a class="bold" href="<?= url('/admin/dealers') ?>" style="margin-left:8px">Manage dealers &amp; individual status →</a>
      </div>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save dealer program settings</button></div>
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
    <div class="admin-card-head"><div><h3>Transactional email</h3><span>Order confirmations, shipping notices, password resets and dealer emails.</span></div><span class="badge <?= $mailConfigured ? 'badge-green' : 'badge-amber' ?>"><?= e(setting('mail.transport', 'mail') === 'resend' ? 'HTTPS API transport' : ($mailConfigured ? 'SMTP settings saved' : 'Review mail configuration')) ?></span></div>
    <div class="admin-card-body">
      <div class="form-row">
        <div class="field"><label for="transport">Transport</label><select class="select" id="transport" name="settings[mail.transport]"><option value="mail"<?= selected('mail', setting('mail.transport', 'mail')) ?>>PHP mail()</option><option value="smtp"<?= selected('smtp', setting('mail.transport', 'mail')) ?>>SMTP</option><option value="resend"<?= selected('resend', setting('mail.transport', 'mail')) ?>>Resend API (HTTPS / port 443)</option></select><span class="hint">Use SMTP if your host allows outbound mail ports. Use Resend HTTPS when those ports are blocked. Test and diagnostics save the form first.</span></div>
        <div class="field"><label for="from-address">From address</label><input class="input" id="from-address" type="email" name="settings[mail.from_address]" value="<?= $get('mail.from_address', (string) \App\Core\Config::get('mail.from_address', 'no-reply@thundermounts.com')) ?>"></div>
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
      <div class="form-row">
        <div class="field"><label for="mail-timeout">Connection timeout (seconds)</label><input class="input" id="mail-timeout" type="number" min="5" max="60" name="settings[mail.timeout]" value="<?= $get('mail.timeout', '15') ?>"></div>
        <div class="field"><label for="mail-resend-key">Resend API key (HTTPS transport only)</label><input class="input" id="mail-resend-key" type="password" name="settings[encrypted.mail_resend_key]" autocomplete="new-password" placeholder="<?= setting('encrypted.mail_resend_key', '') ? 'Saved — leave blank to keep' : 'Enter your Resend API key' ?>"><span class="hint">Encrypted server-side. Verify your sender domain in Resend before sending.</span></div>
      </div>
      <div class="notice notice-info"><div><strong>Connection timed out (110)?</strong> This occurs before login. Confirm the hostname (without a protocol), 465 + SSL or 587 + TLS, and ask the hosting provider whether outbound SMTP is allowed. Increasing the timeout will not bypass a firewall. If ports are blocked, configure Resend over HTTPS instead. Do not disable TLS certificate verification.</div></div>
      <div class="flex wrap mt-2">
        <button class="btn btn-primary" type="submit">Save email settings</button>
        <button class="btn btn-outline" type="submit" formaction="<?= url('/admin/settings/email/diagnose') ?>">Save &amp; diagnose connection</button>
        <a class="btn btn-ghost" href="<?= url('/admin/emails?status=failed') ?>">View failed emails</a>
        <button class="btn btn-outline" type="submit" formaction="<?= url('/admin/settings/email/test') ?>" formnovalidate>Save &amp; send test email</button>
      </div>
    </div>
  </form>

<?php elseif ($tab === 'chat'): ?>
  <form action="<?= url('/admin/settings') ?>" method="post" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="chat">
    <div class="admin-card-head"><div><h3>Website assistant &amp; AI providers</h3><span>Public website answers, with optional AI assistance.</span></div></div>
    <div class="admin-card-body">
      <?php if (setting('chat.provider', 'website') === 'grok'): ?><div class="admin-alert">The previous xAI Grok selection is no longer used. Choose Groq and enter a Groq API key below; old credentials are not reused for a different provider.</div><?php endif; ?>
      <?php if (setting('encrypted.chat.grok_key', '') !== ''): ?><label class="check"><input type="checkbox" name="chat_clear_legacy_grok" value="1"><span>Remove the unused legacy xAI credential</span></label><?php endif; ?>
      <label class="check"><input type="checkbox" name="settings[chat.enabled]" value="1" <?= \App\Core\Settings::bool('chat.enabled', true) ? 'checked' : '' ?>><span>Show the assistant on storefront pages</span></label>
      <div class="field mt-2"><label for="chat-provider">Answer provider</label><select class="select" id="chat-provider" name="settings[chat.provider]">
        <?php foreach (['website' => 'Website content only — no API key needed', 'gemini' => 'Google Gemini', 'groq' => 'Groq', 'openai' => 'OpenAI'] as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= selected(setting('chat.provider', 'website'), $value) ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select></div>
      <p class="muted small">The assistant searches published pages, articles, and public product descriptions. AI mode sends the question and matching excerpts to the selected provider. No account records, order details, or conversation history are included. If a key is missing or the provider fails, the assistant uses website excerpts.</p>
      <?php foreach (['gemini' => 'Google Gemini', 'groq' => 'Groq', 'openai' => 'OpenAI'] as $provider => $label): ?>
        <fieldset style="border:1px solid var(--gray-200);border-radius:8px;padding:20px;margin:20px 0">
          <legend><?= e($label) ?></legend>
          <div class="field"><label for="chat-<?= e($provider) ?>-key">API key</label>
            <input class="input" id="chat-<?= e($provider) ?>-key" type="password" name="settings[encrypted.chat.<?= e($provider) ?>_key]" autocomplete="new-password" maxlength="1000" placeholder="Leave blank to keep the saved key">
            <span class="hint"><?= setting('encrypted.chat.' . $provider . '_key', '') !== '' ? 'A key is saved. It is never displayed here.' : 'No key saved.' ?> Keys are encrypted using APP_KEY. Enter credentials here, never in chat or source code.</span>
          </div>
          <div class="field"><label for="chat-<?= e($provider) ?>-model">Model ID</label><input class="input" id="chat-<?= e($provider) ?>-model" name="settings[chat.<?= e($provider) ?>_model]" value="<?= $get('chat.' . $provider . '_model', \App\Services\ChatService::MODELS[$provider]) ?>" required maxlength="100" pattern="[a-zA-Z0-9._:/-]+"><span class="hint">Use a model available to your provider account. Provider billing and availability apply.</span></div>
          <label class="check"><input type="checkbox" name="chat_clear_<?= e($provider) ?>" value="1"><span>Remove this saved API key when saving</span></label>
        </fieldset>
      <?php endforeach; ?>
      <p class="tiny muted">Production AI mode requires PHP cURL and outbound HTTPS access to api.openai.com, api.groq.com, or generativelanguage.googleapis.com. Website-only mode needs no external AI service. Rate limits: 12 questions/minute per session and 60/hour per IP.</p>
    </div>
    <div class="admin-save-row"><button class="btn btn-primary" type="submit">Save assistant settings</button></div>
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
      <p class="tiny muted">Not recommended — dealer pricing should be reviewed manually. Full dealer controls are in <a href="<?= url('/admin/settings?tab=dealer') ?>">Dealer Program settings</a>.</p>
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
