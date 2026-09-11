<?php

declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function any(string $path, mixed $handler, array $middleware = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->add($method, $path, $handler, $middleware);
        }
    }

    public function add(string $method, string $path, mixed $handler, array $middleware = []): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->compile($path),
        ];
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#u';
    }

    public function match(Request $request): ?array
    {
        $uri = $request->uri();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    fn ($key) => !is_int($key),
                    ARRAY_FILTER_USE_KEY
                );
                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }

        return null;
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
