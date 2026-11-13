<?php

declare(strict_types=1);

/**
 * Thunder Mounts — single front controller.
 * Point the domain document root at this directory (public/).
 */

if (PHP_SAPI === 'cli-server') {
    $staticPath = __DIR__ . (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($staticPath) && basename($staticPath) !== 'index.php') {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';
