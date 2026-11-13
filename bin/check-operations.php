<?php

declare(strict_types=1);

/** Local/test-only checks on a new in-memory SQLite DB. Never sends external mail. */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\DateRange;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\Settings;
use App\Services\AdminDataService;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\SettingsController;

if (PHP_SAPI !== 'cli' || !in_array(Config::get('app.env'), ['local', 'test', 'testing'], true)) {
    fwrite(STDERR, "Run only with APP_ENV=local or test.\n"); exit(1);
}
Database::init(['driver' => 'sqlite', 'sqlite_path' => ':memory:']);
Settings::forget();
$sql = file_get_contents(TM_ROOT . '/database/schema.sqlite.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = preg_replace('/^SET .*;$/m', '', $sql);
Database::pdo()->exec($sql);
function verify(bool $value, string $label): void { if (!$value) throw new RuntimeException($label); echo 'PASS ', $label, PHP_EOL; }
function reject(callable $fn, string $label): void { try { $fn(); } catch (InvalidArgumentException) { verify(true, $label); return; } throw new RuntimeException($label); }
function row(string $table, array $data): int { return Database::insert($table, $data); }
function order(array $extra = []): int {
    return row('orders', array_merge(['order_number' => 'TEST-' . bin2hex(random_bytes(5)), 'email' => 'fixture@example.com', 'created_at' => '2024-05-10 00:00:00', 'updated_at' => now()], $extra));
}
$admin = row('users', ['email' => 'admin@example.com', 'password_hash' => password_hash('local-fixture-only', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
Session::set('user_id', $admin);
Settings::set('store.email', 'thunder@example.com');
Settings::set('mail.from_address', 'sender@example.com');

// Date boundaries: one inclusive day, Monday week start, impossible/future/oversize ranges.
$r = DateRange::resolve('custom', '2024-02-29', '2024-02-29');
verify($r['days'] === 1 && $r['end'] === '2024-03-01 00:00:00', 'Leap day and exclusive upper bound');
verify(DateRange::resolve('today')['from'] === date('Y-m-d'), 'Today filter');
verify(date('N', strtotime(DateRange::resolve('week')['from'])) === '1', 'Week starts Monday');
verify(DateRange::resolve('month')['from'] === date('Y-m-01'), 'Month starts on first day');
reject(static fn () => DateRange::resolve('custom', '2024-02-30', '2024-03-01'), 'Impossible date rejected');
reject(static fn () => DateRange::resolve('custom', '2024-05-11', '2024-05-10'), 'Reversed range rejected');
reject(static fn () => DateRange::resolve('custom', '2020-01-01', '2024-01-01'), 'Unbounded range rejected');
reject(static fn () => DateRange::resolve('custom', '9999-01-01', '9999-01-02'), 'Future dates rejected');

$service = new AdminDataService();
$eligible = order(['status' => 'cancelled']);
$paid = order(['status' => 'paid', 'payment_status' => 'paid', 'paid_at' => '2024-05-10 00:00:00', 'total' => '123.45']);
$nextDay = order(['status' => 'paid', 'payment_status' => 'paid', 'paid_at' => '2024-05-11 00:00:00', 'total' => '999.00', 'created_at' => '2024-05-11 00:00:00']);
$paypal = order(['status' => 'cancelled', 'paypal_order_id' => 'KEEP-PAYPAL']);
$audited = order(['status' => 'cancelled']);
$product = row('products', ['name' => 'Fixture mount', 'slug' => 'fixture-mount', 'sku' => 'FIXTURE', 'created_at' => now(), 'updated_at' => now()]);
row('inventory_movements', ['product_id' => $product, 'order_id' => $audited, 'delta' => -1, 'reason' => 'order', 'created_at' => now()]);
row('order_items', ['order_id' => $eligible, 'product_id' => $product, 'name' => 'Fixture item']);
row('order_notes', ['order_id' => $eligible, 'note' => 'Fixture note', 'created_at' => now()]);
row('order_status_history', ['order_id' => $eligible, 'to_status' => 'cancelled', 'created_at' => now()]);
row('order_items', ['order_id' => $paid, 'product_id' => $product, 'name' => 'Fixture paid item', 'qty' => 2, 'line_total' => '100.00']);
$preview = $service->preview('orders', '2024-06-01');
verify(array_column($preview['rows'], 'id') === [$eligible], 'Only cancelled, never-paid, unaudited orders eligible');
reject(static fn () => $service->delete('orders', '2024-06-01', [$eligible, $paid], $admin), 'Mixed protected batch rejected atomically');
verify((int) Database::value('SELECT COUNT(*) FROM orders WHERE id = ?', [$eligible]) === 1, 'Eligible record retained after mixed batch rollback');
reject(static fn () => $service->delete('orders', '2024-06-01', [$paypal], $admin), 'PayPal idempotency references protected');
reject(static fn () => $service->delete('orders', '2024-06-01', [$audited], $admin), 'Inventory-audited orders protected');
verify($service->delete('orders', '2024-06-01', [$eligible], $admin) === 1, 'Eligible order deletion succeeds');
foreach (['order_items', 'order_notes', 'order_status_history'] as $table) verify((int) Database::value("SELECT COUNT(*) FROM {$table} WHERE order_id = ?", [$eligible]) === 0, 'Related records removed: ' . $table);
verify((int) Database::value("SELECT COUNT(*) FROM admin_activity WHERE action = 'record.delete' AND entity_id = ?", [(string) $eligible]) === 1, 'Per-record deletion audit retained');
reject(static fn () => $service->preview('paypal_webhook_events', '2024-06-01'), 'Arbitrary tables cannot be cleaned');
reject(static fn () => $service->delete('messages', '2024-06-01', [], $admin), 'Empty selection rejected');
reject(static fn () => $service->delete('messages', '2024-06-01', array_fill(0, 51, 1), $admin), 'Oversized batch rejected');

// Email state, missing bodies and queued records cannot be retried/deleted unsafely.
$failed = row('email_log', ['to_email' => 'recipient@example.com', 'subject' => 'Fixture', 'body' => '<p>Fixture</p>', 'status' => 'failed', 'created_at' => '2024-01-01 00:00:00']);
$queued = row('email_log', ['to_email' => 'recipient@example.com', 'subject' => 'Queued', 'body' => '<p>Fixture</p>', 'status' => 'queued', 'created_at' => '2024-01-01 00:00:00']);
$empty = row('email_log', ['to_email' => 'recipient@example.com', 'subject' => 'Pruned', 'body' => null, 'status' => 'failed', 'created_at' => '2024-01-01 00:00:00']);
verify(!Mailer::resend($queued)['ok'], 'Queued email cannot be resent');
verify(!Mailer::resend($empty)['ok'], 'Missing body cannot be resent');
reject(static fn () => $service->delete('emails', '2024-06-01', [$queued], $admin), 'Queued email cannot be deleted');
Settings::set('mail.transport', 'smtp'); Settings::set('encrypted.mail_host', ''); Config::set('mail.host', '');
verify(!Mailer::resend($failed)['ok'], 'Invalid SMTP configuration fails before any network request');
verify(Database::value('SELECT status FROM email_log WHERE id = ?', [$failed]) === 'failed', 'Failed retry returns record to failed state');
verify((int) Database::value('SELECT COUNT(*) FROM email_log') === 3, 'Retry updates original log without duplicate records');
verify($service->delete('emails', '2024-06-01', [$failed], $admin) === 1, 'Failed email can be deleted after confirmation');

// Security retention and inactive-account policy.
$recent = row('login_attempts', ['email' => 'fixture@example.com', 'ip' => '127.0.0.1', 'created_at' => now()]);
reject(static fn () => $service->delete('login_logs', date('Y-m-d', strtotime('tomorrow')), [$recent], $admin), 'Recent security logs protected');
reject(static fn () => $service->delete('customers', date('Y-m-d', strtotime('tomorrow')), [$admin], $admin), 'Admin account protected');
foreach ($service->types() as $type => $definition) { $service->preview($type, date('Y-m-d', strtotime('tomorrow'))); }
verify(true, 'All cleanup allowlists execute on SQLite schema');

// Configuration checks and CRLF normalization without connecting to any SMTP server.
$config = ['transport' => 'smtp', 'from_address' => 'sender@example.com', 'reply_to' => 'team@example.com', 'host' => 'smtp.example.com', 'port' => '465', 'encryption' => 'tls', 'username' => 'sender@example.com', 'password' => 'fixture'];
verify(Mailer::validateConfiguration($config) !== [], 'Reject 465 + STARTTLS mismatch');
$config['encryption'] = 'ssl'; verify(Mailer::validateConfiguration($config) === [], 'Accept 465 + SSL configuration');
$config['port'] = '587'; verify(Mailer::validateConfiguration($config) !== [], 'Reject 587 + implicit SSL mismatch');
$config['encryption'] = 'tls'; verify(Mailer::validateConfiguration($config) === [], 'Accept 587 + STARTTLS configuration');
$config['host'] = 'https://smtp.example.com'; verify(Mailer::validateConfiguration($config) !== [], 'SMTP host must not contain a URL');
$dotStuff = new ReflectionMethod(Mailer::class, 'dotStuff');
verify($dotStuff->invoke(null, "one\r\n.two\nthree\rfour") === "one\r\n..two\r\nthree\r\nfour", 'Normalize line endings and SMTP dot-stuffing');
$settings = (new ReflectionClass(SettingsController::class))->newInstanceWithoutConstructor();
$apply = new ReflectionMethod(SettingsController::class, 'applyGroup');
$input = ['mail.transport' => 'smtp', 'mail.from_address' => 'sender@example.com', 'mail.port' => '465', 'mail.encryption' => 'tls', 'mail.timeout' => '15', 'encrypted.mail_host' => 'smtp.example.com'];
verify($apply->invoke($settings, 'mail', $input) !== [], 'Invalid mail settings rejected before persistence');
verify(Settings::get('mail.port', 'unset') === 'unset', 'Invalid mail settings do not partially persist');
$input['mail.encryption'] = 'ssl';
verify($apply->invoke($settings, 'mail', $input) === [], 'Valid SMTP settings can be saved');
$input['mail.transport'] = 'resend'; $input['encrypted.mail_resend_key'] = 'test-only-api-secret';
verify($apply->invoke($settings, 'mail', $input) === [], 'HTTPS transport settings accepted');
verify(str_starts_with(Database::value('SELECT value FROM settings WHERE `key` = ?', ['encrypted.mail_resend_key']), 'v1:'), 'HTTPS key encrypted at rest');

// Revenue uses paid_at, orders use created_at, next-day midnight is excluded.
$_GET = ['period' => 'custom', 'from' => '2024-05-10', 'to' => '2024-05-10'];
$html = (new DashboardController())->index();
verify(str_contains($html, '$123.45') && !str_contains($html, '$999.00'), 'Dashboard financial range boundaries');
verify(str_contains($html, 'Fixture paid item'), 'Top sellers use selected payment period');
$_GET = ['period' => 'custom', 'from' => '2024-02-30', 'to' => '2024-05-10'];
verify(str_contains((new DashboardController())->index(), 'Showing this month instead'), 'Invalid dashboard filter has a visible fallback');
$template = Mailer::template('contact/admin-notification', ['message_data' => ['subject' => '<unsafe>', 'name' => 'Fixture', 'email' => 'fixture@example.com', 'phone' => '', 'message' => '<script>bad</script>'], 'contact_id' => 1]);
verify(!str_contains($template, '<script>bad</script>') && str_contains($template, '&lt;unsafe&gt;'), 'Contact notification template exists and escapes input');
echo "Operations checks complete on an isolated in-memory database. No external delivery attempted.\n";
