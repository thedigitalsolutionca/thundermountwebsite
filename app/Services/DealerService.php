<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Settings;
use App\Core\Validator;
use Throwable;

final class DealerService
{
    public function forUser(int $userId): ?array
    {
        $dealer = Database::first('SELECT * FROM dealers WHERE user_id = ? ORDER BY id DESC LIMIT 1', [$userId]);
        if ($dealer && $dealer['tier_id']) {
            $dealer['tier'] = Database::first('SELECT * FROM dealer_tiers WHERE id = ?', [$dealer['tier_id']]);
        }
        return $dealer;
    }

    public function find(int $id): ?array
    {
        $dealer = Database::first('SELECT * FROM dealers WHERE id = ?', [$id]);
        if (!$dealer) {
            return null;
        }
        $dealer['user'] = $dealer['user_id'] ? Database::first('SELECT id, email, first_name, last_name, status, last_login_at FROM users WHERE id = ?', [$dealer['user_id']]) : null;
        $dealer['tier'] = $dealer['tier_id'] ? Database::first('SELECT * FROM dealer_tiers WHERE id = ?', [$dealer['tier_id']]) : null;
        if ($dealer['user_id']) {
            $dealer['orders'] = Database::select(
                'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
                [$dealer['user_id']]
            );
        } else {
            $dealer['orders'] = [];
        }
        return $dealer;
    }

    public function apply(int $userId, array $data, ?string $documentPath = null): array
    {
        $validator = (new Validator($data, [
            'company' => 'Business name',
            'contact_name' => 'Contact name',
            'email' => 'Business email',
            'phone' => 'Phone number',
            'business_type' => 'Business type',
            'address1' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'postcode' => 'ZIP / postal code',
        ]))->validate([
            'company' => 'required|max:160',
            'contact_name' => 'required|max:160',
            'email' => 'required|email|max:190',
            'phone' => 'required|phone|max:30',
            'business_type' => 'required|max:60',
            'website' => 'url|max:255',
            'resale_number' => 'max:80',
            'address1' => 'required|max:190',
            'city' => 'required|max:100',
            'state' => 'required|max:60',
            'postcode' => 'required|postcode',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors(), 'dealer' => null];
        }
        $user = Database::first('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return ['ok' => false, 'errors' => ['general' => 'Account not found.'], 'dealer' => null];
        }
        $existing = $this->forUser($userId);
        if ($existing) {
            return ['ok' => false, 'errors' => ['general' => 'A dealer application is already on file for your account.'], 'dealer' => $existing];
        }
        $email = mb_strtolower(trim((string) $data['email']));
        if ((int) Database::value('SELECT COUNT(*) FROM dealers WHERE email = ?', [$email]) > 0) {
            return ['ok' => false, 'errors' => ['email' => 'A dealer application already exists for this email.'], 'dealer' => null];
        }

        $dealerId = Database::transaction(function () use ($userId, $data, $email, $documentPath, $user): int {
            $id = Database::insert('dealers', [
                'user_id' => $userId,
                'company' => trim((string) $data['company']),
                'contact_name' => trim((string) $data['contact_name']),
                'email' => $email,
                'phone' => trim((string) $data['phone']),
                'business_type' => trim((string) $data['business_type']),
                'website' => trim((string) ($data['website'] ?? '')),
                'resale_number' => trim((string) ($data['resale_number'] ?? '')),
                'document_path' => $documentPath ?? '',
                'address1' => trim((string) $data['address1']),
                'address2' => trim((string) ($data['address2'] ?? '')),
                'city' => trim((string) $data['city']),
                'state' => strtoupper(trim((string) $data['state'])),
                'postcode' => strtoupper(trim((string) $data['postcode'])),
                'country' => strtoupper(trim((string) ($data['country'] ?? 'US'))),
                'status' => 'pending',
                'applied_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Database::execute('UPDATE users SET role = ?, dealer_id = ?, company = ?, phone = ?, updated_at = ? WHERE id = ?', [
                'dealer',
                $id,
                trim((string) $data['company']),
                trim((string) $data['phone']),
                now(),
                $userId,
            ]);
            return $id;
        });

        $dealer = $this->find($dealerId);
        try {
            Mailer::send(
                $email,
                'We received your Thunder Mounts dealer application',
                Mailer::template('dealers/received', ['dealer' => $dealer, 'user' => $user])
            );
            $adminEmail = (string) Settings::get('store.notification_email', 'thunder@thundermounts.com');
            Mailer::send(
                $adminEmail,
                'New dealer application — ' . $dealer['company'],
                Mailer::template('dealers/admin-notification', ['dealer' => $dealer])
            );
        } catch (Throwable $e) {
            Logger::error('Dealer application email failed', ['error' => $e->getMessage()]);
        }

        return ['ok' => true, 'errors' => [], 'dealer' => $dealer];
    }

