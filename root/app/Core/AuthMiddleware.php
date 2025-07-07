<?php
namespace App\Core;

use App\Models\UserHandler;
use App\Core\UtilityHandler;
use App\Core\ErrorHandler;

class AuthMiddleware
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (isset($_POST['logout'])) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $ip = $_SERVER['REMOTE_ADDR'];
                ErrorHandler::logMessage("Invalid CSRF token on logout attempt from IP: $ip", 'warning');
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                http_response_code(403);
                exit;
            }

            if (isset($_SESSION['isReally'])) {
                $_SESSION['username'] = $_SESSION['isReally'];
                unset($_SESSION['isReally']);
                header('Location: /home');
                exit;
            }

            session_destroy();
            header('Location: /login');
            exit;
        } elseif (isset($_POST['username'], $_POST['password'])) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                header('Location: /login');
                exit;
            }

            $username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
            $password = $_POST['password'];
            $userInfo = UserHandler::getUserInfo($username);

            if ($userInfo && password_verify($password, $userInfo->password)) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['timeout'] = time();
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['is_admin'] = $userInfo->admin == 1;
                header('Location: /home');
                exit;
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            if (UtilityHandler::isBlacklisted($ip)) {
                $_SESSION['messages'][] = 'Your IP has been blacklisted due to multiple failed login attempts.';
            } else {
                UtilityHandler::updateFailedAttempts($ip);
                $_SESSION['messages'][] = 'Invalid username or password.';
            }
            header('Location: /login');
            exit;
        }
    }

    public static function checkSession(): void
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        if ($ip && UtilityHandler::isBlacklisted($ip)) {
            http_response_code(403);
            echo 'Your IP address has been blacklisted. If you believe this is an error, please contact us.';
            ErrorHandler::logMessage("Blacklisted IP attempted access: $ip", 'error');
            exit;
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeoutExceeded = isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $timeoutLimit);
        $userAgentChanged = isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'];
        if ($timeoutExceeded || $userAgentChanged) {
            session_unset();
            session_destroy();
            header('Location: /login');
            exit;
        }

        $_SESSION['timeout'] = time();
    }
}
