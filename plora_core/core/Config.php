<?php

declare(strict_types=1);

namespace Core;

class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $main = CONFIG_PATH . '/config.php';
        $plugins = CONFIG_PATH . '/plugins.php';
        $database = DATABASE_PATH . '/config.php';

        self::$items = is_file($main) ? require $main : [];

        if (is_file($plugins)) {
            self::$items['plugins'] = array_merge(
                self::$items['plugins'] ?? [],
                require $plugins
            );
        }

        self::$items['database'] = is_file($database) ? require $database : [];

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function all(): array
    {
        self::load();
        return self::$items;
    }
}
