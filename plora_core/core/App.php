<?php

declare(strict_types=1);

namespace Core;

class App
{
    public Router $router;
    public PluginManager $plugins;
    private Request $request;
    private array $middleware = [];

    public function __construct()
    {
        ErrorHandler::register();
        Config::load();

        $this->router = new Router();
        $this->request = new Request();
        $this->plugins = new PluginManager($this);

        Hook::doAction('before_load', $this);

        $this->plugins->loadAll();

        Hook::doAction('after_load', $this);
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function run(): void
    {
        Hook::doAction('register_routes', $this->router, $this);

        $routesFile = APP_PATH . '/Routes.php';
        if (is_file($routesFile)) {
            $router = $this->router;
            $app = $this;
            require $routesFile;
        }

        Hook::doAction('before_route', $this->request, $this);

        $match = $this->router->match($this->request);

        $response = new Response();

        if ($match === null) {
            Hook::doAction('route_not_found', $this->request, $this);
            $response->status(404)->body($this->renderNotFound());
            $response->send();
            return;
        }

        foreach (array_merge($this->middleware, $match['middleware']) as $middlewareItem) {
            $result = call_user_func($middlewareItem, $this->request, $response);
            if ($result === false) {
                $response->send();
                return;
            }
        }

        Hook::doAction('after_route', $this->request, $this);

        $output = $this->dispatch($match['handler'], $match['params']);

        if ($output instanceof Response) {
            $output->send();
            return;
        }

        Hook::doAction('before_render', $this->request, $this);
        $output = Hook::applyFilter('render_output', $output);
        Hook::doAction('after_render', $this->request, $this);

        $response->body((string) $output);
        $response->send();
    }

    private function dispatch(mixed $handler, array $params): mixed
    {
        if ($handler instanceof \Closure) {
            return call_user_func_array($handler, [$this->request, $params]);
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$controllerName, $method] = explode('@', $handler, 2);

            if (!str_contains($controllerName, '\\')) {
                $controllerName = 'App\\Controllers\\' . $controllerName;
            }

            if (!class_exists($controllerName)) {
                throw new \RuntimeException("Controller not found: {$controllerName}");
            }

            $controller = new $controllerName($this);

            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method not found: {$controllerName}::{$method}");
            }

            return call_user_func_array([$controller, $method], [$this->request, $params]);
        }

        if (is_callable($handler)) {
            return call_user_func_array($handler, [$this->request, $params]);
        }

        throw new \RuntimeException('Invalid route handler.');
    }

    private function renderNotFound(): string
    {
        return '<h1>404 - Not Found</h1>';
    }

    public function view(string $view, array $data = []): string
    {
        $path = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        return TemplateEngine::render($path, $data);
    }
}
