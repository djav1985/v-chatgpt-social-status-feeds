<?php

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

use Psr\Http\Message\ServerRequestInterface;
use App\Models\UserModel;
use App\Models\BlacklistModel;
use App\Core\ErrorManager;
use App\Core\SessionManager;
use App\Core\Response;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;

class LoginController
{
    use RequestContext;

    /**
     * Show the login form when the user is not already authenticated.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $this->captureRequest($request);
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
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $this->captureRequest($request);
        $session = SessionManager::getInstance();

        // Redirect already-logged-in users away from login form
        if ($session->get('logged_in') === true && !isset($this->requestBody['logout'])) {
            return Response::redirect('/');
        }

        // Handle logout
        if ($session->get('logged_in') === true && isset($this->requestBody['logout'])) {
            return self::logoutUser();
        }

        // Sanitize and validate input
        $username = trim($this->requestBody['username'] ?? '');
        $password = trim($this->requestBody['password'] ?? '');

        $loginErrors = self::validateLoginInput($username, $password);
        if (!empty($loginErrors)) {
            $error = $loginErrors[0];
            ErrorManager::getInstance()->log($error);
            MessageHelper::addMessage($error);
            return Response::redirect('/login');
        }

        // Validate credentials
        $userInfo = self::validateCredentials($username, $password);
        if ($userInfo) {
            $session->set('logged_in', true);
            $session->set('username', $userInfo->username);
            $session->set('user_agent', $this->requestServer['HTTP_USER_AGENT'] ?? '');
            $session->set('csrf_token', bin2hex(\App\Helpers\EncryptionHelper::bytes(32)));
            $session->set('is_admin', $userInfo->admin);
            $session->set('timeout', time());
            $session->regenerate();
            return Response::redirect('/');
        }

        // Handle failed login attempt
        $ip = $this->requestServer['REMOTE_ADDR'] ?? '';
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

        return Response::redirect('/login');
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
     * Validate login form input before checking credentials.
     *
     * @param string $username Submitted username
     * @param string $password Submitted password
     * @return string[] Validation errors, if any
     */
    private static function validateLoginInput(string $username, string $password): array
    {
        return ValidationHelper::validateLogin([
            'username' => $username,
            'password' => $password,
        ]);
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
