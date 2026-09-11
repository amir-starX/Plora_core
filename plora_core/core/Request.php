<?php

declare(strict_types=1);

namespace Core;

class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $body;
    private array $server;
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->server = $_SERVER;

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = $this->stripBasePath($uri);
        $this->uri = '/' . trim($uri, '/');
        if ($this->uri === '/') {
            $this->uri = '/';
        }

        $this->query = $this->sanitize($_GET);
        $this->body = $this->sanitize($this->parseBody());
        $this->headers = $this->extractHeaders();
    }

    private function stripBasePath(string $uri): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = rtrim($basePath, '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        return $uri === '' ? '/' : $uri;
    }

    private function parseBody(): array
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [];
            }
            return $_POST;
        }
        return [];
    }

    private function sanitize(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });
        return $data;
    }

    private function extractHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }
}
