<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../lib/auth-lib.php
 * Description: ChatGPT API Status Generator
 */

// Check if the request method is POST, indicating a form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the logout button has been pressed
    if (isset($_POST["logout"])) {
        // Check if the session contains a 'isReally' variable
        if (isset($_SESSION['isReally'])) {
            // If 'isReally' exists, set the username back to its original value
            $_SESSION['username'] = $_SESSION['isReally'];
            unset($_SESSION['isReally']); // Remove the 'isReally' variable from the session
            header("Location: /home"); // Redirect to home page
            exit; // Terminate script execution
        } else {
            // Destroy the current session and redirect to login page
            session_destroy(); // End the session
            header("Location: login.php"); // Redirect to login page
            exit; // Terminate script execution
        }
    } elseif (isset($_POST['username']) && isset($_POST['password'])) { // Check if username and password are provided
        $username = sanitize_input($_POST['username']); // Sanitize the username input
        $password = sanitize_input($_POST['password']); // Sanitize the password input

        // Retrieve user information based on the provided username
        $userInfo = getUserInfo($username);

        // Perform login authentication logic
        if ($userInfo && $password === $userInfo->password) { // Check if user exists and passwords match
            $_SESSION['logged_in'] = true; // Set logged in status
            $_SESSION['username'] = $username; // Store the username in session
            $_SESSION['timeout'] = time();  // Set the session timeout time to the current time
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // Store the User-Agent string for security checks
            session_regenerate_id(true); // Regenerate the session ID to prevent fixation attacks
            // Generate and store a secure CSRF token to protect against cross-site request forgery
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Create a random CSRF token

            header('Location: /home'); // Redirect to the home page after successful login
            exit; // Terminate script execution
        } else {
            // Handle failed login attempt
            $ip = $_SERVER['REMOTE_ADDR']; // Get the user's IP address

            // Check if the IP address is blacklisted
            if (is_blacklisted($ip)) {
                // If the IP is blacklisted, set an error message in the session
                $_SESSION['error']  = "Your IP has been blacklisted due to multiple failed login attempts.";
            } else {
                // Update the number of failed login attempts for the given IP address
                update_failed_attempts($ip);
                $_SESSION['error'] = "Invalid username or password."; // Set invalid credentials message
            }

            header("Location: login.php"); // Redirect back to the login page
            exit; // Terminate script execution
        }
    }
}
