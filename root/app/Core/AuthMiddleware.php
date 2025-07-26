<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: AuthMiddleware.php
 * Description: AI Social Status Generator
 */

namespace App\Core;

use App\Models\Security;
use App\Core\ErrorMiddleware;

class AuthMiddleware
{
    /**
     * Verifies the user's authentication and session integrity.
     *
     * This method checks IP blacklist status, login state and session
     * expiration to prevent unauthorized access.
     */
    public static function check(): void
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if ($ip && Security::isBlacklisted($ip)) {
            http_response_code(403);
            ErrorMiddleware::logMessage("Blacklisted IP attempted access: $ip", 'error');
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
