<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Str;
use App\Core\View;

final class MarketingController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function discounts(): string
    {
        $editId = Request::int('edit', 0);
        return View::render('admin/discounts', [
            'pageTitle' => 'Discounts',
            'discounts' => Database::select(
                'SELECT d.*, (SELECT COUNT(*) FROM discount_redemptions r WHERE r.discount_id = d.id) AS redemptions
                 FROM discounts d ORDER BY d.is_active DESC, d.created_at DESC'
            ),
            'editDiscount' => $editId > 0 ? Database::first('SELECT * FROM discounts WHERE id = ?', [$editId]) : null,
        ], 'layouts/admin');
    }

    public function saveDiscount(?string $id = null): never
    {
        Csrf::require();
        $discountId = $id !== null ? (int) $id : 0;
        $input = Request::all();
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $errors = [];
        if ($code === '' || !preg_match('/^[A-Z0-9_-]{2,40}$/', $code)) {
            $errors['code'] = 'Use 2–40 letters, numbers, hyphens or underscores.';
        }
        if ((int) Database::value('SELECT COUNT(*) FROM discounts WHERE UPPER(code) = ? AND id <> ?', [$code, $discountId]) > 0) {
            $errors['code'] = 'That code already exists.';
        }
        $type = in_array($input['type'] ?? '', ['percent', 'fixed'], true) ? $input['type'] : 'percent';
        $value = (float) ($input['value'] ?? 0);
        if ($value <= 0) {
            $errors['value'] = 'Enter a discount greater than zero.';
        }
        if ($type === 'percent' && $value > 100) {
            $errors['value'] = 'A percentage discount cannot exceed 100%.';
        }
        foreach (['min_subtotal', 'usage_limit', 'per_user_limit'] as $numeric) {
            if (($input[$numeric] ?? '') !== '' && (!is_numeric($input[$numeric]) || (float) $input[$numeric] < 0)) {
                $errors[$numeric] = 'Enter a valid number or leave blank.';
            }
        }
        $starts = $this->dateTimeValue((string) ($input['starts_at'] ?? ''));
        $ends = $this->dateTimeValue((string) ($input['ends_at'] ?? ''));
        if ($starts && $ends && $ends < $starts) {
            $errors['ends_at'] = 'The end date must be after the start date.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/discounts' . ($discountId > 0 ? '?edit=' . $discountId : '')));
        }
        $fields = [
            'code' => $code,
            'description' => trim((string) ($input['description'] ?? '')),
            'type' => $type,
            'value' => number_format($value, 2, '.', ''),
            'min_subtotal' => number_format((float) ($input['min_subtotal'] ?? 0), 2, '.', ''),
            'starts_at' => $starts,
            'ends_at' => $ends,
            'usage_limit' => ($input['usage_limit'] ?? '') !== '' ? (int) $input['usage_limit'] : null,
            'per_user_limit' => ($input['per_user_limit'] ?? '') !== '' ? (int) $input['per_user_limit'] : null,
            'dealer_only' => !empty($input['dealer_only']) ? 1 : 0,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($discountId > 0) {
            Database::update('discounts', $fields, 'id = ?', [$discountId]);
            flash('success', 'Discount updated.');
        } else {
            $fields['created_at'] = now();
            Database::insert('discounts', $fields);
            flash('success', 'Discount created.');
        }
        clear_input();
        Response::redirect(url('/admin/discounts'));
    }

    public function deleteDiscount(string $id): never
    {
        Csrf::require();
        $used = (int) Database::value('SELECT COUNT(*) FROM discount_redemptions WHERE discount_id = ?', [(int) $id]);
        if ($used > 0) {
            Database::execute('UPDATE discounts SET is_active = 0, updated_at = ? WHERE id = ?', [now(), (int) $id]);
            flash('success', 'This code has redemptions, so it was deactivated instead of deleted.');
        } else {
            Database::execute('DELETE FROM discounts WHERE id = ?', [(int) $id]);
            flash('success', 'Discount deleted.');
        }
        Response::redirect(url('/admin/discounts'));
    }

    public function reviews(): string
    {
        $status = trim((string) Request::query('status', 'pending'));
        $where = $status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true) ? 'WHERE r.status = ?' : '';
        $params = $where !== '' ? [$status] : [];
        $reviews = Database::select(
            "SELECT r.*, p.name AS product_name, p.slug AS product_slug FROM reviews r
             INNER JOIN products p ON p.id = r.product_id {$where}
             ORDER BY r.created_at DESC LIMIT 200",
            $params
        );
        return View::render('admin/reviews', [
            'pageTitle' => 'Reviews',
            'reviews' => $reviews,
            'status' => $status,
            'counts' => [
                'pending' => (int) Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'pending'"),
                'approved' => (int) Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'approved'"),
                'rejected' => (int) Database::value("SELECT COUNT(*) FROM reviews WHERE status = 'rejected'"),
            ],
        ], 'layouts/admin');
    }

    public function reviewStatus(string $id): never
    {
        Csrf::require();
        $status = (string) Request::input('status', '');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            Database::execute('UPDATE reviews SET status = ? WHERE id = ?', [$status, (int) $id]);
            flash('success', 'Review marked ' . $status . '.');
        }
        Response::redirect(url('/admin/reviews?status=' . urlencode((string) Request::query('from', 'pending'))));
    }

    public function deleteReview(string $id): never
    {
        Csrf::require();
        Database::execute('DELETE FROM reviews WHERE id = ?', [(int) $id]);
        flash('success', 'Review deleted.');
        Response::redirect(url('/admin/reviews'));
    }

    public function messages(): string
    {
        $id = Request::int('id', 0);
        $selected = $id > 0 ? Database::first('SELECT * FROM contact_messages WHERE id = ?', [$id]) : null;
        if ($selected && $selected['status'] === 'new') {
            Database::execute("UPDATE contact_messages SET status = 'read' WHERE id = ?", [$selected['id']]);
            $selected['status'] = 'read';
        }
        return View::render('admin/messages', [
            'pageTitle' => 'Messages',
            'messages' => Database::select('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200'),
            'selected' => $selected,
        ], 'layouts/admin');
    }

    public function messageStatus(string $id): never
    {
        Csrf::require();
        $status = (string) Request::input('status', 'read');
        if (in_array($status, ['new', 'read', 'replied', 'closed'], true)) {
            Database::execute('UPDATE contact_messages SET status = ? WHERE id = ?', [$status, (int) $id]);
            flash('success', 'Message marked ' . $status . '.');
        }
        Response::redirect(url('/admin/messages?id=' . (int) $id));
    }

    public function pages(): string
    {
        $editId = Request::int('edit', 0);
        return View::render('admin/pages', [
            'pageTitle' => 'Pages',
            'pages' => Database::select('SELECT * FROM pages ORDER BY sort_order, title'),
            'editPage' => $editId > 0 ? Database::first('SELECT * FROM pages WHERE id = ?', [$editId]) : null,
        ], 'layouts/admin');
    }

    public function savePage(?string $id = null): never
    {
        Csrf::require();
        $pageId = $id !== null ? (int) $id : 0;
        $input = Request::all();
        $errors = [];
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'] = 'Page title is required.';
        }
        $slug = trim((string) ($input['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($title);
        if ((int) Database::value('SELECT COUNT(*) FROM pages WHERE slug = ? AND id <> ?', [$slug, $pageId]) > 0) {
            $errors['slug'] = 'That slug is already used by another page.';
        }
        if (trim((string) ($input['content'] ?? '')) === '') {
            $errors['content'] = 'Add the page content.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/pages' . ($pageId > 0 ? '?edit=' . $pageId : '')));
        }
        $fields = [
            'title' => $title,
            'slug' => $slug,
            'content' => (string) $input['content'],
            'meta_title' => trim((string) ($input['meta_title'] ?? '')),
            'meta_description' => mb_substr(trim((string) ($input['meta_description'] ?? '')), 0, 300),
            'show_in_footer' => !empty($input['show_in_footer']) ? 1 : 0,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'updated_at' => now(),
        ];
        if ($pageId > 0) {
            Database::update('pages', $fields, 'id = ?', [$pageId]);
            flash('success', 'Page updated.');
        } else {
            $fields['created_at'] = now();
            Database::insert('pages', $fields);
            flash('success', 'Page created.');
        }
        clear_input();
        Response::redirect(url('/admin/pages'));
    }

    public function deletePage(string $id): never
    {
        Csrf::require();
        $page = Database::first('SELECT * FROM pages WHERE id = ?', [(int) $id]);
        if ($page) {
            $protected = ['terms', 'privacy', 'returns', 'shipping', 'installation', 'about', 'faq', 'contact'];
            if (in_array($page['slug'], $protected, true)) {
                Database::execute('UPDATE pages SET is_active = 0, updated_at = ? WHERE id = ?', [now(), $page['id']]);
                flash('success', 'This is a required store page, so it was hidden rather than deleted.');
            } else {
                Database::execute('DELETE FROM pages WHERE id = ?', [$page['id']]);
                flash('success', 'Page deleted.');
            }
        }
        Response::redirect(url('/admin/pages'));
    }

    public function subscribers(): string
    {
        return View::render('admin/subscribers', [
            'pageTitle' => 'Newsletter',
            'subscribers' => Database::select('SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 500'),
            'total' => (int) Database::value("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'subscribed'"),
        ], 'layouts/admin');
    }

    public function exportSubscribers(): never
    {
        $rows = Database::select('SELECT email, status, created_at FROM newsletter_subscribers ORDER BY created_at DESC');
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Email', 'Status', 'Subscribed at']);
        foreach ($rows as $row) {
            fputcsv($handle, [$row['email'], $row['status'], $row['created_at']]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="newsletter-' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }

    private function dateTimeValue(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
