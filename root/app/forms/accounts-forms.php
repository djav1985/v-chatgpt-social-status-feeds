<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/forms/accounts-forms.php
 * Description: ChatGPT API Status Generator
 */

// Check if the request method is POST, indicating form submission for account management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle account editing when the edit button is clicked
    if (isset($_POST["edit_account"])) {
        $accountOwner = $_SESSION["username"]; // Retrieve the username of the account owner from the session

        // Format and sanitize the account name
        $accountName = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST["account"]))));

        // Get and trim inputs from the form
        $prompt = trim($_POST["prompt"]);
        $platform = trim($_POST["platform"]);
        $hashtags = isset($_POST["hashtags"]) ? 1 : 0; // Set hashtags flag based on user input
        $link = trim($_POST["link"]);
        $imagePrompt = trim($_POST["image_prompt"]);

        // Simplified cron handling: convert the cron array into a comma-separated string or set it to 'null' if it's just 'null'
        $cron = (count($_POST["cron"]) === 1 && $_POST["cron"][0] === 'null') ? 'null' : implode(',', $_POST["cron"]);

        $days = (count($_POST["days"]) === 1 && $_POST["days"][0] === 'everyday') ? 'everyday' : implode(',', $_POST["days"]);

        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        }

        // Check if any of the required fields are empty or invalid
        if (empty($cron) || empty($days) || empty($platform) || !isset($hashtags)) {
            $_SESSION['messages'][] = "Error processing input.";
        }

        if (empty($prompt) || empty($imagePrompt)) {
            $_SESSION['messages'][] = "Missing required field(s).";
        }

        // Validate account name, link, prompt, image prompt, and cron settings with regex patterns
        if (!preg_match('/^[a-z0-9-]{8,18}$/', $accountName)) {
            $_SESSION['messages'][] = "Account name must be 8-18 characters long, alphanumeric and hyphens only.";
        }
        // Validate link (must be a valid URL)
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $_SESSION['messages'][] = "Link must be a valid URL starting with https://.";
        }

        // Check if any error messages have been added to the session
        if (!empty($_SESSION['messages'])) {
            header("Location: /accounts");
            exit;
        } else {
            $db = new Database(); // Create a new database object

            // Check if the account already exists in the database
            $db->query("SELECT * FROM accounts WHERE username = :accountOwner AND account = :accountName");
            $db->bind(':accountOwner', $accountOwner); // Bind the account owner's username
            $db->bind(':accountName', $accountName); // Bind the account name
            $accountExists = $db->single(); // Fetch the account record

            if ($accountExists) {
                // If the account exists, update its data
                $db->query("UPDATE accounts SET prompt = :prompt, platform = :platform, hashtags = :hashtags, link = :link, image_prompt = :imagePrompt, cron = :cron, days = :days WHERE username = :accountOwner AND account = :accountName");
            } else {
                // If the account does not exist, insert a new account record
                $db->query("INSERT INTO accounts (username, account, prompt, platform, hashtags, link, image_prompt, cron, days) VALUES (:accountOwner, :accountName, :prompt, :platform, :hashtags, :link, :imagePrompt, :cron, :days)");

                // Additional logic for new accounts: create a directory for storing images
                $acctImagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName;
                if (!file_exists($acctImagePath)) {
                    mkdir($acctImagePath, 0777, true); // Create the directory recursively with full permissions

                    // Create an index.php file in the new directory for security
                    $indexFilePath = $acctImagePath . '/index.php';
                    file_put_contents($indexFilePath, '<?php die(); ?>'); // Prevent direct access to the directory
                }
            }

            // Bind parameters for the account data
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $db->bind(':prompt', $prompt);
            $db->bind(':platform', $platform);
            $db->bind(':hashtags', $hashtags);
            $db->bind(':link', $link);
            $db->bind(':imagePrompt', $imagePrompt);
            $db->bind(':cron', $cron);
            $db->bind(':days', $days);
            $db->execute(); // Execute the query to save the account data

            $_SESSION['messages'][] = "Account has been created or modified."; // Success message
            header("Location: /accounts"); // Redirect back to the accounts page
            exit; // Terminate script execution
        }
    } elseif (isset($_POST["delete_account"])) {
        $accountName = trim($_POST["account"]); // Get and trim the account name from the form
        $accountOwner = $_SESSION["username"]; // Retrieve the username of the account owner from the session

        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
            header("Location: /accounts");
            exit;
        }

        $db = new Database(); // Create a new database object

        // Delete all statuses related to this account
        $db->query("DELETE FROM status_updates WHERE username = :accountOwner AND account = :accountName");
        $db->bind(':accountOwner', $accountOwner); // Bind the account owner's username
        $db->bind(':accountName', $accountName); // Bind the account name
        $db->execute(); // Execute the delete query for statuses

        // Now, delete the account from the accounts table
        $db->query("DELETE FROM accounts WHERE username = :accountOwner AND account = :accountName");
        $db->bind(':accountOwner', $accountOwner); // Bind the account owner's username
        $db->bind(':accountName', $accountName); // Bind the account name
        $db->execute(); // Execute the delete query for the account

        $_SESSION['messages'][] = "Account Deleted."; // Success message
        header("Location: /accounts"); // Redirect back to the accounts page
        exit; // Terminate script execution
    }
}
