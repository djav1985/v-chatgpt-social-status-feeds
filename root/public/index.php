<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: index.php
 * Description: This is the main dashboard file for the ChatGPT API Status Generator. It serves as the entry point for the admin interface.
 */

// Start or resume a session
session_start();

// Include necessary configuration and function files
require_once '../config.php'; // Configuration settings
require_once '../db.php'; // Database functions
require_once '../lib/common-lib.php'; // Common utility functions
require_once '../lib/load-lib.php'; // Dynamic page loading library
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- jQuery included for AJAX and other JavaScript operations -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Scripts for handling header-related interactions -->
    <script src="/assets/js/header-scripts.js"></script>
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <!-- Main stylesheet for the dashboard -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    <!-- Additional stylesheets for specific pages and mobile responsiveness -->
    <link rel="stylesheet" href="/assets/css/pages.css">
    <link rel="stylesheet" href="/assets/css/mobile.css">

    <title>Dashboard</title>
</head>

<body>
    <header>
        <!-- Logo section with link to the home page -->
        <div class="logo">
            <a href="/home">
                <img src="/assets/images/logo.png" alt="Logo">
            </a>
        </div>

        <!-- Logout button form -->
        <div class="logout-button">
            <form action="/login.php" method="POST">
                <button class="orange-button" type="submit" name="logout">Logout</button>
            </form>
        </div>
    </header>

    <!-- Navigation tabs for different sections of the dashboard -->
    <div class="tab">
        <!-- Statuses tab -->
        <a href="/home"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/home') echo 'active'; ?>">Statuses</button></a>
        <!-- Accounts tab -->
        <a href="/accounts"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/accounts') echo 'active'; ?>">Accts</button></a>
        <!-- Conditional tabs based on user's role -->
        <?php
        // Check if user is logged in and retrieve their data
        if (isset($_SESSION['username'])) {
            // Use the common function to get user info
            $userInfo = getUserInfo($_SESSION['username']);

            // Display Users tab for admins
            if ($userInfo && isset($userInfo->admin)) {
                if ($userInfo->admin == 1) {
        ?>
                    <a href="/users"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/users') echo 'active'; ?>">Users</button></a>
                <?php
                    // Display My info tab for non-admin users
                } elseif ($userInfo->admin == 0) {
                ?>
                    <a href="/info"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/info') echo 'active'; ?>">My
                            Info</button></a>
        <?php
                }
            }
        }
        ?>
        <!-- Omni Feed tab -->
        <a href="/feeds.php?user=<?php echo $_SESSION['username']; ?>&acct=all"><button class="tablinks <?php if ($_SERVER['REQUEST_URI'] === '/feeds.php?user=' . $_SESSION['username'] . '&acct=all') echo 'active'; ?>">Omni</button></a>
    </div>

    <!-- Content area where different pages will be loaded based on the selected tab -->
    <?php
    // Include the content of the selected page
    if (isset($pageOutput)) {
        require_once $pageOutput;
    }
    ?>

    <!-- Footer section -->
    <footer>
        <p>&copy;
            <?php echo date("Y"); ?> <a href="https://vontainment.com">Vontainment.com</a> All Rights Reserved.
        </p>
    </footer>
    <script>
        window.difyChatbotConfig = {
            token: 'NMYzy6g6I3Ke3yX9',
            baseUrl: 'https://dify.hugev.xyz'
        }
    </script>
    <script src="https://dify.hugev.xyz/embed.min.js" id="NMYzy6g6I3Ke3yX9" defer>
    </script>
    <style>
        #dify-chatbot-bubble-button {
            background-color: #1C64F2 !important;
        }
    </style>
</body>

</html>
