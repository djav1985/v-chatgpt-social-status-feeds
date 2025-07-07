<?php
namespace App\Core;

use App\Models\UtilityHandler;
use App\Core\ErrorHandler;

class AuthMiddleware
{
    public static function check(): void
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if ($ip && UtilityHandler::isBlacklisted($ip)) {
            http_response_code(403);
            ErrorHandler::logMessage("Blacklisted IP attempted access: $ip", 'error');
            exit();
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit();
        }

        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeoutExceeded = isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $timeoutLimit);
        $userAgentChanged = isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($timeoutExceeded || $userAgentChanged) {
            session_unset();
            session_destroy();
            header('Location: /login');
            exit();
        }

        $_SESSION['timeout'] = time();
    }
}
