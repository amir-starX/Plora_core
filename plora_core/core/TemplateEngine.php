<?php

declare(strict_types=1);

namespace Core;

class TemplateEngine
{
    private static array $extensions = [];

    public static function extend(string $pattern, callable $handler): void
    {
        self::$extensions[] = ['pattern' => $pattern, 'handler' => $handler];
    }

    public static function render(string $file, array $data = []): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$file}");
        }

        $source = file_get_contents($file);
        $source = self::applyExtensions($source);
        $source = Hook::applyFilter('template_source', $source, $file);

        $compiledPath = self::compileToTempFile($source);

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $compiledPath;
        } finally {
            @unlink($compiledPath);
        }

        return ob_get_clean();
    }

    private static function applyExtensions(string $source): string
    {
        foreach (self::$extensions as $extension) {
            $source = preg_replace_callback(
                $extension['pattern'],
                fn (array $matches) => (string) call_user_func($extension['handler'], $matches),
                $source
            );
        }

        $source = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?= htmlspecialchars((string)($1), ENT_QUOTES, \'UTF-8\') ?>', $source);
        $source = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?= $1 ?>', $source);

        return $source;
    }

    private static function compileToTempFile(string $source): string
    {
        $path = sys_get_temp_dir() . '/tpl_' . uniqid('', true) . '.php';
        file_put_contents($path, $source);
        return $path;
    }
}
