<?php

declare(strict_types=1);

/**
 * Retry recently failed transactional email.
 *
 * Usage: php bin/retry-mail.php [limit]
 * Cron:  every 15 minutes: php /path/to/bin/retry-mail.php >> storage/logs/cron.log 2>&1
 *
 * Only rows from the last 7 days are retried, and only once: a failed retry
 * rewrites the error with a "Retry attempt" prefix so the next run skips it.
 * The existing record is claimed atomically and updated by Mailer::resend().
 */

define('TM_CLI', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Mailer;

if (!defined('TM_CLI') && PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$limit = max(1, min(100, (int) ($argv[1] ?? 20)));
$cutoff = date('Y-m-d H:i:s', time() - 7 * 86400);

try {
    $rows = Database::select(
        "SELECT id, to_email, subject, body FROM email_log
         WHERE status = 'failed'
           AND created_at >= ?
           AND (error IS NULL OR error NOT LIKE '%retry attempt%')
         ORDER BY id ASC
         LIMIT {$limit}",
        [$cutoff]
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Unable to read email_log: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$sent = 0;
$failed = 0;

foreach ($rows as $row) {
    if (trim((string) $row['body']) === '') {
        Database::update('email_log', ['error' => 'Retry attempt skipped: no message body retained.'], 'id = ?', [$row['id']]);
        continue;
    }
    $result = Mailer::resend((int) $row['id']);
    if ($result['ok']) {
        $sent++;
        echo 'sent   #' . $row['id'] . PHP_EOL;
    } else {
        // Only mark a failure owned by this attempt; never change another worker's queued row.
        Database::execute("UPDATE email_log SET error = ? WHERE id = ? AND status = 'failed'", ['Retry attempt at ' . now() . ' failed. Review mail settings before a manual retry.', $row['id']]);
        $failed++;
        echo 'failed #' . $row['id'] . PHP_EOL;
    }
}

echo "retry-mail: {$sent} sent, {$failed} failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
