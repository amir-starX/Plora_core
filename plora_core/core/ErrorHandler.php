<?php

declare(strict_types=1);

namespace Core;

class ErrorHandler
{
    private static array $log = [];

    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException(\Throwable $e): void
    {
        self::logSilent('uncaught', $e);

        if (!headers_sent()) {
            http_response_code(500);
        }

        $debug = (bool) Config::get('app.debug', false);

        if ($debug) {
            echo '<h1>Application Error</h1>';
            echo '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            echo '<h1>Something went wrong</h1>';
        }
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function logSilent(string $context, \Throwable $e): void
    {
        self::$log[] = [
            'context' => $context,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'time' => date('Y-m-d H:i:s'),
        ];
    }

    public static function getLog(): array
    {
        return self::$log;
    }
}
