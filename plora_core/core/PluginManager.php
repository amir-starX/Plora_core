<?php

declare(strict_types=1);

namespace Core;

class PluginManager
{
    private App $app;
    private array $loaded = [];
    private array $meta = [];
    private array $errors = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function loadAll(): void
    {
        $enabled = Config::get('plugins', []);
        $directories = $this->discover();
        $order = $this->resolveOrder($directories, $enabled);

        foreach ($order as $name) {
            $this->loadPlugin($name, $directories[$name]);
        }

        Hook::doAction('plugins_loaded', $this);

        foreach ($this->loaded as $plugin) {
            try {
                $plugin->boot();
            } catch (\Throwable $e) {
                $this->errors[] = $plugin->name() . ': ' . $e->getMessage();
                ErrorHandler::logSilent('plugin_boot:' . $plugin->name(), $e);
            }
        }
    }

    private function discover(): array
    {
        $directories = [];
        if (!is_dir(PLUGINS_PATH)) {
            return $directories;
        }

        foreach (scandir(PLUGINS_PATH) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = PLUGINS_PATH . '/' . $entry;
            if (is_dir($path) && is_file($path . '/plugin.yml')) {
                $directories[$entry] = $path;
            }
        }

        return $directories;
    }

    private function resolveOrder(array $directories, array $enabled): array
    {
        $meta = [];
        foreach ($directories as $name => $path) {
            if (empty($enabled[$name])) {
                continue;
            }
            $yaml = YamlParser::parseFile($path . '/plugin.yml');
            $this->meta[$name] = $yaml;
            $meta[$name] = $yaml['dependencies'] ?? [];
        }

        $resolved = [];
        $visiting = [];

        $visit = function (string $name) use (&$visit, &$resolved, &$visiting, $meta) {
            if (isset($resolved[$name]) || !array_key_exists($name, $meta)) {
                return;
            }
            if (isset($visiting[$name])) {
                return;
            }
            $visiting[$name] = true;
            foreach ($meta[$name] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$name]);
            $resolved[$name] = true;
        };

        foreach (array_keys($meta) as $name) {
            $visit($name);
        }

        return array_keys($resolved);
    }

    private function loadPlugin(string $name, string $path): void
    {
        try {
            $meta = $this->meta[$name] ?? YamlParser::parseFile($path . '/plugin.yml');

            if (!empty($meta['requires']['php'])) {
                $constraint = $meta['requires']['php'];
                if (!$this->phpVersionSatisfies($constraint)) {
                    throw new \RuntimeException("PHP version constraint not satisfied: {$constraint}");
                }
            }

            $mainFile = $meta['main'] ?? ($name . '.php');
            $mainPath = $path . '/' . $mainFile;

            if (!is_file($mainPath)) {
                throw new \RuntimeException("Main file not found: {$mainFile}");
            }

            $className = $meta['class'] ?? $name;

            $namespace = $this->extractNamespace($className);
            if ($namespace !== '') {
                Autoloader::addNamespace($namespace, $path);
            }

            require_once $mainPath;

            if (!class_exists($className)) {
                throw new \RuntimeException("Plugin class not found: {$className}");
            }

            if (!is_subclass_of($className, Plugin::class)) {
                throw new \RuntimeException("Plugin class must extend Core\\Plugin: {$className}");
            }

            $instance = new $className($this->app, $meta, $path);
            $instance->register();

            $this->loaded[$name] = $instance;
        } catch (\Throwable $e) {
            $this->errors[] = $name . ': ' . $e->getMessage();
            ErrorHandler::logSilent('plugin_load:' . $name, $e);
        }
    }

    private function extractNamespace(string $className): string
    {
        $parts = explode('\\', $className);
        array_pop($parts);
        return implode('\\', $parts);
    }

    private function phpVersionSatisfies(string $constraint): bool
    {
        if (preg_match('/^>=\s*([\d.]+)$/', $constraint, $m)) {
            return version_compare(PHP_VERSION, $m[1], '>=');
        }
        if (preg_match('/^([\d.]+)$/', $constraint, $m)) {
            return version_compare(PHP_VERSION, $m[1], '>=');
        }
        return true;
    }

    public function loaded(): array
    {
        return $this->loaded;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function get(string $name): ?Plugin
    {
        return $this->loaded[$name] ?? null;
    }
}
