<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/forms/users-forms.php
 * Description: ChatGPT API Status Generator
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_users'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $totalAccounts = $_POST['total-accounts'];
        $maxApiCalls = $_POST['max-api-calls'];
        $usedApiCalls = $_POST['used-api-calls'];
        $expires = $_POST['expires'];
        $admin = $_POST['admin'];

        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        }

        // Validate username and password
        if (!preg_match('/^[a-z0-9]{8,18}$/', $username)) {
            $_SESSION['messages'][] = "Username must be 8-18 characters long, lowercase letters and numbers only.";
        }

        // Validate if the password is either already a bcrypt hash or meets the strength requirements
        if (
            !preg_match('/^\$2[ayb]\$/', $password) && // Skip if it's a bcrypt hash
            !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/', $password) // Validate strength for plain text
        ) {
            $_SESSION['messages'][] = "Password must be 8-16 characters long, including at least one letter, one number, and one symbol.";
        }


        // Validate other fields
        if (
            !filter_var($totalAccounts, FILTER_VALIDATE_INT) ||
            !filter_var($maxApiCalls, FILTER_VALIDATE_INT) ||
            !filter_var($usedApiCalls, FILTER_VALIDATE_INT) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires) || !strtotime($expires) ||
            !in_array($admin, ['0', '1'])
        ) {
            $_SESSION['messages'][] = "There was an error processing input.";
        }

        // Check if any error messages have been added to the session
        if (!empty($_SESSION['messages'])) {
            header("Location: /users");
            exit;
        } else {
            $db = new Database();

            $db->query("SELECT * FROM users WHERE username = :username");
            $db->bind(':username', $username);
            $userExists = $db->single();

            // Check if the password is already hashed
            if (!password_verify($password, $userExists->password)) {
                $password = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($userExists) {
                $db->query("UPDATE users SET password = :password, total_accounts = :totalAccounts, max_api_calls = :maxApiCalls, used_api_calls = :usedApiCalls, admin = :admin, expires = :expires WHERE username = :username");
            } else {
                $db->query("INSERT INTO users (username, password, total_accounts, max_api_calls, used_api_calls, expires, admin) VALUES (:username, :password, :totalAccounts, :maxApiCalls, :usedApiCalls, :expires, :admin)");
                // Create directory for images if user is being created
                $userImagePath = __DIR__ .  '/../../public/images/' . $username;
                if (!file_exists($userImagePath)) {
                    mkdir($userImagePath, 0777, true);
                    // Create index.php in the new directory
                    $indexFilePath = $userImagePath . '/index.php';
                    file_put_contents($indexFilePath, '<?php die(); ?>');
                }
            }
            $db->bind(':username', $username);
            $db->bind(':password', $password); // Store the hashed password
            $db->bind(':totalAccounts', $totalAccounts);
            $db->bind(':maxApiCalls', $maxApiCalls);
            $db->bind(':usedApiCalls', $usedApiCalls);
            $db->bind(':expires', $expires);
            $db->bind(':admin', $admin);
            $db->execute();

            $_SESSION['messages'][] = "User has been created or modified.";
            header("Location: /users");
            exit;
        }
    } elseif (isset($_POST['delete_user']) && isset($_POST['username'])) {
        $username = $_POST['username'];

        // Check if the user is trying to delete their own account
        if ($username === $_SESSION['username']) {
            $_SESSION['messages'][] = "Sorry, you can't delete your own account.";
        } else {
            // CSRF token validation
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
                header("Location: /accounts");
                exit;
            }

            $db = new Database();

            // Remove the user from the users table
            $db->query("DELETE FROM users WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            // Remove all accounts associated with the user from the accounts table
            $db->query("DELETE FROM accounts WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            // Remove all statuses associated with the user from the status_updates table
            $db->query("DELETE FROM status_updates WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            // Remove all log entries associated with the user from the logs table
            $db->query("DELETE FROM logs WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            $_SESSION['messages'][] = "User Deleted";
            header("Location: /accounts");
            exit;
        }
    } elseif (isset($_POST['login_as']) && isset($_POST['username'])) {
        $username = $_POST['username'];

        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
            header("Location: /accounts");
            exit;
        }

        $user = getUserInfo($username);
        if ($user) {
            // Set original username in session if not already set
            if (!isset($_SESSION['isReally'])) {
                $_SESSION['isReally'] = $_SESSION['username'];
            }
            // Change session to new user
            $_SESSION['username'] = $user->username;
            $_SESSION['logged_in'] = true;
            header("Location: /home");
            exit;
        }
    }
}
