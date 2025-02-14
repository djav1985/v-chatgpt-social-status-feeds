<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: accounts-forms.php
 * Description: Handles form submissions for editing and deleting accounts.
 * License: MIT
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token validity
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        header("Location: /accounts");
        exit;
    }

    // Handle account edit form submission
    if (isset($_POST["edit_account"])) {
        $accountOwner = $_SESSION["username"];
        $accountName = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST["account"]))));
        $prompt = trim($_POST["prompt"]);
        $platform = trim($_POST["platform"]);
        $hashtags = isset($_POST["hashtags"]) ? (int)$_POST["hashtags"] : 0;
        $link = trim($_POST["link"]);
        $cron = (count($_POST["cron"]) === 1 && $_POST["cron"][0] === 'null') ? 'null' : implode(',', $_POST["cron"]);
        $days = isset($_POST["days"]) ? (count($_POST["days"]) === 1 && $_POST["days"][0] === 'everyday' ? 'everyday' : implode(',', $_POST["days"])) : '';

        // Validate form inputs
        if (empty($cron) || empty($days) || empty($platform) || !isset($hashtags)) {
            $_SESSION['messages'][] = "Error processing input.";
        }

        if (empty($prompt)) {
            $_SESSION['messages'][] = "Missing required field(s).";
        }

        if (!preg_match('/^[a-z0-9-]{8,18}$/', $accountName)) {
            $_SESSION['messages'][] = "Account name must be 8-18 characters long, alphanumeric and hyphens only.";
        }

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $_SESSION['messages'][] = "Link must be a valid URL starting with https://.";
        }

        // Redirect if there are validation errors
        if (!empty($_SESSION['messages'])) {
            header("Location: /accounts");
            exit;
        } else {
            // Check if account exists
            if (AccountHandler::accountExists($accountOwner, $accountName)) {
                AccountHandler::updateAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
            } else {
                AccountHandler::createAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
                // Create account image directory if it doesn't exist
                $acctImagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName;
                if (!file_exists($acctImagePath)) {
                    mkdir($acctImagePath, 0777, true);
                    $indexFilePath = $acctImagePath . '/index.php';
                    file_put_contents($indexFilePath, '<?php die(); ?>');
                }
            }

            $_SESSION['messages'][] = "Account has been created or modified.";
            header("Location: /accounts");
            exit;
        }
    } elseif (isset($_POST["delete_account"])) {
        // Handle account deletion
        $accountName = trim($_POST["account"]);
        $accountOwner = $_SESSION["username"];
        AccountHandler::deleteAccount($accountOwner, $accountName);

        $_SESSION['messages'][] = "Account Deleted.";
        header("Location: /accounts");
        exit;
    }
}
