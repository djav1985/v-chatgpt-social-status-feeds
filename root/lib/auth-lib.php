<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: auth-lib.php
 * Description: ChatGPT API Status Generator
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["logout"])) {
        if (isset($_SESSION['isReally'])) {
            $_SESSION['username'] = $_SESSION['isReally'];
            unset($_SESSION['isReally']);
            header("Location: /home");
            exit;
        } else {
            session_destroy();
            header("Location: login.php");
            exit;
        }
    } elseif (isset($_POST['username']) && isset($_POST['password'])) {
        $username = sanitize_input($_POST['username']);
        $password = sanitize_input($_POST['password']);

        // Get user info
        $userInfo = getUserInfo($username);

        // Perform your login authentication logic here
        if ($userInfo && $password === $userInfo->password) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['timeout'] = time();  // Set the session timeout time
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // Store the User-Agent string
            session_regenerate_id(true); // Regenerate the session ID
            // Generate and store CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure CSRF token

            header('Location: /home');
            exit;
        } else {
            // Failed login
            $ip = $_SERVER['REMOTE_ADDR'];

            if (is_blacklisted($ip)) {
                // User is blacklisted
                $_SESSION['error']  = "Your IP has been blacklisted due to multiple failed login attempts.";
            } else {
                // Update the number of failed login attempts
                update_failed_attempts($ip);
                $_SESSION['error'] = "Invalid username or password.";
            }

            header("Location: login.php");
            exit;
        }
    }
}
