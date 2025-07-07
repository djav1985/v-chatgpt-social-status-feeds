<?php

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

use App\Core\UtilityHandler;
use App\Core\ErrorHandler;

class AuthController
{
    public static function handleRequest(): void
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
                session_destroy();
                header('Location: /login');
                exit();
            }
            header('Location: /');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? UtilityHandler::validateUsername($_POST['username']) : null;
            $password = isset($_POST['password']) ? UtilityHandler::validatePassword($_POST['password']) : null;

            if ($username === VALID_USERNAME && $password === VALID_PASSWORD) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                session_regenerate_id(true);
                header('Location: /');
                exit();
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            if (UtilityHandler::isBlacklisted($ip)) {
                $error = 'Your IP has been blacklisted due to multiple failed login attempts.';
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
            } else {
                UtilityHandler::updateFailedAttempts($ip);
                $error = 'Invalid username or password.';
                ErrorHandler::logMessage($error);
                $_SESSION['messages'][] = $error;
            }
        }

        include __DIR__ . '/../Views/login.php';
    }
}
