<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../app/forms/accounts-forms.php
 * Description: ChatGPT API Status Generator
 */

// Check if the request method is POST, indicating form submission for account management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle account editing when the edit button is clicked
    if (isset($_POST["edit_account"])) {
        $accountOwner = $_SESSION["username"]; // Retrieve the username of the account owner from the session

        // Format the account name to lowercase and replace spaces with hyphens
        $accountName = strtolower(str_replace(' ', '-', trim($_POST["account"])));

        // Get and trim inputs from the form
        $prompt = trim($_POST["prompt"]);
        $platform = trim($_POST["platform"]);
        $hashtags = isset($_POST["hashtags"]) ? 1 : 0; // Set hashtags flag based on user input
        $link = trim($_POST["link"]);
        $imagePrompt = trim($_POST["image_prompt"]);

        // Check if the 'cron' field is submitted and process it accordingly
        if (isset($_POST["cron"]) && in_array("off", $_POST["cron"], true)) {
            $cron = null; // If "Off" is selected, set cron to null
        } elseif (!empty($_POST["cron"])) {
            $cron = implode(',', $_POST["cron"]); // Concatenate all selected crontab times into a single string
        } else {
            $cron = null; // Set cron to null if no time is selected
        }

        // Process the 'days' field, defaulting to 'everyday' if no days are selected
        $days = isset($_POST["days"]) ? implode(',', $_POST["days"]) : 'everyday';

        // Validate account name, link, and cron settings with regex patterns
        if (!preg_match('/^[a-z0-9-]{8,18}$/', $accountName)) {
            $_SESSION['messages'][] = "Account name must be 8-18 characters long, alphanumeric and hyphens only."; // Error message for invalid account name
        } elseif (!preg_match('/^https:\/\/[\w.-]+(\/[\w.-]*)*\/?$/', $link)) {
            $_SESSION['messages'][] = "Link must be a valid URL starting with https://"; // Error message for invalid link
        } elseif ($cron === null && !in_array("off", $_POST["cron"], true)) {
            $_SESSION['messages'][] = "Please select at least one cron value or set it to 'Off'."; // Error for missing cron setting
        } elseif (!empty($accountName) && !empty($prompt) && !empty($platform) && !empty($link) && !empty($imagePrompt) && ($cron !== null || in_array("off", $_POST["cron"], true))) {
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

            $_SESSION['messages'][] = "Account has been created or modified"; // Success message
            header("Location: /accounts"); // Redirect back to the accounts page
            exit; // Terminate script execution
        } else {
            $_SESSION['messages'][] = "A field is missing or has incorrect data. Please try again."; // Error message for incomplete or incorrect data
            header("Location: /accounts"); // Redirect back to the accounts page
            exit; // Terminate script execution
        }
    } elseif (isset($_POST["delete_account"])) {
        $accountName = trim($_POST["account"]); // Get and trim the account name from the form
        $accountOwner = $_SESSION["username"]; // Retrieve the username of the account owner from the session

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

        $_SESSION['messages'][] = "Account Deleted"; // Success message
        header("Location: /accounts"); // Redirect back to the accounts page
        exit; // Terminate script execution
    }
}
