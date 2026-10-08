<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ErrorManager.php
 * Description: Singleton error handler providing global logging and error handling.
 */

namespace App\Core;

use ErrorException;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;

class ErrorManager
{
    /**
     * Singleton instance of the error handler.
     */
    private static ?self $instance = null;

    private static ?LoggerInterface $psrLogger = null;

    /**
     * Private constructor registers PHP error and exception handlers.
     */
    private function __construct()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Retrieve the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute the given callback within the error handler context.
     * Public static methods should appear before public instance methods.
     */
    public static function handle(callable $callback): void
    {
        $handler = self::getInstance();
        try {
            $callback();
        } catch (Throwable $exception) {
            $handler->handleException($exception);
        }
    }

    /**
     * Log a message to the application log.
     *
     * @param array<string, mixed> $context Optional context data appended as JSON.
     */
    public static function log(string $message, string $type = 'error', array $context = []): void
    {
        $line = '[' . date('Y-m-d H:i:s') . "] [$type]: $message";

        if ($context !== []) {
            $encoded = json_encode($context);
            if ($encoded !== false) {
                $line .= ' ' . $encoded;
            }
        }

        error_log($line . "\n", 3, self::resolveLogFile());
    }

    /**
     * Return a PSR-3 logger backed by ErrorManager::log().
     */
    public static function logger(): LoggerInterface
    {
        return self::$psrLogger ??= new class () extends AbstractLogger {
            public function log($level, string|Stringable $message, array $context = []): void
            {
                ErrorManager::log((string) $message, (string) $level, $context);
            }
        };
    }

    /**
     * Resolve the log file path (LOG_FILE constant overrides the default).
     */
    private static function resolveLogFile(): string
    {
        $configured = defined('LOG_FILE') ? constant('LOG_FILE') : null;
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return __DIR__ . '/../../php_app.log';
    }

    /**
     * Handle PHP errors by converting them to ErrorException.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Handle uncaught exceptions.
     */
    public function handleException(Throwable $exception): void
    {
        $message = 'Uncaught Exception: ' . $exception->getMessage() .
            ' in ' . $exception->getFile() .
            ' on line ' . $exception->getLine();
        $this->log($message, 'exception');
        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
    }

    /**
     * Handle fatal errors on shutdown.
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            $this->log($message, 'fatal');
            http_response_code(500);
            echo 'A critical error occurred.';
        }
    }
}
