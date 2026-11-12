# Deployment guide — Hostinger (Apache + MySQL)

This is the go-live runbook for **thundermounts.com**. It assumes a Hostinger shared/Premium plan with hPanel, Apache, PHP 8.3 and MySQL. The steps are the same on any cPanel-style host; only the names of the database and file-manager screens differ.

---

## 0. Before you start

Collect:

- [ ] PayPal **live** client ID and secret (start with sandbox if you prefer)
- [ ] A PayPal webhook ID after creating the webhook (step 8)
- [ ] SMTP mailbox credentials (Hostinger email: `thunder@thundermounts.com`)
- [ ] Final shipping zones, methods, rates and handling time
- [ ] Final tax rules (state and, if applicable, local rates)
- [ ] Dealer tier percentages and minimum order totals
- [ ] Final legal copy for terms, privacy, returns and warranty

The application ships with **no shipping rate and no tax rate**, because those are business decisions. Checkout will refuse to guess; configure them before announcing the store.

---

## 1. Create the database

1. hPanel → **Databases → MySQL Databases**.
2. Create database `uXXXXXX_thunder` and user `uXXXXXX_thunder`.
3. Save host (`localhost` on Hostinger), database name, user and password.
4. Leave phpMyAdmin open; you may need it for troubleshooting.

MySQL 8 / MariaDB 10.4 or newer is required. The schema uses foreign keys and `utf8mb4`.

---

## 2. Upload the files

The web root must be **`public/`**, not the project root. Two supported layouts:

**Option A — recommended: domain document root points to `public/`**

```
/home/uXXXXXX/domains/thundermounts.com/
  app/  database/  routes/  storage/  install.php  .env
  public/            <-- document root
```

In hPanel → **Websites → Advanced → Document root**, set the domain to `.../public`.

**Option B — upload everything into `public_html/` and use the bundled `.htaccess`**

Only use this if Hostinger will not let you change the document root. The root `.htaccess` in `public/` assumes `public/` is the web root, so Option A is strongly preferred. Never expose `.env`, `storage/` or `database/` through the browser.

Upload with SSH + `rsync`/`git`, or zip the project in hPanel File Manager and extract. Do **not** upload:

- `.git/`
- `storage/dev.sqlite`, `storage/installed.lock`
- local logs in `storage/logs/`

Create these directories after upload if they are missing, and make them writeable:

```bash
mkdir -p storage/logs storage/cache storage/sessions public/uploads/products public/uploads/dealers
chmod -R 775 storage public/uploads
```

---

## 3. Configure `.env`

Copy `.env.example` to `.env` in the project root and set:

```dotenv
APP_NAME="Thunder Mounts"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.thundermounts.com

# Generate with:
# php -r "echo 'base64:', base64_encode(random_bytes(32)), PHP_EOL;"
APP_KEY=base64:PASTE_THE_GENERATED_KEY

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXX_thunder
DB_USERNAME=uXXXXXX_thunder
DB_PASSWORD=your-database-password

COOKIE_SECURE=true
SESSION_LIFETIME=120
```

Rules:

- `APP_KEY` must be set before entering PayPal/SMTP credentials in the admin; they are encrypted with it. **Changing `APP_KEY` after saving credentials makes them unreadable** — re-enter them if you rotate the key.
- `APP_DEBUG=false` in production. Debug pages reveal paths and queries.
- `APP_URL` must be the canonical HTTPS URL, including `www` if that is the canonical host. PayPal return URLs and emails use it.
- Do not commit `.env`.

---

## 4. PHP version and extensions

hPanel → **Advanced → PHP Configuration**:

- PHP **8.3** (8.2 minimum)
- Extensions: `pdo_mysql`, `openssl`, `mbstring`, `gd`, `fileinfo`, `json`, `session`, `ctype`
- `display_errors = Off`, `log_errors = On`
- `upload_max_filesize` and `post_max_size` ≥ 8M (product images)
- `max_execution_time` ≥ 60

The app sets its own security headers; do not add duplicate `X-Frame-Options`/CSP headers in hPanel.

---

## 5. Enable HTTPS

1. hPanel → **SSL** → install the free Let’s Encrypt certificate for the domain and `www`.
2. Turn on **Force HTTPS**.
3. `public/.htaccess` also redirects `http → https` using `X-Forwarded-Proto`, so proxied requests are handled correctly.

