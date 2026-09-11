<?php

declare(strict_types=1);

namespace SamplePlugin;

class Cache
{
    public static function remember(int $seconds, callable $callback): string
    {
        $dir = sys_get_temp_dir() . '/php_framework_cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $origin = ($trace[1]['file'] ?? 'unknown') . ':' . ($trace[1]['line'] ?? 0);
        $key = md5($origin . ':' . $seconds);
        $file = $dir . '/' . $key . '.cache';

        if (is_file($file) && (time() - filemtime($file)) < $seconds) {
            return (string) file_get_contents($file);
        }

        $output = (string) $callback();
        file_put_contents($file, $output);

        return $output;
    }
}
