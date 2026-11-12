<?php

declare(strict_types=1);

/**
 * Optional web installer for hosts without SSH (PaaS/Docker).
 *
 * Disabled by default. Set ALLOW_INSTALL=1 in the environment, open
 * /install.php once, create the administrator, then set ALLOW_INSTALL=0
 * (or remove it) and redeploy.
 *
 * The real installer stays outside the document root at ../install.php.
 */

if (getenv('ALLOW_INSTALL') !== '1' && ($_SERVER['ALLOW_INSTALL'] ?? '') !== '1') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}

require dirname(__DIR__) . '/install.php';
