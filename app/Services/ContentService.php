<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Settings;
use App\Core\Validator;
use Throwable;

final class ContentService
{
    public function page(string $slug): ?array
    {
        return Database::first('SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1', [$slug]);
    }

    public function pages(bool $footerOnly = false): array
    {
        $sql = 'SELECT * FROM pages WHERE is_active = 1';
        if ($footerOnly) {
            $sql .= ' AND show_in_footer = 1';
        }
        return Database::select($sql . ' ORDER BY sort_order, title');
    }

    public function contact(array $data): array
    {
        $validator = (new Validator($data, [
            'name' => 'Name',
            'email' => 'Email address',
            'phone' => 'Phone number',
            'subject' => 'Subject',
            'message' => 'Message',
        ]))->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|phone|max:30',
            'subject' => 'required|max:190',
            'message' => 'required|min:10|max:5000',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }
        $id = Database::insert('contact_messages', [
            'name' => trim((string) $data['name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'subject' => trim((string) $data['subject']),
            'message' => trim((string) $data['message']),
            'status' => 'new',
            'created_at' => now(),
        ]);
        try {
            $admin = (string) Settings::get('store.notification_email', 'thunder@thundermounts.com');
            Mailer::send(
                $admin,
                'Website enquiry: ' . mb_substr((string) $data['subject'], 0, 120),
                Mailer::template('contact/admin-notification', [
                    'message_data' => $data,
                    'contact_id' => $id,
                ]),
                (string) $data['email']
            );
            Mailer::send(
                (string) $data['email'],
                'We received your message — Thunder Mounts',
                Mailer::template('contact/received', ['name' => $data['name'], 'subject' => $data['subject']])
            );
        } catch (Throwable $e) {
            Logger::error('Contact email failed', ['error' => $e->getMessage()]);
        }
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public function subscribe(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Enter a valid email address.'];
        }
        $email = mb_strtolower(trim($email));
        $existing = Database::first('SELECT * FROM newsletter_subscribers WHERE email = ?', [$email]);
        if ($existing) {
            if ($existing['status'] === 'subscribed') {
                return ['ok' => true, 'message' => 'Your subscription is already active. You will continue to receive Thunder Mounts product news and installer resources.'];
            }
            Database::execute('UPDATE newsletter_subscribers SET status = ? WHERE id = ?', ['subscribed', $existing['id']]);
        } else {
            Database::insert('newsletter_subscribers', [
                'email' => $email,
                'status' => 'subscribed',
                'created_at' => now(),
            ]);
        }
        return ['ok' => true, 'message' => 'Your subscription is confirmed. Watch your inbox for Thunder Mounts product news and installer resources.'];
    }

    public function joinWaitlist(int $productId, string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Enter a valid email address.'];
        }
        $product = Database::first('SELECT * FROM products WHERE id = ? AND is_active = 1', [$productId]);
        if (!$product) {
            return ['ok' => false, 'message' => 'Product not found.'];
        }
        $email = mb_strtolower(trim($email));
        $exists = Database::value('SELECT COUNT(*) FROM product_waitlist WHERE product_id = ? AND email = ?', [$productId, $email]);
        if ((int) $exists === 0) {
            Database::insert('product_waitlist', [
                'product_id' => $productId,
                'email' => $email,
                'created_at' => now(),
            ]);
        }
        return ['ok' => true, 'message' => 'We will email you when ' . $product['name'] . ' is available.'];
    }

    public function submitReview(array $user, array $data): array
    {
        $validator = (new Validator($data, [
            'rating' => 'Rating',
            'title' => 'Review title',
            'body' => 'Review',
        ]))->validate([
            'rating' => 'required|integer|min_value:1|max_value:5',
            'title' => 'required|max:190',
            'body' => 'required|min:10|max:3000',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }
        $product = Database::first('SELECT id, name FROM products WHERE id = ? AND is_active = 1', [(int) $data['product_id']]);
        if (!$product) {
            return ['ok' => false, 'errors' => ['product' => 'Product not found.']];
        }
        $verified = (int) Database::value(
            "SELECT COUNT(*) FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status IN ('paid','partially_refunded')",
            [$user['id'], $product['id']]
        ) > 0;
        $status = Settings::bool('reviews.auto_approve', false) ? 'approved' : 'pending';
        $id = Database::insert('reviews', [
            'product_id' => $product['id'],
            'user_id' => (int) $user['id'],
            'name' => trim(($user['first_name'] ?? '') . ' ' . mb_substr((string) ($user['last_name'] ?? ''), 0, 1) . '.'),
            'rating' => (int) $data['rating'],
            'title' => trim((string) $data['title']),
            'body' => trim((string) $data['body']),
            'status' => $status,
            'verified_purchase' => $verified ? 1 : 0,
            'created_at' => now(),
        ]);
        return [
            'ok' => true,
            'errors' => [],
            'id' => $id,
            'message' => $status === 'approved'
                ? 'Thank you — your review is published.'
                : 'Thank you — your review is awaiting moderation.',
        ];
    }
}
