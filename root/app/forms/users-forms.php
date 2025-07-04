<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: users-forms.php
 * Description: Handles user form submissions for editing, deleting, and logging in as users.
 * License: MIT
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token validity
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        header("Location: /users");
        exit;
    }

    // Handle user edit form submission
    if (isset($_POST['edit_users'])) {
        // Extract and validate form inputs
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $totalAccounts = intval($_POST['total-accounts']);
        $maxApiCalls = intval($_POST['max-api-calls']);
        $usedApiCalls = intval($_POST['used-api-calls']);
        $expires = trim($_POST['expires']);
        $admin = intval($_POST['admin']);

        // Validate username format
        if (!preg_match('/^[a-z0-9]{5,16}$/', $username)) {
            $_SESSION['messages'][] = "Username must be 5-16 characters long, lowercase letters and numbers only.";
        }

        // Validate password format if provided
        if (!empty($password) && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/', $password)) {
            $_SESSION['messages'][] = "Password must be 8-16 characters long, including at least one letter, one number, and one symbol.";
        }

        // Redirect if there are validation errors
        if (!empty($_SESSION['messages'])) {
            header("Location: /users");
            exit;
        } else {
            try {
                // Check if user exists
                $userExists = UserHandler::userExists($username);
                // Hash password if it's not already hashed and provided
                if (!empty($password) && (!$userExists || !password_verify($password, $userExists->password))) {
                    $password = password_hash($password, PASSWORD_DEFAULT);
                } elseif ($userExists) {
                    // Keep the existing password if not provided
                    $password = $userExists->password;
                }

                // Update or insert user record
                $isUpdate = $userExists !== null;
                if (!$userExists) {
                    $isUpdate = false;
                }
                $result = UserHandler::updateUser($username, $password, $totalAccounts, $maxApiCalls, $usedApiCalls, $expires, $admin, $isUpdate);
                if ($result) {
                    // Create user image directory if it doesn't exist
                    if (!$userExists) {
                        $userImagePath = __DIR__ .  '/../../public/images/' . $username;
                        if (!file_exists($userImagePath)) {
                            mkdir($userImagePath, 0755, true);
                            $indexFilePath = $userImagePath . '/index.php';
                            file_put_contents($indexFilePath, '<?php die(); ?>');
                        }
                    }
                    $_SESSION['messages'][] = "User has been created or modified.";
                } else {
                    $_SESSION['messages'][] = "Failed to create or modify user.";
                }
            } catch (Exception $e) {
                $_SESSION['messages'][] = "Failed to create or modify user: " . $e->getMessage();
            }
            header("Location: /users");
            exit;
        }
    } elseif (isset($_POST['delete_user']) && isset($_POST['username'])) {
        // Handle user deletion
        $username = $_POST['username'];

        // Prevent self-deletion
        if ($username === $_SESSION['username']) {
            $_SESSION['messages'][] = "Sorry, you can't delete your own account.";
        } else {
            // Delete user and related records
            try {
                UserHandler::deleteUser($username);
                $_SESSION['messages'][] = "User Deleted";
            } catch (Exception $e) {
                $_SESSION['messages'][] = "Failed to delete user: " . $e->getMessage();
            }
        }
        header("Location: /users");
        exit;
    } elseif (isset($_POST['login_as']) && isset($_POST['username'])) {
        // Handle login as another user
        $username = $_POST['username'];

        try {
            // Get user info and set session
            $user = UserHandler::getUserInfo($username);
            if ($user) {
                if (!isset($_SESSION['isReally'])) {
                    $_SESSION['isReally'] = $_SESSION['username'];
                }
                $_SESSION['username'] = $user->username;
                $_SESSION['logged_in'] = true;
                header("Location: /home");
                exit;
            } else {
                $_SESSION['messages'][] = "Failed to login as user.";
            }
        } catch (Exception $e) {
            $_SESSION['messages'][] = "Failed to login as user: " . $e->getMessage();
        }
        header("Location: /users");
        exit;
    }
}
