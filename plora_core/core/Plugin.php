<?php

declare(strict_types=1);

namespace Core;

abstract class Plugin
{
    protected App $app;
    protected array $meta;
    protected string $path;

    final public function __construct(App $app, array $meta, string $path)
    {
        $this->app = $app;
        $this->meta = $meta;
        $this->path = $path;
    }

    abstract public function register(): void;

    public function boot(): void
    {
    }

    public function name(): string
    {
        return $this->meta['name'] ?? static::class;
    }

    public function version(): string
    {
        return $this->meta['version'] ?? '0.0.0';
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function path(string $relative = ''): string
    {
        return $relative === '' ? $this->path : $this->path . '/' . ltrim($relative, '/');
    }

    protected function app(): App
    {
        return $this->app;
    }

    protected function hook(string $name, callable $callback, int $priority = 10): void
    {
        Hook::addAction($name, $callback, $priority);
    }

    protected function filter(string $name, callable $callback, int $priority = 10): void
    {
        Hook::addFilter($name, $callback, $priority);
    }

    protected function view(string $view, array $data = []): string
    {
        $file = $this->path('Views/' . str_replace('.', '/', $view) . '.php');
        return TemplateEngine::render($file, $data);
    }
}
