# Deploy from GitHub (Docker)

GitHub stores and builds the source; it does not host PHP by itself. This repo includes a production Docker image (`Dockerfile`) so any GitHub-connected Docker host can deploy it automatically.

**Recommended:** Railway, because it provides GitHub deploys, a MySQL database and a persistent volume in one place. Render, Fly.io, DigitalOcean App Platform and a VPS with Coolify work with the same image.

> GitHub Pages, Netlify and Vercel static hosting will **not** run this app: it is server-rendered PHP with a database, sessions and uploads.

---

## Option A — Railway (recommended)

### 1. Create the project

1. Sign in at <https://railway.app> and choose **New Project → Deploy from GitHub repo**.
2. Select `thedigitalsolutionca/thundermountwebsite` and the `main` branch.
3. Railway detects the `Dockerfile` and builds it. The first build takes a few minutes.

### 2. Add MySQL

1. In the project canvas choose **New → Database → MySQL**.
2. Open the app service → **Variables**. Add the database values, either with Railway’s variable-reference picker or by copying from the MySQL service:

| Variable | Value |
|---|---|
| `DB_DRIVER` | `mysql` |
| `DB_HOST` | MySQL host |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | MySQL database |
| `DB_USERNAME` | MySQL user |
| `DB_PASSWORD` | MySQL password |

The app also accepts Railway’s native `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER` and `MYSQLPASSWORD` names if you prefer.

### 3. Add application variables

| Variable | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...` — generate once, never change it |
| `COOKIE_SECURE` | `true` |
| `PORT` | `8080` |
| `ALLOW_INSTALL` | `1` for the first deploy only |

Generate `APP_KEY` locally:

```bash
php -r "echo 'base64:', base64_encode(random_bytes(32)), PHP_EOL;"
```

or with OpenSSL:

```bash
printf 'base64:%s\n' "$(openssl rand -base64 32)"
```

### 4. Add persistent upload storage

1. Open the app service → **Settings → Volumes → Add Volume**.
2. Mount path: **`/var/www/html/public/uploads`**
3. Redeploy.

Without this volume, product/dealer images disappear on every redeploy. Sessions and logs may stay ephemeral; users are simply signed out after a redeploy.

### 5. Generate the public URL

1. App service → **Settings → Networking → Generate Domain**.
2. Add `APP_URL=https://your-domain.up.railway.app` under Variables.
3. Redeploy so canonical links and emails use the right host.

### 6. Install the database

With `ALLOW_INSTALL=1`, open:

```text
https://your-domain.up.railway.app/install.php
```

Create the administrator, then:

1. Set `ALLOW_INSTALL=0` (or delete the variable) and redeploy.
2. Sign in at `/admin/login`.
3. Configure Shipping, Tax, Email/SMTP, PayPal sandbox and Checkout.

Alternatively use the Railway shell:

```bash
php install.php --name="Store Owner" --email=owner@example.com --password="a-long-password"
```

### 7. Post-deploy checks

- [ ] `/` and `/shop` load over HTTPS
- [ ] `/admin/login` works and the admin password is changed
- [ ] `ALLOW_INSTALL` is `0`
- [ ] Product image upload survives a redeploy (volume mounted)
- [ ] Shipping base location, zone and method saved
- [ ] Tax rate saved and visible at checkout
- [ ] SMTP test email arrives
- [ ] PayPal **sandbox** payment creates a paid order
- [ ] `APP_KEY` is backed up; changing it breaks stored PayPal/SMTP credentials

---

## If Railway shows 502 Bad Gateway

The domain is reachable but Railway cannot reach a port inside the container. Check these in order:

1. **Public networking target port**
   App service → **Settings → Networking → Public Networking**. The target port must be **8080**. If it is `3000`, `80`, or blank, edit it to `8080`.
2. **`PORT` variable**
   App service → **Variables** → add `PORT=8080`. The entrypoint uses `PORT` and falls back to `8080`, but setting it prevents a mismatch with the platform.
3. **Service root**
   The service root directory must be the repository root, not `public/`. If the root is `public/`, Railway cannot find the `Dockerfile`.
4. **Build settings**
   Settings → Build: builder **Dockerfile**, Dockerfile path **`Dockerfile`**.
5. **Deployment logs**
   Deployments → latest deployment → **View Logs**. A successful start logs:
   ```text
   Thunder Mounts: starting Apache on 0.0.0.0:8080
   ```
   If the container exits before that line, the build or a startup command failed; copy the first error from the log.
6. **Static probe**
   Open `https://your-domain/healthz.txt`. It must return `ok` without touching PHP or the database. If it returns 502, the problem is the container/port, not the application.
7. **Volume path**
   The uploads volume must mount at `/var/www/html/public/uploads`. Mounting it at `/var/www/html` hides the application.

If `/healthz.txt` works but `/` does not, the container is reachable and the issue is configuration (usually database variables or `APP_KEY`). Set `APP_DEBUG=true` temporarily to see the error.

---

## Option B — Render

1. **New → Web Service → Build and deploy from a Git repository**.
2. Select the repo; Render detects the `Dockerfile`.
3. Add a MySQL database from another provider (Render’s managed database is PostgreSQL, which this app does not use) or a MySQL-compatible service.
4. Set the same `APP_*` and `DB_*` variables.
5. Add a persistent disk mounted at `/var/www/html/public/uploads`.
6. Set `APP_URL` to the Render URL (`RENDER_EXTERNAL_URL` is also detected automatically).
7. Set `ALLOW_INSTALL=1`, open `/install.php`, install, then set it to `0`.

Render injects `$PORT`; the container entrypoint already configures Apache to use it.

---

## Option C — Any Docker host / VPS

```bash
git clone https://github.com/thedigitalsolutionca/thundermountwebsite.git
cd thundermountwebsite

docker build -t thunder-mounts .

docker run -d --name thunder-mounts \
  -p 8080:8080 \
  -e PORT=8080 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_URL=https://shop.example.com \
  -e APP_KEY="base64:..." \
  -e DB_DRIVER=mysql \
  -e DB_HOST=mysql \
  -e DB_PORT=3306 \
  -e DB_DATABASE=thunder \
  -e DB_USERNAME=thunder \
  -e DB_PASSWORD="..." \
  -e ALLOW_INSTALL=1 \
  -v thunder_uploads:/var/www/html/public/uploads \
  thunder-mounts
```

Then open `/install.php`, install, and recreate the container with `ALLOW_INSTALL=0`.

Put Caddy/Nginx or the platform proxy in front for HTTPS. The app expects `X-Forwarded-Proto: https` when TLS terminates at the proxy.

For automatic deploys on a VPS, install [Coolify](https://coolify.io) or use a GitHub Actions workflow that builds the image and restarts the container. Railway/Render already do this from the GitHub integration.

---

## How the image works

- `php:8.3-apache` with `pdo_mysql`, `gd` (JPEG/PNG/WebP) and `mbstring`
- Apache document root is `public/`; `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`
- `docker/entrypoint.sh` makes Apache listen on **80, 8080 and `$PORT`**, fixes permissions for the uploads volume and logs the active ports
- `/healthz.txt` is a static probe that works even before the database is installed
- `.dockerignore` excludes `.env`, SQLite files, logs, sessions and uploaded media from the image
- The application reads standard environment variables; no `.env` file is required in the container.

## Important

- Do **not** bake `.env` or real credentials into the image. Use platform variables.
- Use a separate database for staging and production.
- Set `APP_URL` before testing PayPal or password-reset emails.
- Back up `APP_KEY` and the uploads volume/database.
- Keep `ALLOW_INSTALL` unset/`0` after installation.
