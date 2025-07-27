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
use App\Services\SecurityService;
use App\Core\ErrorMiddleware;
use App\Core\Controller;
use App\Core\Csrf;

class AuthController extends Controller
{
    /**
     * Show the login form when the user is not already authenticated.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: /');
            exit();
        }

        $this->render('login', []);
    }

    /**
     * Handle login form submission and logout actions.
     *
     * @return void
     */
    public function handleSubmission(): void
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_POST['logout'])) {
            if (Csrf::validate($_POST['csrf_token'] ?? '')) {
                self::logoutUser();
            } else {
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                header('Location: /login');
                exit();
            }
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !isset($_POST['logout'])) {
            header('Location: /');
            exit();
        }

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
            if (SecurityService::isBlacklisted($ip)) {
                $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            } else {
                SecurityService::updateFailedAttempts($ip);
                $error = 'Invalid username or password.';
                ErrorMiddleware::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        $this->render('login', []);
    }

    /**
     * Destroy the user session and redirect to the login page.
     *
     * @return void
     */
    private static function logoutUser(): void
    {
        unset($_SESSION['is_admin']);
        session_destroy();
        header('Location: /login');
        exit();
    }

    /**
     * Validate the supplied login credentials.
     *
     * @param string $username Submitted username
     * @param string $password Submitted password
     * @return object|null Returns user info on success or null on failure
     */
    private static function validateCredentials(string $username, string $password): ?object
    {
        $userInfo = User::getUserInfo($username);

        return ($userInfo && password_verify($password, $userInfo->password)) ? $userInfo : null;
    }
}
