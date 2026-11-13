<?php

declare(strict_types=1);

/**
 * Thunder Mounts installer.
 *
 * CLI:  php install.php --name="Store Owner" --email=owner@example.com --password="..."
 * Web:  open /install.php once, create the first admin, then delete this file.
 *
 * The installer refuses to run twice and never overwrites an installed store.
 */

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;

define('TM_ROOT', __DIR__);
define('TM_APP', __DIR__ . '/app');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = TM_APP . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$isCli = PHP_SAPI === 'cli';

Env::load(TM_ROOT . '/.env');
$config = require TM_APP . '/Config/config.php';
Config::load($config);

$driver = (string) Config::get('database.driver', 'mysql');
$lockFile = TM_ROOT . '/storage/installed.lock';
$errors = [];
$done = false;

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** @return array<int, string> */
function schema_statements(string $driver): array
{
    $file = TM_ROOT . '/database/' . ($driver === 'sqlite' ? 'schema.sqlite.sql' : 'schema.sql');
    if (!is_file($file)) {
        throw new RuntimeException('Schema file not found: ' . $file);
    }
    $sql = (string) file_get_contents($file);
    $statements = [];
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $statements[] = $statement;
    }
    return $statements;
}

function already_installed(): bool
{
    global $lockFile;
    if (is_file($lockFile)) {
        return true;
    }
    try {
        if (Database::tableExists('users')) {
            return (int) Database::value('SELECT COUNT(*) FROM users WHERE role = ?', ['admin'], 0) > 0;
        }
    } catch (Throwable) {
        return false;
    }
    return false;
}

