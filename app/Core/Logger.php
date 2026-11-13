<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function write(string $level, string $message, array $context = []): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] %s %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : ''
        );
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function activity(
        ?int $adminId,
        string $action,
        string $entityType = '',
        string $entityId = '',
        string $meta = ''
    ): void {
        try {
            Database::insert('admin_activity', [
                'user_id' => $adminId,
                'action' => mb_substr($action, 0, 80),
                'entity_type' => mb_substr($entityType, 0, 60),
                'entity_id' => mb_substr($entityId, 0, 60),
                'meta' => mb_substr($meta, 0, 255),
                'ip' => Request::ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            self::write('warning', 'Activity log insert skipped', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }
}
