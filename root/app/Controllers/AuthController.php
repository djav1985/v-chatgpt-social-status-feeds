<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: AuthController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\Security;
use App\Core\ErrorMiddleware;
use App\Core\Controller;

class AuthController extends Controller
{
    public static function handleRequest(): void
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
                unset($_SESSION['is_admin']);
                session_destroy();
                header('Location: /login');
                exit();
            }
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $error = 'Invalid CSRF token. Please try again.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
                (new self())->render('login', []);
                return;
            }

            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $userInfo = User::getUserInfo($username);

            // Verify user credentials against hashed password
            if ($userInfo && password_verify($password, $userInfo->password)) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $userInfo->username;
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['is_admin'] = $userInfo->admin;
                $_SESSION['timeout'] = time();
                session_regenerate_id(true);
                header('Location: /');
                exit();
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            if (Security::isBlacklisted($ip)) {
                $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            } else {
                Security::updateFailedAttempts($ip);
                $error = 'Invalid username or password.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        (new self())->render('login', []);
    }
}
