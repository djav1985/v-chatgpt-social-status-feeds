<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../app/forms/accounts-forms.php
 * Description: ChatGPT API Status Generator
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["edit_account"])) {
        $accountOwner = $_SESSION["username"];
        $accountName = strtolower(str_replace(' ', '-', trim($_POST["account"])));
        $prompt = trim($_POST["prompt"]);
        $platform = trim($_POST["platform"]);
        $hashtags = isset($_POST["hashtags"]) ? 1 : 0;
        $link = trim($_POST["link"]);
        $imagePrompt = trim($_POST["image_prompt"]);

        // Check if 'cron' field is submitted and process it accordingly
        if (isset($_POST["cron"]) && in_array("off", $_POST["cron"], true)) {
            $cron = null; // "Off" is selected, set cron to null
        } elseif (!empty($_POST["cron"])) {
            $cron = implode(',', $_POST["cron"]); // Concatenate all selected times
        } else {
            $cron = null; // Handle cases where no cron time is selected
        }

        // Process the 'days' field
        $days = isset($_POST["days"]) ? implode(',', $_POST["days"]) : 'everyday';

        // Validate account name, link, and cron
        if (!preg_match('/^[a-z0-9-]{8,18}$/', $accountName)) {
            $_SESSION['messages'][] = "Account name must be 8-18 characters long, alphanumeric and hyphens only.";
        } elseif (!preg_match('/^https:\/\/[\w.-]+(\/[\w.-]*)*\/?$/', $link)) {
            $_SESSION['messages'][] = "Link must be a valid URL starting with https://";
        } elseif ($cron === null && !in_array("off", $_POST["cron"], true)) {
            $_SESSION['messages'][] = "Please select at least one cron value or set it to 'Off'.";
        } elseif (!empty($accountName) && !empty($prompt) && !empty($platform) && !empty($link) && !empty($imagePrompt) && ($cron !== null || in_array("off", $_POST["cron"], true))) {
            $db = new Database();

            // Check if the account exists
            $db->query("SELECT * FROM accounts WHERE username = :accountOwner AND account = :accountName");
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $accountExists = $db->single();

            if ($accountExists) {
                // Update account data
                $db->query("UPDATE accounts SET prompt = :prompt, platform = :platform, hashtags = :hashtags, link = :link, image_prompt = :imagePrompt, cron = :cron, days = :days WHERE username = :accountOwner AND account = :accountName");
            } else {
                // Insert new account data
                $db->query("INSERT INTO accounts (username, account, prompt, platform, hashtags, link, image_prompt, cron, days) VALUES (:accountOwner, :accountName, :prompt, :platform, :hashtags, :link, :imagePrompt, :cron, :days)");
                // Additional logic for new accounts
                $acctImagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName;
                if (!file_exists($acctImagePath)) {
                    mkdir($acctImagePath, 0777, true); // Create the directory recursively

                    // Create index.php in the new directory
                    $indexFilePath = $acctImagePath . '/index.php';
                    file_put_contents($indexFilePath, '<?php die(); ?>');
                }
            }
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $db->bind(':prompt', $prompt);
            $db->bind(':platform', $platform);
            $db->bind(':hashtags', $hashtags);
            $db->bind(':link', $link);
            $db->bind(':imagePrompt', $imagePrompt);
            $db->bind(':cron', $cron);
            $db->bind(':days', $days);
            $db->execute();

            $_SESSION['messages'][] = "Account has been created or modified";
            header("Location: /accounts");
            exit;
        } else {
            $_SESSION['messages'][] = "A field is missing or has incorrect data. Please try again.";
            header("Location: /accounts");
            exit;
        }
    } elseif (isset($_POST["delete_account"])) {
        $accountName = trim($_POST["account"]);
        $accountOwner = $_SESSION["username"];

        $db = new Database();

        // Delete all statuses related to this account
        $db->query("DELETE FROM status_updates WHERE username = :accountOwner AND account = :accountName");
        $db->bind(':accountOwner', $accountOwner);
        $db->bind(':accountName', $accountName);
        $db->execute();

        // Now, delete the account from the accounts table
        $db->query("DELETE FROM accounts WHERE username = :accountOwner AND account = :accountName");
        $db->bind(':accountOwner', $accountOwner);
        $db->bind(':accountName', $accountName);
        $db->execute();

        $_SESSION['messages'][] = "Account Deleted";
        header("Location: /accounts");
        exit;
    }
}
