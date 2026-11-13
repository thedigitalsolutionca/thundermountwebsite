<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

define('TM_ROOT', dirname(__DIR__));
define('TM_APP', __DIR__);

// PSR-4 style autoloader (App\ → app/).
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = TM_APP . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require TM_APP . '/helpers.php';

Env::load(TM_ROOT . '/.env');
$config = require TM_APP . '/Config/config.php';

// Allow env overrides for session security and PayPal credentials from admin later.
$config['session']['name'] = Env::get('SESSION_NAME', $config['session']['name']);
$config['app']['name'] = Env::get('APP_NAME', $config['app']['name']);
Config::load($config);

date_default_timezone_set((string) Config::get('app.timezone', 'America/Los_Angeles'));

// Ensure writable runtime directories exist.
foreach (['storage/logs', 'storage/cache', 'public/uploads/products', 'public/uploads/dealers', 'public/uploads/gallery'] as $dir) {
    $absolute = TM_ROOT . '/' . $dir;
    if (!is_dir($absolute)) {
        @mkdir($absolute, 0775, true);
    }
}

if ((bool) Config::get('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', TM_ROOT . '/storage/logs/php-' . date('Y-m-d') . '.log');
}

set_exception_handler(static function (Throwable $e): void {
    Router::handleException($e);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        Logger::error('Fatal error', $error);
        if (!headers_sent()) {
            http_response_code(500);
            echo '<!doctype html><meta charset="utf-8"><title>Service temporarily unavailable</title>'
                . '<p style="font-family:system-ui;padding:40px">We are updating the store and will be back shortly. '
                . 'Please call <a href="tel:8053386680">805-338-6680</a> if you need help right away.</p>';
        }
    }
});

Database::init((array) Config::get('database'));

// Database-backed settings require the schema; during installation we show guidance instead of a stack trace.
try {
    $installed = Database::tableExists('settings');
} catch (Throwable) {
    $installed = false;
}

if (!$installed) {
    $isCli = PHP_SAPI === 'cli';
    if ($isCli) {
        fwrite(STDERR, "Database is not installed. Run: php install.php\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Thunder Mounts — Setup required</title><style>body{font-family:Inter,system-ui,sans-serif;background:#f5f5f5;color:#181818;display:grid;place-items:center;min-height:100vh;margin:0}'
        . '.card{background:#fff;border:1px solid #e5e7eb;border-top:4px solid #C82026;border-radius:8px;max-width:620px;padding:40px;box-shadow:0 10px 40px rgba(0,0,0,.08)}'
        . 'code{background:#f3f4f6;padding:2px 6px;border-radius:4px}h1{margin-top:0}a{color:#C82026}</style></head><body>'
        . '<div class="card"><h1>Setup required</h1><p>The store database has not been installed yet.</p>'
        . '<p>Upload the project, create a MySQL database in Hostinger, enter the credentials in <code>.env</code>, then open '
        . '<code>/install.php</code> in your browser (or run <code>php install.php</code> from SSH).</p>'
        . '<p>Full instructions are in <code>DEPLOYMENT.md</code>.</p></div></body></html>';
    exit;
}

// CLI callers (bin/*.php, cron) only need env, config, helpers and the database.
// Session cookies, HTTP headers and route dispatch are web-only.
if (PHP_SAPI === 'cli' || (defined('TM_CLI') && TM_CLI)) {
    return;
}

Session::start();
\App\Core\Response::securityHeaders();

$currentUser = \App\Core\Auth::user();

// Shared view data.
$footerPages = [];
try {
    $footerPages = Database::select(
        'SELECT title, slug FROM pages WHERE is_active = 1 AND show_in_footer = 1 ORDER BY sort_order, title LIMIT 6'
    );
    if (!\App\Services\DealerService::arePagesEnabled()) {
        $footerPages = array_values(array_filter(
            $footerPages,
            static fn (array $p): bool => ($p['slug'] ?? '') !== 'dealer-program'
        ));
    }
} catch (Throwable) {
}

View::share('appName', (string) Config::get('app.name', 'Thunder Mounts'));
View::share('storePhone', setting('store.phone', '805-338-6680'));
View::share('storeEmail', setting('store.email', 'thunder@thundermounts.com'));
View::share('storeAddress', setting('store.address', '4212 E Los Angeles Ave, Simi Valley, CA 93063'));
View::share('storeHours', setting('store.hours', 'Mon–Fri 9:00am–4:00pm · Sat 10:00am–2:00pm'));
View::share('footerPages', $footerPages);
View::share('currentUser', $currentUser);
View::share('flashMessages', \App\Core\Session::pullFlash());
View::share('pageTitle', null);
View::share('metaDescription', setting('seo.description', 'Engineered mounting systems for garage doors and openers. Value. Symmetry. Safety.'));
View::share('canonical', absolute_url(Request::path()));

// Load routes.
$router = new Router();
(require TM_ROOT . '/routes/web.php')($router);
(require TM_ROOT . '/routes/admin.php')($router);

$router->dispatch();
