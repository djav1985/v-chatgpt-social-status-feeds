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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

class ErrorManager
{
    private static ?self $instance = null;
    private static ?LoggerInterface $psrLogger = null;

    /**
     * Register PHP error, exception, and shutdown handlers.
     */
    private function __construct()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Return the singleton ErrorManager instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Execute a callable inside the error-handling context.
     *
     * Any Throwable thrown by $callback is forwarded to handleException().
     *
     * @param callable $callback The code to execute.
     * @return void
     */
    public static function handle(callable $callback): void
    {
        $manager = self::getInstance();
        try {
            $callback();
        } catch (Throwable $exception) {
            $manager->handleException($exception);
        }
    }

    /**
     * Write a message to the application log.
     *
     * @param string               $message Message to log.
     * @param string               $type    Log level/label (e.g. error, info, exception, fatal).
     * @param array<string, mixed> $context Optional context data.
     * @return void
     */
    public static function log(string $message, string $type = 'error', array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = '[' . $timestamp . '] [' . $type . ']: ' . $message;

        if (!empty($context)) {
            $encodedContext = json_encode($context);
            if ($encodedContext !== false) {
                $line .= ' ' . $encodedContext;
            }
        }

        error_log($line . "\n", 3, self::resolveLogFile());
    }

    /**
     * Return a PSR-3-compatible logger surface backed by ErrorManager.
     *
     * @return LoggerInterface
     */
    public static function logger(): LoggerInterface
    {
        if (self::$psrLogger === null) {
            self::$psrLogger = new class () implements LoggerInterface {
                public function emergency(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::EMERGENCY, $context);
                }

                public function alert(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::ALERT, $context);
                }

                public function critical(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::CRITICAL, $context);
                }

                public function error(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::ERROR, $context);
                }

                public function warning(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::WARNING, $context);
                }

                public function notice(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::NOTICE, $context);
                }

                public function info(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::INFO, $context);
                }

                public function debug(Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, LogLevel::DEBUG, $context);
                }

                public function log($level, Stringable|string $message, array $context = []): void
                {
                    ErrorManager::log((string) $message, (string) $level, $context);
                }
            };
        }

        return self::$psrLogger;
    }

    /**
     * PSR-3 convenience: emergency level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function emergency(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::EMERGENCY, $context);
    }

    /**
     * PSR-3 convenience: alert level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function alert(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::ALERT, $context);
    }

    /**
     * PSR-3 convenience: critical level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function critical(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::CRITICAL, $context);
    }

    /**
     * PSR-3 convenience: warning level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function warning(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::WARNING, $context);
    }

    /**
     * PSR-3 convenience: notice level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function notice(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::NOTICE, $context);
    }

    /**
     * PSR-3 convenience: info level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function info(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::INFO, $context);
    }

    /**
     * PSR-3 convenience: debug level logging.
     *
     * @param Stringable|string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public static function debug(Stringable|string $message, array $context = []): void
    {
        self::log((string) $message, LogLevel::DEBUG, $context);
    }

    /**
     * Log an HTTP request.
     *
     * @param string $method HTTP method (GET, POST, etc).
     * @param string $path   Request path.
     * @param array<string, mixed> $context Optional additional context.
     * @return void
     */
    public static function logRequest(string $method, string $path, array $context = []): void
    {
        $defaultContext = [
            'method' => $method,
            'path' => $path,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];
        self::log('HTTP Request', 'info', array_merge($defaultContext, $context));
    }

    /**
     * Log an HTTP response.
     *
     * @param string $method HTTP method.
     * @param string $path   Request path.
     * @param int    $status HTTP status code.
     * @param array<string, mixed> $context Optional additional context.
     * @return void
     */
    public static function logResponse(string $method, string $path, int $status, array $context = []): void
    {
        $defaultContext = [
            'method' => $method,
            'path' => $path,
            'status' => $status,
        ];
        self::log('HTTP Response', 'info', array_merge($defaultContext, $context));
    }

    /**
     * Convert a PHP error into an ErrorException.
     *
     * Registered as the global error handler in __construct().
     *
     * @param int    $errno   Error level constant (E_WARNING, E_NOTICE, etc.).
     * @param string $errstr  Error message.
     * @param string $errfile File where the error occurred.
     * @param int    $errline Line number where the error occurred.
     * @return bool False when the error code is not included in error_reporting().
     * @throws \ErrorException Always, when the error code is active.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Handle an uncaught exception.
     *
     * Logs the exception details, then either writes to STDERR (CLI) or emits
     * a 500 response and exits (HTTP context).
     *
     * @param \Throwable $exception The uncaught exception or error.
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        $message = 'Uncaught Exception: ' . $exception->getMessage() .
            ' in ' . $exception->getFile() .
            ' on line ' . $exception->getLine();
        self::log($message, 'exception');

        if (PHP_SAPI === 'cli') {
            if (defined('STDERR')) {
                fwrite(STDERR, $message . "\n");
            } else {
                echo $message . "\n";
            }
            exit(1);
        }

        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
        exit(1);
    }

    /**
     * Handle fatal errors detected after script shutdown.
     *
     * Registered via register_shutdown_function(). Checks error_get_last() for
     * E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, and E_PARSE. Logs the error and
     * emits a 500 response when a fatal error is found.
     *
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::log($message, 'fatal');
            http_response_code(500);
            echo 'A critical error occurred.';
        }
    }

    /**
     * Resolve the log file path.
     *
     * @return string
     */
    private static function resolveLogFile(): string
    {
        if (defined('LOG_FILE') && is_string(LOG_FILE) && LOG_FILE !== '') {
            return LOG_FILE;
        }

        return __DIR__ . '/../../php_app.log';
    }
}
