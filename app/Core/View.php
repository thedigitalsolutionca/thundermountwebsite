<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /** @var array<string,mixed> */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile($view, array_merge(self::$shared, $data));
        if ($layout === null) {
            return $content;
        }
        return self::renderFile($layout, array_merge(self::$shared, $data, ['content' => $content]));
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::renderFile($view, array_merge(self::$shared, $data));
    }

    private static function renderFile(string $view, array $data): string
    {
        $file = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
