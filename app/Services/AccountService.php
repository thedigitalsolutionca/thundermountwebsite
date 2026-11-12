<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use Throwable;

final class AccountService
{
    public function register(array $data, bool $autoLogin = false): array
    {
        $validator = (new Validator($data, [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'email' => 'Email address',
            'phone' => 'Phone number',
            'password' => 'Password',
        ]))->validate([
            'first_name' => 'required|max:80',
            'last_name' => 'required|max:80',
            'email' => 'required|email|max:190',
            'phone' => 'required|phone|max:30',
            'company' => 'max:120',
            'password' => 'required|min:8|max:200',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors(), 'user' => null];
        }
        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? $data['password'] ?? '')) {
            return ['ok' => false, 'errors' => ['password_confirmation' => 'Passwords do not match.'], 'user' => null];
        }
        $email = mb_strtolower(trim((string) $data['email']));
        if ((int) Database::value('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0) {
            return ['ok' => false, 'errors' => ['email' => 'An account with this email already exists. Try signing in.'], 'user' => null];
        }

        $userId = Database::insert('users', [
            'email' => $email,
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'company' => trim((string) ($data['company'] ?? '')),
            'role' => 'customer',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = Database::first('SELECT * FROM users WHERE id = ?', [$userId]);

        if ($autoLogin) {
            CartService::mergeForUser($userId);
            Auth::login($user);
        }
        $this->sendWelcome($user);
        return ['ok' => true, 'errors' => [], 'user' => $user];
    }

    public function authenticate(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $recentFailures = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND created_at >= ? AND (email = ? OR ip = ?)',
            [date('Y-m-d H:i:s', time() - 900), $email, $ip]
        );
        if ($recentFailures >= 8) {
            return ['ok' => false, 'message' => 'Too many unsuccessful sign-in attempts. Please wait 15 minutes and try again.', 'user' => null];
        }

        $user = Database::first('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $valid = $user !== null && password_verify($password, (string) $user['password_hash']);

        Database::insert('login_attempts', [
            'email' => $email,
            'ip' => $ip,
            'success' => $valid ? 1 : 0,
            'created_at' => now(),
        ]);

        if (!$valid) {
            return ['ok' => false, 'message' => 'The email or password you entered is incorrect.', 'user' => null];
        }
        if ($user['status'] !== 'active') {
            return ['ok' => false, 'message' => 'This account is not active. Please contact us for help.', 'user' => null];
        }
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [
                password_hash($password, PASSWORD_DEFAULT),
                $user['id'],
            ]);
        }

        CartService::mergeForUser((int) $user['id']);
        Auth::login($user);
        return ['ok' => true, 'message' => 'Welcome back.', 'user' => $user];
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function updateProfile(int $userId, array $data): array
    {
        $validator = (new Validator($data, [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'email' => 'Email address',
            'phone' => 'Phone number',
        ]))->validate([
            'first_name' => 'required|max:80',
            'last_name' => 'required|max:80',
            'email' => 'required|email|max:190',
            'phone' => 'required|phone|max:30',
            'company' => 'max:120',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }
        $email = mb_strtolower(trim((string) $data['email']));
        $taken = (int) Database::value('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $userId]);
        if ($taken > 0) {
            return ['ok' => false, 'errors' => ['email' => 'Another account is already using this email.']];
        }
        Database::execute(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, company = ?, updated_at = ? WHERE id = ?',
            [
                trim((string) $data['first_name']),
                trim((string) $data['last_name']),
                $email,
                trim((string) ($data['phone'] ?? '')),
                trim((string) ($data['company'] ?? '')),
                now(),
                $userId,
            ]
        );
        return ['ok' => true, 'errors' => []];
    }

    public function changePassword(int $userId, string $current, string $new, string $confirm): array
    {
        $user = Database::first('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user || !password_verify($current, (string) $user['password_hash'])) {
            return ['ok' => false, 'errors' => ['current_password' => 'Your current password is not correct.']];
        }
        if (mb_strlen($new) < 8) {
            return ['ok' => false, 'errors' => ['password' => 'Use at least 8 characters for your new password.']];
        }
        if ($new !== $confirm) {
            return ['ok' => false, 'errors' => ['password_confirmation' => 'The new passwords do not match.']];
        }
        Database::execute('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?', [
            password_hash($new, PASSWORD_DEFAULT),
            now(),
            $userId,
        ]);
        return ['ok' => true, 'errors' => []];
    }

    public function requestPasswordReset(string $email): array
    {
        $email = mb_strtolower(trim($email));
        $user = Database::first('SELECT * FROM users WHERE email = ? AND status = ?', [$email, 'active']);
        // Always return the same response so the form cannot be used to enumerate accounts.
        if (!$user) {
            return ['ok' => true, 'message' => 'If an account exists for that email, a reset link is on its way.'];
        }
        $token = Str::random(32);
        Database::execute('UPDATE users SET reset_token_hash = ?, reset_expires_at = ?, updated_at = ? WHERE id = ?', [
            hash('sha256', $token),
            date('Y-m-d H:i:s', time() + 3600),
            now(),
            $user['id'],
        ]);
        $link = absolute_url('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
        try {
            Mailer::send(
                $email,
                'Reset your Thunder Mounts password',
                Mailer::template('auth/reset', [
                    'user' => $user,
                    'link' => $link,
                    'expires' => '1 hour',
                ])
            );
        } catch (Throwable $e) {
            Logger::error('Password reset email failed', ['error' => $e->getMessage()]);
        }
        return ['ok' => true, 'message' => 'If an account exists for that email, a reset link is on its way.'];
    }

    public function resetPassword(string $token, string $email, string $password, string $confirm): array
    {
        $email = mb_strtolower(trim($email));
        if (mb_strlen($password) < 8) {
            return ['ok' => false, 'errors' => ['password' => 'Use at least 8 characters.']];
        }
        if ($password !== $confirm) {
            return ['ok' => false, 'errors' => ['password_confirmation' => 'The passwords do not match.']];
        }
        $user = Database::first('SELECT * FROM users WHERE email = ? AND reset_token_hash = ? LIMIT 1', [
            $email,
            hash('sha256', trim($token)),
        ]);
        if (!$user || empty($user['reset_expires_at']) || strtotime($user['reset_expires_at']) < time()) {
            return ['ok' => false, 'errors' => ['token' => 'This reset link is invalid or has expired. Request a new one.']];
        }
        Database::execute(
            'UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_expires_at = NULL, updated_at = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), now(), $user['id']]
        );
        Database::execute('DELETE FROM login_attempts WHERE email = ?', [$email]);
        return ['ok' => true, 'errors' => []];
    }

    public function addresses(int $userId): array
    {
        return Database::select('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC, id DESC', [$userId]);
    }

    public function address(int $userId, int $addressId): ?array
    {
        return Database::first('SELECT * FROM addresses WHERE id = ? AND user_id = ?', [$addressId, $userId]);
    }

    public function saveAddress(int $userId, array $data, ?int $addressId = null): array
    {
        $validator = (new Validator($data, [
            'label' => 'Label',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'address1' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'postcode' => 'Postal code',
            'phone' => 'Phone number',
        ]))->validate([
            'label' => 'required|max:60',
            'first_name' => 'required|max:80',
            'last_name' => 'required|max:80',
            'address1' => 'required|max:190',
            'city' => 'required|max:100',
            'state' => 'required|max:60',
            'postcode' => 'required|postcode',
            'country' => 'required|max:2',
            'phone' => 'required|phone|max:30',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()];
        }
        if ($addressId === null) {
            $count = (int) Database::value('SELECT COUNT(*) FROM addresses WHERE user_id = ?', [$userId]);
            if ($count >= 15) {
                return ['ok' => false, 'errors' => ['label' => 'You have reached the maximum number of saved addresses.']];
            }
        }
        $fields = [
            'label' => trim((string) $data['label']),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'company' => trim((string) ($data['company'] ?? '')),
            'address1' => trim((string) $data['address1']),
            'address2' => trim((string) ($data['address2'] ?? '')),
            'city' => trim((string) $data['city']),
            'state' => strtoupper(trim((string) $data['state'])),
            'postcode' => strtoupper(trim((string) $data['postcode'])),
            'country' => strtoupper(trim((string) ($data['country'] ?? 'US'))),
            'phone' => trim((string) ($data['phone'] ?? '')),
        ];

        if ($addressId !== null) {
            $owned = $this->address($userId, $addressId);
            if (!$owned) {
                return ['ok' => false, 'errors' => ['label' => 'Address not found.']];
            }
            Database::update('addresses', $fields, 'id = ? AND user_id = ?', [$addressId, $userId]);
            $id = $addressId;
        } else {
            $fields['user_id'] = $userId;
            $fields['created_at'] = now();
            $id = Database::insert('addresses', $fields);
        }
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public function deleteAddress(int $userId, int $addressId): bool
    {
        return Database::execute('DELETE FROM addresses WHERE id = ? AND user_id = ?', [$addressId, $userId]) > 0;
    }

    public function setDefaultAddress(int $userId, int $addressId, string $type = 'shipping'): bool
    {
        $address = $this->address($userId, $addressId);
        if (!$address) {
            return false;
        }
        $column = $type === 'billing' ? 'is_default_billing' : 'is_default_shipping';
        Database::execute("UPDATE addresses SET {$column} = 0 WHERE user_id = ?", [$userId]);
        Database::execute("UPDATE addresses SET {$column} = 1 WHERE id = ? AND user_id = ?", [$addressId, $userId]);
        return true;
    }

    public function sendWelcome(array $user): void
    {
        try {
            Mailer::send(
                (string) $user['email'],
                'Welcome to Thunder Mounts',
                Mailer::template('auth/welcome', ['user' => $user])
            );
        } catch (Throwable $e) {
            Logger::error('Welcome email failed', ['error' => $e->getMessage()]);
        }
    }
}
