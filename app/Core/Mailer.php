<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * SMTP, explicit PHP mail(), and optional HTTPS email transport.
 * Every attempt is written to email_log so the merchant can audit delivery.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $html, ?string $replyTo = null, ?int $existingLogId = null): bool
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

        $logId = $existingLogId ?? self::log($to, $subject, $html, 'queued');
        $ok = false;
        $error = null;

        try {
            $problems = self::validateConfiguration($config);
            if ($problems !== []) throw new \RuntimeException(implode(' ', $problems));
            if ($replyTo !== null && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Invalid reply-to address.');
            if ($config['transport'] === 'resend') {
                $ok = self::httpsMail($config, $to, $subject, $html, $replyTo, $logId, $error);
            } elseif ($config['transport'] === 'smtp') {
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

    public static function resend(int $id): array
    {
        $row = Database::first('SELECT * FROM email_log WHERE id = ?', [$id]);
        if (!$row || $row['status'] !== 'failed' || trim((string) $row['body']) === '' || !filter_var($row['to_email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Only failed emails with a retained body and valid recipient can be retried.'];
        }
        // Claim atomically: a second click/worker cannot send the same attempt concurrently.
        if (Database::execute("UPDATE email_log SET status = 'queued', error = 'Retry in progress' WHERE id = ? AND status = 'failed'", [$id]) !== 1) {
            return ['ok' => false, 'message' => 'This email is already being retried.'];
        }
        $ok = self::send($row['to_email'], $row['subject'], $row['body'], null, $id);
        return ['ok' => $ok, 'message' => $ok ? 'The mail provider accepted the retry. This does not confirm inbox delivery.' : 'Retry failed. Review the updated error in the email log.'];
    }

    public static function validateConfiguration(array $config): array
    {
        $errors = [];
        if (!filter_var($config['from_address'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Configure a valid From address.';
        if (!filter_var($config['reply_to'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Configure a valid store reply-to email address.';
        if (($config['transport'] ?? '') === 'smtp') {
            if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $config['host'] ?? '') || str_contains($config['host'], '..')) $errors[] = 'Enter an SMTP hostname only (no https://, ssl://, path or port).';
            $port = (int) ($config['port'] ?? 0);
            if ($port < 1 || $port > 65535) $errors[] = 'SMTP port must be between 1 and 65535.';
            if (!in_array($config['encryption'] ?? '', ['ssl', 'tls', 'none'], true)) $errors[] = 'Choose a valid SMTP encryption mode.';
            if ($port === 465 && $config['encryption'] !== 'ssl') $errors[] = 'Port 465 requires implicit SSL/TLS. Select SSL (465).';
            if ($port === 587 && $config['encryption'] === 'ssl') $errors[] = 'Port 587 uses STARTTLS. Select TLS (587), not SSL.';
            if (($config['username'] ?? '') !== '' && ($config['password'] ?? '') === '') $errors[] = 'An SMTP username is configured but the password is missing.';
            if (($config['username'] ?? '') !== '' && $config['encryption'] === 'none') $errors[] = 'Use TLS or SSL before sending SMTP credentials.';
        }
        if (($config['transport'] ?? '') === 'resend' && trim($config['api_key'] ?? '') === '') $errors[] = 'Enter a Resend API key for HTTPS email delivery.';
        return $errors;
    }

    private static function connectionError(string $host, int $port, string $encryption, int $errno, string $detail): string
    {
        return 'SMTP connection failed to ' . $host . ':' . $port . ' (' . $encryption . '): ' . $detail . ' (' . $errno . '). '
            . 'The connection failed before authentication. Confirm the provider hostname, use 465 + SSL or 587 + TLS, and ask your host to allow outbound SMTP. If SMTP ports are blocked, use HTTPS email delivery on port 443.';
    }

    public static function diagnose(): array
    {
        $config = self::config();
        $errors = self::validateConfiguration($config);
        if ($errors !== []) return ['ok' => false, 'message' => implode(' ', $errors)];
        if ($config['transport'] !== 'smtp') return ['ok' => true, 'message' => 'Configuration is valid. Send a test email to verify provider acceptance; this check does not deliver an email.'];
        $remote = ($config['encryption'] === 'ssl' ? 'ssl://' : '') . $config['host'] . ':' . $config['port'];
        $socket = @stream_socket_client($remote, $errno, $detail, $config['timeout'], STREAM_CLIENT_CONNECT);
        if (!$socket) return ['ok' => false, 'message' => self::connectionError($config['host'], (int) $config['port'], $config['encryption'], $errno, $detail)];
        stream_set_timeout($socket, $config['timeout']);
        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO localhost', [250]);
            if ($config['encryption'] === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('TLS negotiation failed. Check certificates and SMTP port/encryption.');
            }
            return ['ok' => true, 'message' => 'SMTP connection, greeting and encryption checks passed. No login or email was attempted. Send a test email to verify authentication and sender authorization.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } finally { fclose($socket); }
    }

    private static function httpsMail(array $config, string $to, string $subject, string $html, ?string $replyTo, int $logId, ?string &$error): bool
    {
        if (!function_exists('curl_init')) throw new \RuntimeException('PHP cURL is required for HTTPS mail.');
        $curl = curl_init('https://api.resend.com/emails');
        $response = '';
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $config['api_key'], 'Idempotency-Key: tm-mail-' . hash('sha256', (string) Config::get('app.url') . ':' . $logId . ':' . $to . ':' . $subject . ':' . $html)],
            CURLOPT_POSTFIELDS => json_encode(['from' => $config['from_name'] . ' <' . $config['from_address'] . '>', 'to' => [$to], 'subject' => $subject, 'html' => $html, 'reply_to' => $replyTo ?: $config['reply_to']]),
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => $config['timeout'], CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$response): int { if (strlen($response) + strlen($chunk) > 65536) return 0; $response .= $chunk; return strlen($chunk); },
        ]);
        $ok = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $data = json_decode($response, true);
        if ($ok === false || $status < 200 || $status >= 300 || empty($data['id'])) {
            $error = 'HTTPS email provider did not confirm acceptance (HTTP ' . $status . '). Check the API key, verified sender domain and hosting access to api.resend.com:443. If the request timed out, verify provider logs before resending.';
            return false;
        }
        return true;
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
            'transport' => in_array($transport, ['smtp', 'resend'], true) ? $transport : 'mail',
            'timeout' => max(5, min(60, (int) setting('mail.timeout', '15'))),
            'api_key' => (string) setting('encrypted.mail_resend_key', ''),
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
        $socket = @stream_socket_client($remote, $errno, $errstr, $config['timeout'], STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]));
        if (!$socket) {
            $error = self::connectionError($host, $port, $encryption, $errno, $errstr);
            return false;
        }
        stream_set_timeout($socket, $config['timeout']);

        try {
            self::expect($socket, [220]);
            $ehloHost = parse_url((string) Config::get('app.url', ''), PHP_URL_HOST) ?: 'localhost';
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
            self::writeAll($socket, $data . "\r\n");
            self::expect($socket, [250]);
            // DATA was accepted. A QUIT timeout must not trigger duplicate delivery on retry.
            @fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            @fclose($socket);
            return false;
        }
    }

    private static function writeAll($socket, string $data): void
    {
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) throw new \RuntimeException('SMTP write failed or timed out. Check provider logs before retrying.');
            $offset += $written;
        }
    }

    private static function command($socket, string $command, array $expected): string
    {
        self::writeAll($socket, $command . "\r\n");
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
        if ($response === '') {
            $meta = stream_get_meta_data($socket);
            throw new \RuntimeException(!empty($meta['timed_out']) ? 'SMTP response timed out. Check the port/encryption pair and hosting firewall.' : 'SMTP server closed the connection without a response.');
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
        return $response;
    }

    private static function dotStuff(string $body): string
    {
        return preg_replace('/^\./m', '..', preg_replace('/\r\n|\r|\n/', "\r\n", $body) ?? $body) ?? $body;
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
