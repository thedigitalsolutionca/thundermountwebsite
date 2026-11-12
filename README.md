# Thunder Mounts — storefront, dealer portal and admin

Production-ready PHP application for **Thunder Mount Garage Systems LLC** (Simi Valley, CA). It replaces the Wix storefront with a server-rendered catalog, guest and account checkout, a dealer portal, PayPal payments and a complete admin/CMS.

- **Storefront:** home, shop with filters, product pages, cart drawer, checkout, order lookup, content pages, contact, newsletter.
- **Accounts:** register, sign in, password reset, order history, reorder, saved addresses, profile and password.
- **Dealer program:** application, manual approval, pricing tiers with percentage discounts, portal dashboard and dealer order history.
- **Checkout:** server-side shipping quotes and tax, PayPal (card, PayPal balance, Apple Pay, Google Pay) or optional “pay by invoice”, stock checks and order emails.
- **Admin:** dashboard, orders and invoices, catalog and images, inventory movements, categories, customers, dealers and tiers, discounts, reviews, contact inbox, pages, newsletter export, shipping zones/methods, tax rates and settings.

Catalog names, SKUs and prices come from the approved Thunder Mounts catalog. Shipping rates, tax rules, dealer tiers, inventory counts, payment credentials and SMTP credentials are **not invented** — they are configured by the merchant in the admin.

---

## Requirements

| Component | Minimum |
| --- | --- |
| PHP | 8.2 (8.3 recommended) with `pdo_mysql`, `openssl`, `mbstring`, `gd`, `fileinfo` |
| Database | MySQL 8 / MariaDB 10.4+ (SQLite is supported for local testing) |
| Web server | Apache with `mod_rewrite`, or Nginx with an equivalent front-controller rule |
| HTTPS | Required for PayPal, Apple Pay and secure cookies |

No Composer, Node or build step is required. The app is plain PHP 8 with a PSR-4 autoloader.

---

## Quick start (local, SQLite)

```bash
git clone <repo> thundermountwebsite
cd thundermountwebsite
cp .env.example .env
```

Edit `.env` for local use:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=base64:REPLACE_WITH_32_RANDOM_BYTES
COOKIE_SECURE=false

DB_DRIVER=sqlite
DB_SQLITE_PATH=/absolute/path/to/thundermountwebsite/storage/dev.sqlite
```

Generate a key:

```bash
php -r "echo 'base64:', base64_encode(random_bytes(32)), PHP_EOL;"
```

Install the schema and seed data:

```bash
php install.php --name="Store Owner" --email=owner@example.com --password="choose-a-long-password"
```

Or open `http://localhost:8080/install.php` and use the form.

Serve the site from `public/`:

```bash
php -S 0.0.0.0:8080 -t public
```

- Storefront: <http://localhost:8080>
- Admin: <http://localhost:8080/admin/login>

The installer creates 6 categories, 19 products, product images, 9 content pages and 43 settings. It refuses to run again once an administrator exists.

> SQLite is for local development and automated testing. Production should use MySQL.

---

## Production deployment

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the Hostinger walkthrough, `public/` document-root setup, MySQL, `.env`, permissions, PayPal webhooks, SMTP, cron and the go-live checklist.

Short version:

1. Upload the project outside `public_html`, or point the domain document root at `public/`.
2. Create a MySQL database and copy the credentials into `.env`.
3. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://www.thundermounts.com`, and a strong `APP_KEY`.
4. Run `php install.php ...` once, then **delete `install.php`**.
5. In **Admin → Shipping** add real zones and rates; in **Admin → Tax** add real rates.
6. In **Admin → Settings** add PayPal and SMTP credentials.
7. Configure the PayPal webhook (see below).

---

## Payments

### PayPal (server-side)

- The browser never supplies prices. `CheckoutController` recalculates the cart, shipping and tax on the server before creating a PayPal order.
- Orders use `intent=CAPTURE`, a server-generated order number and an idempotency key.
- Capture is verified server-side before an order becomes paid.
- `POST /webhooks/paypal` verifies the PayPal signature through the API, stores every event in `paypal_webhook_events` (unique `event_id`, so retries are idempotent) and reconciles captures/refunds.
- Apple Pay and Google Pay are rendered through the PayPal JS SDK (`paypal.Applepay()` / `paypal.Googlepay()`), not hand-built buttons.

Subscribe the webhook to:

- `PAYMENT.CAPTURE.COMPLETED`
- `PAYMENT.CAPTURE.DENIED`
- `PAYMENT.CAPTURE.REFUNDED`

Webhook URL: `https://www.thundermounts.com/webhooks/paypal`

Until `encrypted.paypal.webhook_id` is set, the endpoint returns 503 (so PayPal retries) instead of trusting an unverified body.

### Pay by invoice

If PayPal is not ready, enable **Settings → Checkout → Allow customers to place an order and pay by invoice** and provide instructions. Orders are created unpaid, stock is reduced, and the customer receives payment instructions. The admin can later record payment.

---

## Email

Transactional email uses SMTP when configured, otherwise PHP `mail()`. Every attempt is written to `email_log` with status and error, visible through the admin's email test and database.

Templates live in `app/Views/emails/`:

- welcome, password reset
- order confirmed, manual-payment instructions, shipped, status update, admin new-order notification
- dealer received, approved, rejected, admin notification
- contact received, admin notification