Verify: `https://www.thundermounts.com` loads, `http://` redirects, and there is no mixed-content warning.

---

## 6. Run the installer

The installer creates the schema, seeds the catalog/content/settings and creates the first administrator.

**With SSH (preferred):**

```bash
cd ~/domains/thundermounts.com
php install.php \
  --name="Store Owner" \
  --email="owner@thundermounts.com" \
  --password="a-long-unique-password"
```

**Without SSH:** temporarily enable the web installer by adding this line to `public/.htaccess`:

```apache
SetEnv ALLOW_INSTALL 1
```

Open `https://www.thundermounts.com/install.php`, complete the form, then **remove that line** and delete `public/install.php`. The web installer is disabled otherwise.

After it finishes:

1. Confirm `storage/installed.lock` exists.
2. **Delete `install.php` and `public/install.php`** (or at least confirm the installer refuses to run: it checks for an existing admin).
3. Sign in at `https://www.thundermounts.com/admin/login`.
4. Change the seeded admin password if it was shared in chat or email.

If the installer reports that tables already exist, the database is not empty. Drop all tables in phpMyAdmin and re-run, or point `.env` at a fresh database.

---

## 7. Configure the store

Do these in order; later steps depend on earlier ones.

1. **Settings → Store**: legal name, phone, email, address, hours, announcement.
2. **Settings → Email**: SMTP host/port/encryption/user/password/from address. Send a test email.
3. **Shipping**: set the base (ship-from) location at the top of the page, then create a zone for United States (states `*` or the ones you serve) and methods:
   - flat rate (e.g. `$12`)
   - free over a subtotal threshold
   - weight-based if you ship by weight
   Add estimated business days. Create a second zone for Canada only if you ship there.
4. **Tax**: add a rate per destination state you must collect in. Set `tax.shipping_taxable` to match your state rules. If tax is enabled with no active rates at all, checkout pauses and asks the customer to call.
5. **Dealers**: create tiers (name, discount %, minimum order), set `dealer.auto_approve` off to review applications manually.
6. **Products**: verify prices, SKUs, stock and images; set `is_active`; mark special-order items.
7. **Discounts / Pages / Reviews / Messages**: review the seeded content and adjust legal copy.
8. **Settings → Checkout**: leave manual/invoice payments **off** unless you intend to take orders without PayPal.

---

## 8. PayPal

### 8.1 Credentials

**Admin → Settings → Payments**:

- Mode: `live` (use `sandbox` until the live account is verified)
- Client ID and secret from the PayPal developer dashboard
- Enable PayPal

Credentials are stored encrypted (`encrypted.paypal.client_id`, `encrypted.paypal.client_secret`). The secret is never rendered back into the page.

### 8.2 Webhook

1. PayPal Developer Dashboard → your app → **Webhooks → Add webhook**.
2. URL: `https://www.thundermounts.com/webhooks/paypal`
3. Events:
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.DENIED`
   - `PAYMENT.CAPTURE.REFUNDED`
4. Copy the **Webhook ID** into **Admin → Settings → Payments → PayPal webhook ID**.

The endpoint verifies the signature with PayPal before acting; it never trusts the posted JSON. Events are stored in `paypal_webhook_events` with a unique `event_id`, so PayPal retries are safe. Until the webhook ID is configured, the endpoint returns HTTP 503 so PayPal keeps retrying rather than silently dropping events.

### 8.3 Apple Pay and Google Pay

- Google Pay works through the PayPal JS SDK once PayPal is enabled.
- Apple Pay additionally requires the domain to be registered with PayPal (`applepay.cdn-apple.com` validates the domain-association file). Use the domain-association route exposed by the app, then add the domain in the PayPal dashboard.
- Test on a real device/browser: Safari for Apple Pay, Chrome for Google Pay. Sandbox accounts cannot complete Apple Pay.

### 8.4 Smoke test

1. Add a product to the cart and check out with a sandbox buyer account.
2. Confirm the order appears in **Admin → Orders** as paid, stock decreased, and the confirmation email arrives.
3. Refund the capture in PayPal and confirm the webhook marks the order refunded.
4. Try an unsigned POST to `/webhooks/paypal`; it must return `400 {"error":"Invalid payload"}`.

---

## 9. Email

Configure SMTP in **Admin → Settings → Email**, then send a test message. If mail fails:

- Check `storage/logs/` for the error.
- Check the `email_log` table (`status`, `error`).
- Hostinger usually requires the mailbox user in full (`thunder@thundermounts.com`) and port `465` (SSL) or `587` (TLS).
- Add SPF/DKIM records in hPanel → Email → DNS if messages land in spam.

Every transactional email is sent through one `Mailer`; templates are in `app/Views/emails/`.

---

## 10. Cron (optional)

Hostinger supports cron jobs. The app does not require a daemon, but two jobs are recommended:

```cron
# every 15 minutes — retry failed transactional email
*/15 * * * * /usr/bin/php /home/uXXXXXX/domains/thundermounts.com/bin/retry-mail.php >> /home/uXXXXXX/domains/thundermounts.com/storage/logs/cron.log 2>&1

