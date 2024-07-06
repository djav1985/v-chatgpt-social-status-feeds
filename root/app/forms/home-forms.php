<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../app/forms/home-forms.php
 * Description: ChatGPT API Status Generator
 */

require_once __DIR__ . '/../../lib/status-lib.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["delete_status"])) {
        $accountName = trim($_POST["account"]);
        $accountOwner = trim($_POST["username"]);
        $statusId = (int) $_POST["id"];  // Use 'id' from POST, ensuring it matches your form input

        $db = new Database();
        // First, retrieve the image file name
        $db->query("SELECT status_image FROM status_updates WHERE id = :statusId AND account = :account AND username = :username");
        $db->bind(':statusId', $statusId);
        $db->bind(':account', $accountName);
        $db->bind(':username', $accountOwner);
        $status = $db->single();

        if ($status && $status->status_image) {
            // Delete the image file if it exists
            $imagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName . '/' . $status->status_image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Then delete the status
        $db->query("DELETE FROM status_updates WHERE id = :statusId AND account = :account AND username = :username");
        $db->bind(':statusId', $statusId);
        $db->bind(':account', $accountName);
        $db->bind(':username', $accountOwner);
        $db->execute();

        header("Location: /home");
        exit;
    } elseif (isset($_POST["generate_status"])) {
        $accountName = trim($_POST["account"]);
        $accountOwner = trim($_POST["username"]);

        // Check if user has available API calls
        $userInfo = getUserInfo($accountOwner);
        if ($userInfo && $userInfo->used_api_calls >= $userInfo->max_api_calls) {
            // Redirect with a message if API calls are exhausted
            $_SESSION['messages'][] = "Sorry, your available API calls have run out.";
            header("Location: /home");
            exit;
        }

        // Call the function to generate the status
        generateStatus($accountName, $accountOwner);

        // Increment used API calls
        $userInfo->used_api_calls += 1;

        // Update user's used API calls in the database
        $db = new Database();
        $sql = "UPDATE users SET used_api_calls = :used_api_calls WHERE username = :username";
        $db->query($sql);
        $db->bind(':used_api_calls', $userInfo->used_api_calls);
        $db->bind(':username', $accountOwner);
        $db->execute();

        // Redirect to avoid form resubmission
        header("Location: /home");
        exit;
    }
}
