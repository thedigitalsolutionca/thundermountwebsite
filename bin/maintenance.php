<?php

declare(strict_types=1);

/**
 * Housekeeping for shared hosting (no daemons required).
 *
 * Usage: php bin/maintenance.php
 * Cron:  15 3 * * * php /path/to/bin/maintenance.php >> storage/logs/cron.log 2>&1
 *
 * Removes abandoned carts, old login attempts, processed webhook payloads,
 * old email bodies, expired session files and stale cache files. Order and
 * customer records are never touched.
 */

define('TM_CLI', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

if (!defined('TM_CLI') && PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$now = time();
$report = [];

function prune(string $label, string $sql, array $params, array &$report): void
{
    try {
        $report[$label] = Database::execute($sql, $params);
    } catch (Throwable $e) {
        $report[$label] = 'skipped: ' . $e->getMessage();
    }
}

// Abandoned carts (guest and signed-in) untouched for 30 days.
prune(
    'carts deleted',
    'DELETE FROM carts WHERE updated_at < ?',
    [date('Y-m-d H:i:s', $now - 30 * 86400)],
    $report
);
prune('cart_items deleted', 'DELETE FROM cart_items WHERE cart_id NOT IN (SELECT id FROM carts)', [], $report);

// Login throttling data is only useful for a day.
prune('login_attempts deleted', 'DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', $now - 86400)], $report);

// Keep webhook payloads for 90 days, then drop the raw body but keep the audit row.
prune(
    'webhook payloads cleared',
    "UPDATE paypal_webhook_events SET payload = NULL WHERE payload IS NOT NULL AND created_at < ?",
    [date('Y-m-d H:i:s', $now - 90 * 86400)],
    $report
);
prune(
    'webhook rows deleted',
    "DELETE FROM paypal_webhook_events WHERE status IN ('processed','failed') AND created_at < ?",
    [date('Y-m-d H:i:s', $now - 365 * 86400)],
    $report
);

// Old email bodies contain customer data; keep a year of audit metadata only.
prune(
    'email bodies cleared',
    "UPDATE email_log SET body = NULL WHERE body IS NOT NULL AND created_at < ?",
    [date('Y-m-d H:i:s', $now - 365 * 86400)],
    $report
);

// Failed PHP session files past the configured lifetime.
$sessionDir = TM_ROOT . '/storage/sessions';
$sessionLifetime = max(1800, (int) Config::get('session.lifetime', 120) * 60);
$sessions = 0;
foreach (glob($sessionDir . '/sess_*') ?: [] as $file) {
    if (is_file($file) && filemtime($file) < $now - $sessionLifetime - 300) {
        @unlink($file);
        $sessions++;
    }
}
$report['session files deleted'] = $sessions;

// Cache files older than a day (rendered fragments, rate limits, etc.).
$cacheFiles = 0;
foreach (glob(TM_ROOT . '/storage/cache/*') ?: [] as $file) {
    if (is_file($file) && filemtime($file) < $now - 86400) {
        @unlink($file);
        $cacheFiles++;
    }
}
$report['cache files deleted'] = $cacheFiles;

foreach ($report as $label => $value) {
    echo str_pad($label, 26) . $value . PHP_EOL;
}
