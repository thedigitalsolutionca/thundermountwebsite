<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\DateRange;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AdminDataService;
use InvalidArgumentException;
use Throwable;

final class OperationsController
{
    public function __construct() { Auth::requireAdmin(); }

    public function data(): string
    {
        $service = new AdminDataService();
        $type = (string) Request::query('type', 'messages');
        $before = (string) Request::query('before', date('Y-m-d', strtotime('-30 days')));
        $error = '';
        try { $preview = $service->preview($type, $before, Request::int('page', 1), Request::int('record')); }
        catch (InvalidArgumentException $e) { $error = $e->getMessage(); $type = 'messages'; $before = date('Y-m-d', strtotime('-30 days')); $preview = $service->preview($type, $before); }
        return View::render('admin/data', ['pageTitle' => 'Data management', 'types' => $service->types(), 'type' => $type, 'before' => $before, 'preview' => $preview, 'error' => $error], 'layouts/admin');
    }

    public function deleteData(): never
    {
        Csrf::require();
        $type = (string) Request::post('type', '');
        $before = (string) Request::post('before', '');
        $redirect = url('/admin/data?' . http_build_query(['type' => $type, 'before' => $before]));
        $attempts = array_values(array_filter($_SESSION['_cleanup_failures'] ?? [], static fn ($time) => $time > time() - 900));
        if (count($attempts) >= 5) { flash('error', 'Too many failed confirmations. Try again in 15 minutes.'); Response::redirect($redirect); }
        $user = Database::first("SELECT password_hash FROM users WHERE id = ? AND role = 'admin' AND status = 'active'", [Auth::id()]);
        $password = Request::post('password', '');
        if (!is_string($password) || !$user || !password_verify($password, $user['password_hash'])) {
            $attempts[] = time(); $_SESSION['_cleanup_failures'] = $attempts;
            flash('error', 'Your current administrator password is required. Nothing was deleted.'); Response::redirect($redirect);
        }
        if (Request::post('confirmation') !== 'DELETE') { flash('error', 'Type DELETE to confirm permanent removal. Nothing was deleted.'); Response::redirect($redirect); }
        try {
            $ids = Request::post('ids', []);
            if (!is_array($ids)) throw new InvalidArgumentException('Choose records from the preview.');
            $count = (new AdminDataService())->delete($type, $before, $ids, Auth::id());
            unset($_SESSION['_cleanup_failures']);
            flash('success', $count . ' selected records permanently removed. An audit entry has been retained.');
        } catch (InvalidArgumentException $e) { flash('error', $e->getMessage()); }
        catch (Throwable) { flash('error', 'The cleanup could not be completed. No changes were committed. Please refresh and try again.'); }
        Response::redirect($redirect);
    }

    public function emails(): string
    {
        $status = (string) Request::query('status', 'failed');
        if (!in_array($status, ['failed', 'sent', 'queued', 'all'], true)) $status = 'failed';
        $q = mb_substr((string) Request::query('q', ''), 0, 190);
        $where = ['1 = 1']; $params = []; $error = '';
        if ($status !== 'all') { $where[] = 'status = ?'; $params[] = $status; }
        if ($q !== '') { $where[] = '(to_email LIKE ? OR subject LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
        $from = (string) Request::query('from', ''); $to = (string) Request::query('to', '');
        try {
            if ($from !== '') { DateRange::date($from); $where[] = 'created_at >= ?'; $params[] = $from . ' 00:00:00'; }
            if ($to !== '') { $end = DateRange::date($to); $where[] = 'created_at < ?'; $params[] = $end->modify('+1 day')->format('Y-m-d 00:00:00'); }
            if ($from !== '' && $to !== '' && $from > $to) throw new InvalidArgumentException('Start date must not follow the end date.');
        } catch (InvalidArgumentException $e) { $error = $e->getMessage(); $where[] = '1 = 0'; }
        $sql = implode(' AND ', $where);
        $count = (int) Database::value('SELECT COUNT(*) FROM email_log WHERE ' . $sql, $params);
        $page = max(1, min(Request::int('page', 1), max(1, (int) ceil($count / 25)))); $offset = ($page - 1) * 25;
        $rows = Database::select('SELECT id, to_email, subject, status, error, created_at, sent_at FROM email_log WHERE ' . $sql . ' ORDER BY id DESC LIMIT 25 OFFSET ' . $offset, $params);
        $selected = Request::int('id') > 0 ? Database::first('SELECT * FROM email_log WHERE id = ?', [Request::int('id')]) : null;
        return View::render('admin/emails', ['pageTitle' => 'Email delivery', 'rows' => $rows, 'count' => $count, 'page' => $page, 'pages' => max(1, (int) ceil($count / 25)), 'selected' => $selected, 'status' => $status, 'q' => $q, 'from' => $from, 'to' => $to, 'error' => $error], 'layouts/admin');
    }

    public function resendEmail(string $id): never
    {
        Csrf::require();
        $recent = array_values(array_filter($_SESSION['_email_retries'] ?? [], static fn ($time) => $time > time() - 60));
        if (count($recent) >= 5) { flash('error', 'Please wait a minute before retrying more emails.'); Response::redirect(url('/admin/emails?status=failed')); }
        $recent[] = time(); $_SESSION['_email_retries'] = $recent;
        if (Request::post('confirm_resend') !== 'yes') { flash('error', 'Confirm that you checked provider logs for possible prior acceptance.'); Response::redirect(url('/admin/emails?status=failed&id=' . (int) $id)); }
        try {
            $result = Mailer::resend((int) $id);
            AdminDataService::audit(Auth::id(), 'email.retry', 'email_log', (string) (int) $id, $result['ok'] ? 'Provider accepted retry' : 'Retry did not succeed');
            flash($result['ok'] ? 'success' : 'error', $result['message']);
        } catch (Throwable) { flash('error', 'The retry could not be completed. Check the email log and provider before trying again.'); }
        Response::redirect(url('/admin/emails?status=all&id=' . (int) $id));
    }
}
