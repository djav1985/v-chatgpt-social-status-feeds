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

        if ($password === $password2) {
            // Initialize database object
            $db = new Database();

            // Update password in the database
            $db->query("UPDATE users SET password = :password WHERE username = :username");
            $db->bind(':username', $username);
            $db->bind(':password', $password); // Consider using password_hash($password, PASSWORD_DEFAULT) for security
            if ($db->execute()) {
                $_SESSION['messages'][] = "Password Updated!";
            } else {
                $_SESSION['messages'][] = "Failed to update password.";
            }
        } else {
            $_SESSION['messages'][] = "Passwords do not match. Please try again.";
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
