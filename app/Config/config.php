<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'Thunder Mounts'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
        'url' => rtrim((string) Env::get('APP_URL', ''), '/'),
        'base_path' => rtrim((string) Env::get('APP_BASE_PATH', ''), '/'),
        'timezone' => Env::get('APP_TIMEZONE', 'America/Los_Angeles'),
        'key' => (string) Env::get('APP_KEY', ''),
    ],
    'database' => [
        'driver' => Env::get('DB_DRIVER', 'mysql'),
        'host' => Env::get('DB_HOST', 'localhost'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', ''),
        'username' => Env::get('DB_USERNAME', ''),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
        'sqlite_path' => Env::get('DB_SQLITE_PATH', dirname(__DIR__, 2) . '/storage/database.sqlite'),
    ],
    'session' => [
        'name' => Env::get('SESSION_NAME', 'tm_session'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', '120'),
        'secure' => filter_var(Env::get('COOKIE_SECURE', 'true'), FILTER_VALIDATE_BOOL),
    ],
    'mail' => [
        'transport' => Env::get('MAIL_TRANSPORT', 'mail'),
        'host' => Env::get('MAIL_HOST', ''),
        'port' => (int) Env::get('MAIL_PORT', '587'),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'username' => Env::get('MAIL_USERNAME', ''),
        'password' => Env::get('MAIL_PASSWORD', ''),
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@thundermounts.com'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'Thunder Mounts'),
        'reply_to' => Env::get('MAIL_REPLY_TO', 'thunder@thundermounts.com'),
    ],
    'paypal' => [
        'mode' => Env::get('PAYPAL_MODE', 'sandbox'),
        'client_id' => Env::get('PAYPAL_CLIENT_ID', ''),
        'client_secret' => Env::get('PAYPAL_CLIENT_SECRET', ''),
        'webhook_id' => Env::get('PAYPAL_WEBHOOK_ID', ''),
    ],
    'uploads' => [
        'path' => dirname(__DIR__, 2) . '/public/uploads',
        'url' => '/uploads',
        'max_bytes' => 5 * 1024 * 1024,
    ],
    'storage' => [
        'logs' => dirname(__DIR__, 2) . '/storage/logs',
        'cache' => dirname(__DIR__, 2) . '/storage/cache',
    ],
];
