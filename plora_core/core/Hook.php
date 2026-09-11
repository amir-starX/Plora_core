<?php

declare(strict_types=1);

namespace Core;

class Hook
{
    private static array $actions = [];
    private static array $filters = [];

    public static function addAction(string $name, callable $callback, int $priority = 10): void
    {
        self::$actions[$name][$priority][] = $callback;
        ksort(self::$actions[$name]);
    }

    public static function doAction(string $name, mixed ...$args): void
    {
        if (empty(self::$actions[$name])) {
            return;
        }

        foreach (self::$actions[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    call_user_func_array($callback, $args);
                } catch (\Throwable $e) {
                    ErrorHandler::logSilent('hook:' . $name, $e);
                }
            }
        }
    }

    public static function addFilter(string $name, callable $callback, int $priority = 10): void
    {
        self::$filters[$name][$priority][] = $callback;
        ksort(self::$filters[$name]);
    }

    public static function applyFilter(string $name, mixed $value, mixed ...$args): mixed
    {
        if (empty(self::$filters[$name])) {
            return $value;
        }

        foreach (self::$filters[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $value = call_user_func_array($callback, array_merge([$value], $args));
                } catch (\Throwable $e) {
                    ErrorHandler::logSilent('filter:' . $name, $e);
                }
            }
        }

        return $value;
    }

    public static function hasAction(string $name): bool
    {
        return !empty(self::$actions[$name]);
    }

    public static function removeAllForHook(string $name): void
    {
        unset(self::$actions[$name], self::$filters[$name]);
    }
}