SMTP is configured in **Admin → Settings → Email** or through `.env` (`MAIL_*`).

---

## Shipping and tax

Shipping is rule-based, not hard-coded:

- Zones match country codes and state/province lists (`*` = any state).
- Methods can be `flat`, `weight` (`rate + ceil(weight) × per_unit`) or `free`.
- Each method supports `min_weight`, `max_weight`, `free_over` (subtotal threshold), estimated business days and ordering.

Tax rates are matched by country and exact state. `tax.shipping_taxable` decides whether shipping is part of the taxable base. If tax is enabled but no rate matches, checkout is blocked and asks the customer to call — it never silently charges 0%.

---

## Project structure

```
app/
  bootstrap.php          front controller bootstrap
  Config/config.php      config defaults + env overrides
  Core/                  Env, Config, Database, Router, Request, Response, View,
                         Session, Csrf, Auth, Validator, Money, Str, Settings,
                         Crypt, Mailer, Paginator, Icons, Logger
  Services/              catalog, cart, checkout, orders, discounts, shipping, tax,
                         accounts, dealers, content, uploads, inventory, PayPal
  Controllers/Storefront/
  Controllers/Admin/
  Views/
    layouts/             storefront, blank, admin
    storefront/          pages, partials, auth, account, checkout, dealer
    admin/               every admin screen
    emails/              transactional email templates
    errors/              404 and 500
database/
  schema.sql             MySQL schema
  schema.sqlite.sql      SQLite schema for local tests
  seed.php               canonical catalog/content/settings seed
routes/
  web.php                storefront route table
  admin.php              admin route table
public/
  index.php              front controller
  .htaccess              rewrites, HTTPS, caching
  assets/css/app.css     storefront + admin styles
  assets/js/app.js       storefront interactions
  assets/js/admin.js     admin interactions
  uploads/               product/dealer uploads (writeable; never executable)
install.php              one-time installer
bin/
  retry-mail.php         cron: retry recently failed transactional email
  maintenance.php        cron: prune carts, sessions, webhook payloads, old email bodies
```

### Data conventions

- Money is stored as `DECIMAL(10,2)` and all arithmetic happens in **integer cents** (`App\Core\Money`).
- Prices are always resolved on the server, including dealer tier discounts.
- `specs` and `box_contents` are JSON (`{"Label":"value"}` and `["item", …]`).
- Sensitive settings use the `encrypted.` prefix and AES-256-GCM (`App\Core\Crypt`) with `APP_KEY`.
- Order numbers use `TM-yymmdd-XXXXXX`.
- Hidden products are excluded from storefront queries; admin can still see them.
- Deleting a product that appears on an order hides it instead of breaking order history.

---

## Housekeeping (optional cron)

Two small CLI scripts are included for shared hosting, where no daemon can run:

```bash
php bin/maintenance.php    # prune abandoned carts, old sessions/cache/webhook payloads
php bin/retry-mail.php     # retry failed email from the last 7 days, once each
```

Schedule them in hPanel → Cron Jobs (see `DEPLOYMENT.md`). Both are safe to run manually and never touch orders, customers or paid invoices.

## Security

- Prepared statements everywhere (`App\Core\Database`).
- CSRF tokens on every state-changing form (`App\Core\Csrf`).
- Password hashing with `password_hash()`; login throttling through `login_attempts`.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` under HTTPS.
- Session files are kept under `storage/sessions`.
- Role checks (`customer`, `dealer`, `admin`) are enforced in controllers, not just menus.
- Uploads are validated by MIME/extension/size and stored with random names; `public/uploads/.htaccess` disables code execution.
- CSP, HSTS, `X-Frame-Options`, `nosniff`, referrer and permissions policies in `Response::securityHeaders()`.
- `install.php` refuses to run when an administrator already exists.

---

## Testing

There is no test framework dependency. The app was exercised end-to-end with a real request handler against SQLite:

- all public GET routes, 404s and XML endpoints
- registration, login/logout, password reset request
- cart add/update/drawer, shipping quote, checkout, order creation and success page
- order lookup, admin order list/detail/invoice, mark-paid, tracking, notes, status changes
- product/category/discount/page/shipping/tax/tier CRUD
- inventory adjustments, review moderation, contact inbox, newsletter export
- dealer application, approval, tier assignment and dealer pricing
- email rendering for all 13 transactional templates

To repeat the smoke test, serve the app against a SQLite database, add a shipping method and tax rate, then walk the flows above. (The ad-hoc harness is not committed; it depends on a local PHP-WASM runtime.)

---

## What the client still needs to supply

These are deliberately blank, because they are business decisions or credentials:

- PayPal **live** client ID/secret and webhook ID (sandbox values can be used first).
- SMTP host/user/password (Hostinger email works).
- Real shipping zones, methods and rates.
- Real tax rules (state/county/city as applicable).
- Final legal copy for terms, privacy, returns and warranty.
- A high-resolution logo file if the wordmark should be replaced by a raster logo.
- Confirmation of hours, phone numbers and the public notification email.
- Dealer tier percentages and minimum-order rules.

Until shipping and tax are configured, checkout intentionally shows a “contact us” state instead of guessing a price.

---

© Thunder Mount Garage Systems LLC. Built as a custom PHP application; no page-builder or SaaS dependency.
