<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: login.php
 * Description: AI Social Status Generator
 */
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
                <input type="hidden" name="csrf_token" value="<?php
 echo $_SESSION['csrf_token']; ?>">
                <label for="username">Username:</label>
                <input class="form-input" id="username" type="text" name="username" autocomplete="username" required>

                <label for="password">Password:</label>
                <input class="form-input" id="password" type="password" name="password" autocomplete="current-password" required>

                <input class="btn btn-primary btn-lg" type="submit" value="Log In">
            </form>

            <!-- Display error messages if any -->
        </div>
    </div>
<?php
 echo App\Models\UtilityHandler::displayAndClearMessages(); ?>
</body>
</html>