    public function approve(int $dealerId, ?int $tierId, ?int $adminId, string $note = ''): array
    {
        $dealer = $this->find($dealerId);
        if (!$dealer) {
            return ['ok' => false, 'message' => 'Dealer not found.'];
        }
        if ($dealer['status'] === 'approved') {
            return ['ok' => false, 'message' => 'This dealer is already approved.'];
        }
        if ($tierId !== null && !Database::first('SELECT id FROM dealer_tiers WHERE id = ?', [$tierId])) {
            return ['ok' => false, 'message' => 'Choose a valid dealer tier.'];
        }
        Database::transaction(function () use ($dealer, $tierId, $adminId, $note): void {
            Database::execute('UPDATE dealers SET status = ?, tier_id = ?, reviewed_at = ?, reviewed_by = ?, admin_notes = ?, updated_at = ? WHERE id = ?', [
                'approved',
                $tierId,
                now(),
                $adminId,
                $note,
                now(),
                $dealer['id'],
            ]);
            if ($dealer['user_id']) {
                Database::execute('UPDATE users SET role = ?, dealer_id = ?, status = ?, updated_at = ? WHERE id = ?', [
                    'dealer',
                    $dealer['id'],
                    'active',
                    now(),
                    $dealer['user_id'],
                ]);
            }
        });

        $fresh = $this->find($dealerId);
        try {
            Mailer::send(
                (string) $dealer['email'],
                'Your Thunder Mounts dealer account is approved',
                Mailer::template('dealers/approved', ['dealer' => $fresh])
            );
        } catch (Throwable $e) {
            Logger::error('Dealer approval email failed', ['error' => $e->getMessage()]);
        }
        return ['ok' => true, 'message' => $dealer['company'] . ' is now an approved dealer.'];
    }

    public function assignTier(int $dealerId, ?int $tierId, ?int $adminId, string $note = ''): array
    {
        $dealer = $this->find($dealerId);
        if (!$dealer) {
            return ['ok' => false, 'message' => 'Dealer not found.'];
        }
        if ($dealer['status'] !== 'approved') {
            return $this->approve($dealerId, $tierId, $adminId, $note);
        }
        if ($tierId !== null && !Database::first('SELECT id FROM dealer_tiers WHERE id = ? AND is_active = 1', [$tierId])) {
            return ['ok' => false, 'message' => 'Choose a valid active dealer tier.'];
        }
        Database::execute('UPDATE dealers SET tier_id = ?, reviewed_at = ?, reviewed_by = ?, admin_notes = ?, updated_at = ? WHERE id = ?', [
            $tierId,
            now(),
            $adminId,
            $note !== '' ? $note : $dealer['admin_notes'],
            now(),
            $dealerId,
        ]);
        return ['ok' => true, 'message' => 'Dealer pricing tier updated.'];
    }

    public function reject(int $dealerId, ?int $adminId, string $note = ''): array
    {
        $dealer = $this->find($dealerId);
        if (!$dealer) {
            return ['ok' => false, 'message' => 'Dealer not found.'];
        }
        Database::execute('UPDATE dealers SET status = ?, reviewed_at = ?, reviewed_by = ?, admin_notes = ?, tier_id = NULL, updated_at = ? WHERE id = ?', [
            'rejected',
            now(),
            $adminId,
            $note,
            now(),
            $dealer['id'],
        ]);
        try {
            Mailer::send(
                (string) $dealer['email'],
                'Update on your Thunder Mounts dealer application',
                Mailer::template('dealers/rejected', ['dealer' => $dealer, 'note' => $note])
            );
        } catch (Throwable $e) {
            Logger::error('Dealer rejection email failed', ['error' => $e->getMessage()]);
        }
        return ['ok' => true, 'message' => 'Application marked as not approved.'];
    }

    public function list(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'd.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . trim((string) $filters['q']) . '%';
            $where[] = '(d.company LIKE ? OR d.email LIKE ? OR d.contact_name LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        return Database::select(
            'SELECT d.*, t.name AS tier_name, t.discount_percent
             FROM dealers d LEFT JOIN dealer_tiers t ON t.id = d.tier_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY CASE d.status WHEN \'pending\' THEN 0 ELSE 1 END, d.created_at DESC',
            $params
        );
    }

    public function counts(): array
    {
        $rows = Database::select('SELECT status, COUNT(*) AS total FROM dealers GROUP BY status');
        $out = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['total'];
        }
        return $out;
    }

    public function tiers(bool $activeOnly = false): array
    {
        $sql = 'SELECT t.*, (SELECT COUNT(*) FROM dealers d WHERE d.tier_id = t.id AND d.status = \'approved\') AS dealer_count
                FROM dealer_tiers t' . ($activeOnly ? ' WHERE t.is_active = 1' : '') . ' ORDER BY t.name';
        return Database::select($sql);
    }

    public function saveTier(array $data, ?int $id = null): array
    {
        $validator = (new Validator($data, ['name' => 'Tier name']))->validate([
            'name' => 'required|max:80',
            'discount_percent' => 'required|numeric|min_value:0|max_value:100',
            'free_shipping_min' => 'numeric|min_value:0',
            'min_order_total' => 'numeric|min_value:0',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }
        $fields = [
            'name' => trim((string) $data['name']),
            'discount_percent' => number_format((float) $data['discount_percent'], 2, '.', ''),
            'free_shipping_min' => ($data['free_shipping_min'] ?? '') !== '' ? number_format((float) $data['free_shipping_min'], 2, '.', '') : null,
            'min_order_total' => number_format((float) ($data['min_order_total'] ?? 0), 2, '.', ''),
            'notes' => trim((string) ($data['notes'] ?? '')),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($id !== null) {
            Database::update('dealer_tiers', $fields, 'id = ?', [$id]);
            return ['ok' => true, 'errors' => [], 'id' => $id];
        }
        $fields['created_at'] = now();
        return ['ok' => true, 'errors' => [], 'id' => Database::insert('dealer_tiers', $fields)];
    }

    public function deleteTier(int $id): array
    {
        $count = (int) Database::value('SELECT COUNT(*) FROM dealers WHERE tier_id = ?', [$id]);
        if ($count > 0) {
            return ['ok' => false, 'message' => 'This tier is assigned to ' . $count . ' dealer(s). Reassign them first.'];
        }
        Database::execute('DELETE FROM dealer_tiers WHERE id = ?', [$id]);
        return ['ok' => true, 'message' => 'Dealer tier deleted.'];
    }
}
