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
use App\Core\Csrf;

class AuthController extends Controller
{
    public function handleRequest(): void
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout']))) {
            header('Location: /');
            exit();
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
            self::logoutUser();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
                $error = 'Invalid CSRF token. Please try again.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $userInfo = self::validateCredentials($username, $password);

                if ($userInfo) {
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
        }

        $this->render('login', []);
    }

    private static function logoutUser(): void
    {
        unset($_SESSION['is_admin']);
        session_destroy();
        header('Location: /login');
        exit();
    }

    private static function validateCredentials(string $username, string $password): ?object
    {
        $userInfo = User::getUserInfo($username);

        return ($userInfo && password_verify($password, $userInfo->password)) ? $userInfo : null;
    }
}
