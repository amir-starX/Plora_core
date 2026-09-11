<?php

declare(strict_types=1);

namespace Core;

class Autoloader
{
    private static array $prefixes = [];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        self::$prefixes[$prefix] = $baseDir;
    }

    public static function load(string $class): bool
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (strncmp($prefix, $class, strlen($prefix)) === 0) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return true;
                }
            }
        }
        return false;
    }
}
