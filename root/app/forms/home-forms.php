<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: home-forms.php
 * Description: Handles form submissions for deleting and generating statuses.
 * License: MIT
 */

require_once __DIR__ . '/../../lib/status-lib.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token validity
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['messages'][] = "Invalid CSRF token. Please try again.";
        header("Location: /accounts");
        exit;
    }

    // Handle status deletion
    if (isset($_POST["delete_status"])) {
        $accountName = trim($_POST["account"]);
        $accountOwner = trim($_POST["username"]);
        $statusId = (int) $_POST["id"];

        // Retrieve status image path
        $statusImagePath = StatusHandler::getStatusImagePath($statusId, $accountName, $accountOwner);

        // Delete status image if it exists
        if ($statusImagePath) {
            $imagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName . '/' . $statusImagePath;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Delete status record from the database
        StatusHandler::deleteStatus($statusId, $accountName, $accountOwner);
        $_SESSION['messages'][] = "Successfully deleted status.";
        header("Location: /home");
        exit;
    } elseif (isset($_POST["generate_status"])) {
        // Handle status generation
        $accountName = trim($_POST["account"]);
        $accountOwner = trim($_POST["username"]);

        // Check if user has available API calls
        $userInfo = UserHandler::getUserInfo($accountOwner);
        if ($userInfo && $userInfo->used_api_calls >= $userInfo->max_api_calls) {
            $_SESSION['messages'][] = "Sorry, your available API calls have run out.";
            header("Location: /home");
            exit;
        }

        $statusResult = generateStatus($accountName, $accountOwner);
        if ($statusResult['success'] === false) {
            $_SESSION['messages'][] = "Failed to generate status: " . $statusResult['message'];
            header("Location: /home");
            exit;
        }

        $userInfo->used_api_calls += 1;
        UserHandler::updateUsedApiCalls($accountOwner, $userInfo->used_api_calls);
        $_SESSION['messages'][] = "Successfully generated status.";
        header("Location: /home");
        exit;
    }
}