function seed_store(): array
{
    $seed = require TM_ROOT . '/database/seed.php';
    $now = date('Y-m-d H:i:s');
    $counts = ['categories' => 0, 'products' => 0, 'images' => 0, 'pages' => 0, 'settings' => 0];

    $categoryIds = [];
    foreach ($seed['categories'] ?? [] as $category) {
        $categoryIds[$category['slug']] = Database::insert('categories', [
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
            'image' => $category['image'] ?? '',
            'sort_order' => (int) ($category['sort_order'] ?? 0),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $counts['categories']++;
    }

    foreach ($seed['products'] ?? [] as $product) {
        $productId = Database::insert('products', [
            'category_id' => $categoryIds[$product['category'] ?? ''] ?? null,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'sku' => $product['sku'],
            'model' => $product['model'] ?? $product['sku'],
            'brand' => $product['brand'] ?? 'Thunder Mounts',
            'short_description' => $product['short_description'] ?? '',
            'description' => $product['description'] ?? '',
            'specs' => json_encode($product['specs'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'box_contents' => json_encode($product['box_contents'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'compatibility' => $product['compatibility'] ?? '',
            'price' => $product['price'],
            'dealer_price' => $product['dealer_price'] ?? null,
            'cost' => $product['cost'] ?? null,
            'stock_qty' => (int) ($product['stock_qty'] ?? 0),
            'low_stock_threshold' => (int) ($product['low_stock_threshold'] ?? 5),
            'manage_stock' => (int) ($product['manage_stock'] ?? 0),
            'stock_status' => $product['stock_status'] ?? 'in_stock',
            'weight' => $product['weight'] ?? null,
            'material' => $product['material'] ?? '',
            'finish' => $product['finish'] ?? '',
            'is_featured' => (int) ($product['is_featured'] ?? 0),
            'is_active' => 1,
            'is_taxable' => 1,
            'meta_title' => $product['meta_title'] ?? '',
            'meta_description' => $product['meta_description'] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $counts['products']++;

        foreach (($product['images'] ?? []) as $index => $image) {
            Database::insert('product_images', [
                'product_id' => $productId,
                'path' => $image['path'],
                'alt' => $image['alt'] ?? '',
                'sort_order' => $index,
            ]);
            $counts['images']++;
        }
    }

    foreach ($seed['pages'] ?? [] as $page) {
        Database::insert('pages', [
            'slug' => $page['slug'],
            'title' => $page['title'],
            'content' => $page['content'] ?? '',
            'meta_title' => $page['meta_title'] ?? '',
            'meta_description' => $page['meta_description'] ?? '',
            'is_active' => 1,
            'show_in_footer' => (int) ($page['show_in_footer'] ?? 0),
            'sort_order' => (int) ($page['sort_order'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $counts['pages']++;
    }

    foreach (\App\Services\GalleryService::DEFAULT_ITEMS as $galleryItem) {
        Database::insert('gallery_items', [
            'media_type' => $galleryItem['media_type'],
            'video_source' => '',
            'title' => $galleryItem['title'],
            'caption' => $galleryItem['caption'],
            'category' => $galleryItem['category'],
            'media_path' => $galleryItem['media_path'],
            'embed_url' => '',
            'poster_path' => '',
            'alt_text' => $galleryItem['alt_text'],
            'sort_order' => $galleryItem['sort_order'],
            'is_featured' => $galleryItem['is_featured'],
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    foreach ($seed['settings'] ?? [] as $setting) {
        Database::execute(
            'INSERT INTO settings (group_name, `key`, value, updated_at) VALUES (?, ?, ?, ?)',
            [$setting[2] ?? 'general', $setting[0], $setting[1], $now]
        );
        $counts['settings']++;
    }

    return $counts;
}

function create_admin(string $name, string $email, string $password): void
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    $now = date('Y-m-d H:i:s');
    Database::insert('users', [
        'email' => strtolower(trim($email)),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'first_name' => $first,
        'last_name' => $last,
        'role' => 'admin',
        'status' => 'active',
        'email_verified_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function run_install(string $name, string $email, string $password, string $driver): array
{
    global $lockFile;
    if (strlen($password) < 10) {
        throw new InvalidArgumentException('Choose a password of at least 10 characters.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    if (trim($name) === '') {
        throw new InvalidArgumentException('Enter the store owner name.');
    }

    Database::init((array) Config::get('database'));

    if ($driver === 'sqlite') {
        Database::pdo()->exec('PRAGMA foreign_keys = OFF');
    } else {
        Database::pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    foreach (schema_statements($driver) as $statement) {
        Database::pdo()->exec($statement);
    }

    if ($driver === 'sqlite') {
        Database::pdo()->exec('PRAGMA foreign_keys = ON');
    } else {
        Database::pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    if (already_installed()) {
        throw new RuntimeException('This store already has an administrator account, so the installer stopped without changing anything.');
    }

    Database::pdo()->beginTransaction();
    try {
        $counts = seed_store();
        create_admin($name, $email, $password);
        Database::pdo()->commit();
    } catch (Throwable $e) {
        Database::pdo()->rollBack();
        throw $e;
    }

    @mkdir(TM_ROOT . '/storage', 0775, true);
    file_put_contents($lockFile, 'Installed ' . date('c') . ' by ' . $email . PHP_EOL);

    return $counts;
}

// ---------------------------------------------------------------------------
// Handle requests
// ---------------------------------------------------------------------------
if ($isCli) {
    $options = [];
    foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
        if (preg_match('/^--([a-z-]+)=(.*)$/', $arg, $m)) {
            $options[$m[1]] = $m[2];
        }
    }
    if (isset($options['hash'])) {
        echo password_hash($options['hash'], PASSWORD_DEFAULT), PHP_EOL;
        exit(0);
    }
    if (is_file($lockFile)) {
        fwrite(STDERR, "Already installed. Delete storage/installed.lock to reinstall on an empty database.\n");
        exit(1);
    }
    try {
        $counts = run_install(
            (string) ($options['name'] ?? getenv('INSTALL_ADMIN_NAME') ?: 'Store Owner'),
            (string) ($options['email'] ?? getenv('INSTALL_ADMIN_EMAIL') ?: ''),
            (string) ($options['password'] ?? getenv('INSTALL_ADMIN_PASSWORD') ?: ''),
            $driver
        );
        echo "Installed with {$counts['products']} products, {$counts['categories']} categories, {$counts['pages']} pages and {$counts['settings']} settings.\n";
        echo "Admin: " . (string) ($options['email'] ?? getenv('INSTALL_ADMIN_EMAIL')) . PHP_EOL;
        echo "Delete install.php before going live.\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Install failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

$installed = already_installed();
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($post && !$installed) {
    try {
        $counts = run_install(
            trim((string) ($_POST['name'] ?? '')),
            trim((string) ($_POST['email'] ?? '')),
            (string) ($_POST['password'] ?? ''),
            $driver
        );
        $done = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$dbStatus = 'not checked';
try {
    Database::init((array) Config::get('database'));
    Database::pdo()->query('SELECT 1');
    $dbStatus = 'connected';
} catch (Throwable $e) {
    $dbStatus = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thunder Mounts — Installer</title>
<style>
  :root{--ink:#181818;--red:#C82026;--gray:#E5E7EB;--muted:#5B6168}
  *{box-sizing:border-box}
  body{margin:0;font-family:Inter,system-ui,-apple-system,'Segoe UI',sans-serif;background:#f3f4f6;color:var(--ink);line-height:1.55}
  .wrap{max-width:720px;margin:0 auto;padding:48px 20px}
  .card{background:#fff;border:1px solid var(--gray);border-top:4px solid var(--red);border-radius:10px;padding:32px;box-shadow:0 10px 40px rgba(0,0,0,.07)}
  h1{margin:0 0 6px;font-size:1.7rem;letter-spacing:-.02em}
  .muted{color:var(--muted);font-size:.9rem}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  label{display:block;font-weight:700;font-size:.8rem;margin-bottom:5px}
  input{width:100%;padding:11px 13px;border:1.5px solid #D1D5DB;border-radius:6px;font:inherit}
  input:focus{outline:0;border-color:var(--ink);box-shadow:0 0 0 3px rgba(24,24,24,.08)}
  .btn{display:inline-flex;align-items:center;justify-content:center;background:var(--red);color:#fff;border:0;border-radius:6px;padding:12px 22px;font:inherit;font-weight:700;cursor:pointer}
  .btn:hover{background:#A5181E}
  .notice{border-radius:6px;padding:12px 14px;font-size:.88rem;margin-bottom:16px;border:1px solid}
  .ok{background:#E6F4EC;border-color:#B9E0C7;color:#145A32}
  .bad{background:#FCF0F0;border-color:#F3B6B9;color:#7A1217}
  .warn{background:#FDF3E1;border-color:#F3DF8F;color:#5E4600}
  code{background:#f3f4f6;border-radius:4px;padding:1px 5px;font-size:.86em}
  table{width:100%;border-collapse:collapse;margin:16px 0;font-size:.88rem}
  td{padding:6px 0;border-bottom:1px solid #F3F4F6}
  td:last-child{text-align:right;font-weight:700}
  .steps{font-size:.88rem;color:var(--muted);margin-top:18px}
  @media(max-width:560px){.row{grid-template-columns:1fr}.card{padding:22px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>Thunder Mounts installer</h1>
    <p class="muted">Creates the database tables, imports the catalog and creates the first administrator.</p>

    <?php if ($done): ?>
      <div class="notice ok"><strong>Installation complete.</strong> Your store is ready.</div>
      <table>
        <tr><td>Categories</td><td><?= (int) $counts['categories'] ?></td></tr>
        <tr><td>Products</td><td><?= (int) $counts['products'] ?></td></tr>
        <tr><td>Product images</td><td><?= (int) $counts['images'] ?></td></tr>
        <tr><td>Pages</td><td><?= (int) $counts['pages'] ?></td></tr>
        <tr><td>Settings</td><td><?= (int) $counts['settings'] ?></td></tr>
      </table>
      <p class="warn notice"><strong>Now delete <code>install.php</code></strong> from the server, then sign in at <a href="/admin/login">/admin/login</a>.</p>
      <p class="steps">Next: Admin → Shipping to add your real shipping rates, Admin → Tax to add your tax rules, and Admin → Settings to add PayPal and SMTP credentials.</p>
    <?php elseif ($installed): ?>
      <div class="notice warn"><strong>Already installed.</strong> An administrator account exists, so the installer is locked. Sign in at <a href="/admin/login">/admin/login</a>. Delete <code>install.php</code> if it is still on the server.</div>
    <?php else: ?>
      <?php foreach ($errors as $error): ?><div class="notice bad"><?= h($error) ?></div><?php endforeach; ?>
      <div class="notice <?= $dbStatus === 'connected' ? 'ok' : 'bad' ?>">Database (<?= h($driver) ?>): <?= h($dbStatus) ?></div>

      <?php if ($dbStatus !== 'connected'): ?>
        <p class="muted">Edit <code>.env</code> with your database credentials first. On Hostinger, create a MySQL database in hPanel and copy the host, name, user and password here.</p>
      <?php else: ?>
        <form method="post" autocomplete="off">
          <div class="row">
            <div style="margin-bottom:14px"><label for="name">Your name</label><input id="name" name="name" required value="<?= h($_POST['name'] ?? '') ?>" placeholder="Gus Estravit"></div>
            <div style="margin-bottom:14px"><label for="email">Admin email</label><input id="email" type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@thundermounts.com"></div>
          </div>
          <div style="margin-bottom:18px"><label for="password">Password (10+ characters)</label><input id="password" type="password" name="password" required minlength="10"></div>
          <button class="btn" type="submit">Install store</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
