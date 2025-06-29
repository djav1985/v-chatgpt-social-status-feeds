<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: auth-lib.php
 * Description: Handles user authentication and session management.
 * License: MIT
 */

/**
 * Handles POST requests for user authentication and session management.
 * This includes login and logout functionality.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle logout request
    if (isset($_POST["logout"])) {
        // If the user is logged in, log them out and redirect to home
        if (isset($_SESSION['isReally'])) {
            $_SESSION['username'] = $_SESSION['isReally'];
            unset($_SESSION['isReally']);
            header("Location: /home");
            die(1);
        } else {
            // Destroy session and redirect to login page
            session_destroy();
            header("Location: login.php");
            die(1);
        }
    } elseif (isset($_POST['username']) && isset($_POST['password'])) {
        $username = ($_POST['username']);
        $password = ($_POST['password']);

        $userInfo = UserHandler::getUserInfo($username);

        // Verify user credentials
        if ($userInfo && password_verify($password, $userInfo->password)) {
            // Set session variables and regenerate session ID for security
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['timeout'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['is_admin'] = $userInfo->admin == 1;

            header('Location: /home');
            die(1);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];

            // Check if IP is blacklisted due to multiple failed login attempts
            if (UtilityHandler::isBlacklisted($ip)) {
                $_SESSION['error']  = "Your IP has been blacklisted due to multiple failed login attempts.";
            } else {
                // Update failed login attempts for the IP
                UtilityHandler::updateFailedAttempts($ip);
                $_SESSION['error'] = "Invalid username or password.";
            }
            header("Location: login.php");
            die(1);
        }
    }
}
