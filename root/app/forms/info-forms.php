<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/forms/info-forms.php
 * Description: ChatGPT API Status Generator
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["change_password"])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];

        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        }

        // Check if passwords match
        if ($password !== $password2) {
            $_SESSION['messages'][] = "Passwords do not match. Please try again.";
        }

        // Validate password
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/', $password)) {
            $_SESSION['messages'][] = "Password must be 8-16 characters long, including at least one letter, one number, and one symbol.";
        }

        // If there are any error messages, redirect and exit
        if (!empty($_SESSION['messages'])) {
            header("Location: /info");
            exit;
        }

        // Hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Initialize database object
        $db = new Database();

        // Update password in the database
        $db->query("UPDATE users SET password = :password WHERE username = :username");
        $db->bind(':username', $username);
        $db->bind(':password', $hashedPassword); // Store the hashed password
        $db->execute();

        // Add success message
        $_SESSION['messages'][] = "Password Updated!";
        header("Location: /info");
        exit;
    }
}