# daily 03:15 — prune expired sessions/carts and old webhook rows
15 3 * * * /usr/bin/php /home/uXXXXXX/domains/thundermounts.com/bin/maintenance.php >> /home/uXXXXXX/domains/thundermounts.com/storage/logs/cron.log 2>&1
```

Both scripts exit non-zero on failure, so cron email alerts work. PayPal also retries webhooks on its own, and every failure is visible in the admin.

---

## 11. Backups

- hPanel → **Files → Backups** for daily snapshots.
- Additionally export MySQL weekly (phpMyAdmin → Export) and download `public/uploads/` monthly.
- Test a restore once before launch. Backups you have never restored are not backups.

---

## 12. Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`
- [ ] `install.php` deleted; `storage/installed.lock` present
- [ ] HTTPS forced; certificate valid for `www` and apex
- [ ] `storage/` and `public/uploads/` not browsable; `.env` returns 403
- [ ] Admin password changed; test accounts removed (customer `buyer@example.com`, `second-admin@example.com`, sample orders)
- [ ] Real shipping zones/rates and tax rates entered
- [ ] PayPal live credentials + webhook ID saved; test capture and refund
- [ ] SMTP test email delivered to Gmail and Outlook
- [ ] 404 page, contact form, newsletter signup, password reset all work
- [ ] Product images optimized (< 300 KB each); favicon present
- [ ] Legal pages reviewed by the client
- [ ] DNS: `www` and apex both resolve; canonical host matches `APP_URL`
- [ ] Google Search Console / sitemap submitted (`/sitemap.xml`)
- [ ] Monitoring/uptime check on the homepage and `/admin/login`

---

## 13. Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Blank page / 500 | PHP version, missing extension, or `.env` unreadable | Set `APP_DEBUG=true` temporarily; check `storage/logs/` and Hostinger error log |
| “Database connection failed” | Wrong credentials or host | Recheck hPanel database user privileges; Hostinger uses `localhost` |
| 404 on every page except `/` | `mod_rewrite` off or document root wrong | Enable rewrite; point document root at `public/` |
| Redirect loop | HTTPS forced both at proxy and `.htaccess` | Keep one redirect; the bundled rule respects `X-Forwarded-Proto` |
| CSS/JS missing | Document root is project root, not `public/` | Fix document root or move assets |
| Checkout says “contact us” | No shipping method or tax rate matches | Configure Shipping/Tax in admin |
| PayPal button missing | PayPal disabled or credentials empty | Settings → Payments; hard-refresh after saving |
| Webhook 503 | Webhook ID not set | Add it in Settings → Payments |
| Uploads fail | `public/uploads` not writeable | `chmod -R 775 public/uploads` |
| Session lost after login | `storage/sessions` not writeable or `APP_URL` host mismatch | Fix permissions; use one canonical host |
| Emails not delivered | SMTP wrong or SPF/DKIM missing | Check `email_log`, then DNS |

For a deeper error, temporarily set `APP_DEBUG=true`, reproduce, then set it back to `false`. Never leave debug mode on in production.

---

## 14. Updating the site

1. Back up the database and `public/uploads/`.
2. Upload changed files (do not overwrite `.env` or `storage/`).
3. If the schema changed, run the migration notes shipped with the release **before** loading the site.
4. Clear `storage/cache/` and `storage/sessions/` if templates or sessions behave oddly.
5. Smoke-test: home, shop, product, cart, checkout, admin login.

There is no build step and no `composer install`; deployment is file copy plus database.
