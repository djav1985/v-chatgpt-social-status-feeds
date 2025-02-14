<?php
/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: login.php
 * Description: ChatGPT API Status Generator login page.
 * License: MIT
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../lib/auth-lib.php';
// Instantiate the ErrorHandler to register handlers
new ErrorHandler();
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <!-- Meta tags for responsive design and SEO -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>AI Status Admin Login</title>
    <!-- External CSS for styling -->
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
</head>
<body>
    <div class="columns">
        <div class="column col-12 col-md-6 col-mx-auto" id="login-box">
            <!-- Logo for branding -->
            <img class="img-responsive" id="logo" src="assets/images/logo.png" alt="Logo">
            <!-- Login form -->
            <form class="form-group" method="post">
                <label for="username">Username:</label>
                <input class="form-input" id="username" type="text" name="username" autocomplete="username" required>

                <label for="password">Password:</label>
                <input class="form-input" id="password" type="password" name="password" autocomplete="current-password" required>

                <input class="btn btn-primary btn-lg" type="submit" value="Log In">
            </form>

            <!-- Display error messages if any -->
        </div>
    </div>
    <?php echo UtilityHandler::displayAndClearMessages(); ?>
</body>
</html>
