<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: LoginController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\BlacklistModel;
use App\Core\ErrorManager;
use App\Core\Controller;
use App\Core\SessionManager;
use App\Core\Response;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class LoginController extends Controller
{
    /**
     * Show the login form when the user is not already authenticated.
     *
     * @return Response
     */
    public function handleRequest(): Response
    {
        $session = SessionManager::getInstance();
        if ($session->get('logged_in') === true) {
            return Response::redirect('/');
        }

        return Response::view('login');
    }

    /**
     * Handle login form submission and logout actions.
     *
     * @return Response
     */
    public function handleSubmission(): Response
    {
        $session = SessionManager::getInstance();

        // Redirect already-logged-in users away from login form
        if ($session->get('logged_in') === true && !isset($_POST['logout'])) {
            return Response::redirect('/');
        }

        // Handle logout
        if ($session->get('logged_in') === true && isset($_POST['logout'])) {
            if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                MessageHelper::addMessage('Invalid CSRF token. Please try again.');
                return Response::redirect('/login');
            }
            return self::logoutUser();
        }

        // Validate CSRF token
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token. Please try again.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            return Response::view('login');
        }

        // Trim and validate input
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate credentials
        $userInfo = self::validateCredentials($username, $password);
        if ($userInfo) {
            $session->set('logged_in', true);
            $session->set('username', $userInfo->username);
            $session->set('user_agent', $_SERVER['HTTP_USER_AGENT']);
            $session->set('csrf_token', \hash('sha256', \uniqid('', true)));
            $session->set('is_admin', $userInfo->admin);
            $session->set('timeout', time());
            $session->regenerate();
            return Response::redirect('/');
        }

        // Handle failed login attempt
        $ip = $_SERVER['REMOTE_ADDR'];
        if (BlacklistModel::isBlacklisted($ip)) {
            $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
        } else {
            BlacklistModel::updateFailedAttempts($ip);
            $error = 'Invalid username or password.';
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
        }

        return Response::view('login');
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
        $userInfo = UserModel::getUserInfo($username);

        if ($userInfo && isset($userInfo->password) && password_verify($password, (string) $userInfo->password)) {
            return $userInfo;
        }

        return null;
    }

    /**
     * Destroy the session and redirect to login page.
     *
     * @return Response
     */
    private static function logoutUser(): Response
    {
        SessionManager::getInstance()->destroy();
        return Response::redirect('/login');
    }
}
