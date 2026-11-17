<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, path: string, handler: callable, middleware: list<callable>}> */
    private array $routes = [];

    /** @param callable|array{0: class-string, 1: string} $handler */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $this->toCallable($handler),
            'middleware' => array_map(fn ($mw) => $this->toCallable($mw), $middleware),
        ];
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    private function toCallable(callable|array $handler): callable
    {
        if (!is_array($handler)) {
            return $handler;
        }

        [$class, $method] = $handler;
        return static function (...$args) use ($class, $method) {
            return (new $class())->{$method}(...$args);
        };
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Path relative to the application's base URL (APP_URL), the same one base_url() builds links from.
        $uri = '/' . request_path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['path'], $uri);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $mw) {
                $mw();
            }

            $route['handler'](...array_values($params));
            return;
        }

        http_response_code(404);
        view('errors.404');
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $uri): ?array
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $uri = rtrim($uri, '/') ?: '/';

        if ($pattern === $uri) {
            return [];
        }

        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = ctype_digit($value) ? (int) $value : $value;
            }
        }
        return $params;
    }
}
