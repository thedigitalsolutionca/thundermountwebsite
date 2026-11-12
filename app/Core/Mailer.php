<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Dependency-free SMTP client with PHP mail() fallback.
 * Every attempt is written to email_log so the merchant can audit delivery.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $html, ?string $replyTo = null): bool
    {
        $config = self::config();
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Logger::error('Mail rejected: invalid recipient', ['to' => $to]);
            return false;
        }

        $from = $config['from_address'];
        $fromName = $config['from_name'];
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . self::encodeName($fromName) . ' <' . $from . '>',
            'Reply-To: ' . ($replyTo ?: $config['reply_to']),
            'X-Mailer: ThunderMounts/1.0',
        ];

        $logId = self::log($to, $subject, $html, 'queued');
        $ok = false;
        $error = null;

        try {
            if ($config['transport'] === 'smtp' && $config['host'] !== '') {
                $ok = self::smtp($config, $to, $subject, $html, $headers, $error);
            } else {
                $ok = @mail($to, self::encodeHeader($subject), $html, implode("\r\n", $headers));
                if (!$ok) {
                    $error = 'PHP mail() returned false.';
                }
            }
        } catch (Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
            Logger::error('Mail exception', ['message' => $e->getMessage()]);
        }

        if ($logId > 0) {
            Database::update('email_log', [
                'status' => $ok ? 'sent' : 'failed',
                'error' => $error,
                'sent_at' => $ok ? now() : null,
            ], 'id = ?', [$logId]);
        }
        return $ok;
    }

    public static function template(string $view, array $data): string
    {
        $site = [
            'name' => setting('store.name', 'Thunder Mounts'),
            'url' => url('/'),
            'phone' => setting('store.phone', '805-338-6680'),
            'email' => setting('store.email', 'thunder@thundermounts.com'),
            'address' => setting('store.address', '4212 E Los Angeles Ave, Simi Valley, CA 93063'),
        ];
        return View::render('emails/' . $view, array_merge($site, $data), 'emails/layout');
    }

    private static function config(): array
    {
        $transport = (string) (setting('mail.transport', '') ?: Config::get('mail.transport', 'mail'));
        return [
            'transport' => $transport === 'smtp' ? 'smtp' : 'mail',
            'host' => (string) (setting('encrypted.mail_host', '') ?: Config::get('mail.host', '')),
            'port' => (string) (setting('mail.port', '') ?: Config::get('mail.port', '587')),
            'encryption' => (string) (setting('mail.encryption', '') ?: Config::get('mail.encryption', 'tls')),
            'username' => (string) (setting('encrypted.mail_username', '') ?: Config::get('mail.username', '')),
            'password' => (string) (setting('encrypted.mail_password', '') ?: Config::get('mail.password', '')),
            'from_address' => (string) (setting('mail.from_address', '') ?: Config::get('mail.from_address', 'no-reply@thundermounts.com')),
            'from_name' => (string) setting('store.name', (string) Config::get('app.name', 'Thunder Mounts')),
            'reply_to' => (string) (setting('store.email', '') ?: Config::get('mail.reply_to', 'thunder@thundermounts.com')),
        ];
    }

    private static function smtp(array $config, string $to, string $subject, string $html, array $headers, ?string &$error): bool
    {
        $host = $config['host'];
        $port = (int) $config['port'];
        $encryption = strtolower($config['encryption']);
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            $error = 'SMTP connection failed: ' . $errstr . ' (' . $errno . ')';
            return false;
        }
        stream_set_timeout($socket, 15);

        try {
            self::expect($socket, [220]);
            $ehloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
            self::command($socket, 'EHLO ' . $ehloHost, [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Unable to start TLS with the SMTP server.');
                }
                self::command($socket, 'EHLO ' . $ehloHost, [250]);
            }

            if ($config['username'] !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($config['username']), [334]);
                self::command($socket, base64_encode($config['password']), [235]);
            }

            self::command($socket, 'MAIL FROM:<' . $config['from_address'] . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);
            $data = implode("\r\n", array_merge($headers, [
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($subject),
                'Date: ' . date('r'),
            ])) . "\r\n\r\n" . self::dotStuff($html) . "\r\n.";
            fwrite($socket, $data . "\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            @fclose($socket);
            return false;
        }
    }

    private static function command($socket, string $command, array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expected);
    }

    private static function expect($socket, array $codes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
        return $response;
    }

    private static function dotStuff(string $body): string
    {
        return preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body)) ?? $body;
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private static function encodeName(string $name): string
    {
        return self::encodeHeader($name);
    }

    private static function log(string $to, string $subject, string $body, string $status): int
    {
        try {
            return Database::insert('email_log', [
                'to_email' => $to,
                'subject' => mb_substr($subject, 0, 255),
                'body' => $body,
                'status' => $status,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            return 0;
        }
    }
}
