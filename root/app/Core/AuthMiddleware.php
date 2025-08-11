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

use App\Services\SecurityService;
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
        if ($ip && SecurityService::isBlacklisted($ip)) {
            http_response_code(403);
            ErrorMiddleware::logMessage("Blacklisted IP attempted access: $ip", 'error');
            exit();
        }

        $session = SessionManager::getInstance();
        if (!$session->get('logged_in')) {
            header('Location: /login');
            exit();
        }

        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeout = $session->get('timeout');
        $timeoutExceeded = is_int($timeout) && (time() - $timeout > $timeoutLimit);
        $userAgent = $session->get('user_agent');
        $userAgentChanged = is_string($userAgent) && $userAgent !== ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($timeoutExceeded || $userAgentChanged) {
            $session->destroy();
            header('Location: /login');
            exit();
        }

        $session->set('timeout', time());
    }
}
