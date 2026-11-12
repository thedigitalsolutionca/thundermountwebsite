<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,params:array,handler:callable,middleware:array}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function any(string $pattern, callable $handler, array $middleware = []): void
    {
        foreach (['GET', 'POST'] as $method) {
            $this->add($method, $pattern, $handler, $middleware);
        }
    }

    private function add(string $method, string $pattern, callable $handler, array $middleware): void
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern === '//') {
            $pattern = '/';
        }
        $params = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                $rule = $m[2] ?? '[^/]+';
                return '(' . $rule . ')';
            },
            $pattern
        );
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Request::path();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }
            array_shift($matches);
            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }

            try {
                foreach ($route['middleware'] as $middleware) {
                    if (is_callable($middleware)) {
                        $middleware();
                    }
                }
                $result = ($route['handler'])(...array_values($params));
                if (is_string($result)) {
                    Response::html($result);
                }
                return;
            } catch (Throwable $e) {
                self::handleException($e);
                return;
            }
        }

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            Response::html(View::render('errors/error', [
                'title' => 'Method not allowed',
                'status' => 405,
                'message' => 'That request method is not supported for this address.',
            ], 'layouts/blank'), 405);
            return;
        }

        Response::html(View::render('errors/404', ['title' => 'Page not found'], 'layouts/storefront'), 404);
    }

    public static function handleException(Throwable $e): void
    {
        Logger::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
        ]);

        if ((bool) Config::get('app.debug', false)) {
            $message = $e->getMessage();
            $trace = $e->getTraceAsString();
        } else {
            $message = 'An unexpected error occurred. Please try again, or contact us if the problem continues.';
            $trace = '';
        }

        if (Request::wantsJson()) {
            Response::json(['error' => $message], 500);
        }
        Response::html(View::render('errors/error', [
            'title' => 'Something went wrong',
            'status' => 500,
            'message' => $message,
            'trace' => $trace,
        ], 'layouts/blank'), 500);
    }
}
