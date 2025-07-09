<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: info-forms.php
 * Description: Handles form submissions for changing user passwords and updating profile information.
 * License: MIT
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token validity
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        header("Location: /info");
        exit;
    }

    // Handle password change form submission
    if (isset($_POST["change_password"])) {
        // Always use the currently logged in user for password updates
        $username = $_SESSION['username'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];

        // Check if passwords match
        if ($password !== $password2) {
            $_SESSION['messages'][] = "Passwords do not match. Please try again.";
        }

        // Validate password format
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/', $password)) {
            $_SESSION['messages'][] = "Password must be 8-16 characters long, including at least one letter, one number, and one symbol.";
        }

        // Redirect if there are validation errors
        if (!empty($_SESSION['messages'])) {
            header("Location: /info");
            exit;
        }

        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update user password in the database
        try {
            UserHandler::updatePassword($username, $hashedPassword);
            $_SESSION['messages'][] = "Password Updated!";
        } catch (Exception $e) {
            $_SESSION['messages'][] = "Password update failed: " . $e->getMessage();
        }
        header("Location: /info");
        exit;
    } elseif (isset($_POST["update_profile"])) {
        // Handle profile update form submission
        $username = $_SESSION['username'];
        $who = trim($_POST['who']);
        $where = trim($_POST['where']);
        $what = trim($_POST['what']);
        $goal = trim($_POST['goal']);

        // Validate form inputs
        if (empty($who) || empty($where) || empty($what) || empty($goal)) {
            $_SESSION['messages'][] = "All fields are required.";
            header("Location: /info");
            exit;
        }

        // Update user profile in the database
        try {
            UserHandler::updateProfile($username, $who, $where, $what, $goal);
            $_SESSION['messages'][] = "Profile Updated!";
        } catch (Exception $e) {
            $_SESSION['messages'][] = "Profile update failed: " . $e->getMessage();
        }
        header("Location: /info");
        exit;
    }
}
