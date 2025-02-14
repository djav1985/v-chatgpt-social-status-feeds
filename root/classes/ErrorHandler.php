<?php

class ErrorHandler
{
    public function __construct()
    {
        self::register();
    }

    /**
     * Registers the error, exception, and shutdown handlers.
     */
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handles standard PHP errors and converts them to exceptions.
     * 
     * @param int $errno The error number.
     * @param string $errstr The error message.
     * @param string $errfile The file where the error occurred.
     * @param int $errline The line number where the error occurred.
     * @throws ErrorException
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        // Convert PHP warnings and notices into exceptions
        if (!(error_reporting() & $errno)) {
            // Error is suppressed with @
            return;
        }

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Handles uncaught exceptions and logs them.
     * 
     * @param Throwable $exception The uncaught exception.
     */
    public static function handleException(Throwable $exception): void
    {
        $message = "Uncaught Exception: " . $exception->getMessage() .
                   " in " . $exception->getFile() .
                   " on line " . $exception->getLine();
        self::logMessage($message, 'exception');

        // Display generic message to users
        http_response_code(500);
        echo "Something went wrong. Please try again later.";
    }

    /**
     * Handles fatal errors (shutdown errors) and logs them.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_CORE_ERROR)) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::logMessage($message, 'fatal');
            
            http_response_code(500);
            echo "A critical error occurred.";
        }
    }

    /**
     * Logs a message to the log file.
     * 
     * @param string $message The message to log.
     * @param string $type The type of log entry (e.g., 'error', 'exception', 'warning', 'info').
     */
    public static function logMessage(string $message, string $type = 'error'): void
    {
        $logFile = dirname($_SERVER['DOCUMENT_ROOT']) . '/php_app.log';
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] [$type]: $message\n";
        error_log($logMessage, 3, $logFile);
    }
}
