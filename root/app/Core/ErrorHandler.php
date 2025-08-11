<?php
/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ErrorHandler.php
 * Description: Singleton error handler providing global logging and error handling.
 */

namespace App\Core;

use ErrorException;
use Throwable;

class ErrorHandler
{
    /**
     * Singleton instance of the error handler.
     */
    private static ?self $instance = null;

    /**
     * Log file location.
     */
    private string $logFile;

    /**
     * Private constructor registers PHP error and exception handlers.
     */
    private function __construct()
    {
        $this->logFile = __DIR__ . '/../../php_app.log';
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
     * Log a message to the application log.
     */
    public function log(string $message, string $type = 'error'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type]: $message\n";
        error_log($logMessage, 3, $this->logFile);
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

    /**
     * Execute the given callback within the error handler context.
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
     * Display and clear session-based messages.
     */
    public static function displayAndClearMessages(): void
    {
        $session = SessionManager::getInstance();
        $messages = $session->get('messages', []);
        if (!empty($messages)) {
            foreach ($messages as $message) {
                echo '<script>showToast(' . json_encode($message) . ');</script>';
            }
            $session->set('messages', []);
        }
    }
}
