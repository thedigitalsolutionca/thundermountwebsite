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
 * Each attempt is also recorded as its own email_log row by Mailer::send().
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
    $before = (int) Database::value('SELECT COALESCE(MAX(id), 0) FROM email_log');
    $ok = Mailer::send((string) $row['to_email'], (string) $row['subject'], (string) ($row['body'] ?? ''));
    if ($ok) {
        Database::update('email_log', [
            'status' => 'sent',
            'error' => null,
            'sent_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $row['id']]);
        $sent++;
        echo 'sent   #' . $row['id'] . ' ' . $row['to_email'] . PHP_EOL;
    } else {
        Database::update('email_log', [
            'error' => 'Retry attempt at ' . date('Y-m-d H:i:s') . ' failed; see the newer log row for details.',
        ], 'id = ?', [(int) $row['id']]);
        // Do not let the copy Mailer::send() just wrote become a retry candidate.
        Database::execute(
            "UPDATE email_log SET error = ? WHERE id > ? AND status = 'failed' AND (error IS NULL OR error NOT LIKE '%retry attempt%')",
            ['Created by a retry attempt; not retried again automatically.', $before]
        );
        $failed++;
        echo 'failed #' . $row['id'] . ' ' . $row['to_email'] . PHP_EOL;
    }
}

echo "retry-mail: {$sent} sent, {$failed} failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
