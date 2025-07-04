<?php


/**
 * Project: ChatGPT API
 * @category  Dashboard
 * @package   ChatGPTSocialStatusFeeds
 * @author    Vontainment <info@vontainment.com>
 * @copyright 2023-2025 Vontainment
 * @license   MIT https://opensource.org/licenses/MIT
 * @link      https://vontainment.com/
 * @version   2.0.0
 * @file      index.php
 * @desc      Main dashboard file for the ChatGPT API Status Generator.
 */

// Set secure session cookie parameters before starting the session
$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secureFlag,
    'samesite' => 'Lax'
]);
session_start();
session_regenerate_id(true); // Regenerate session ID to prevent session fixation attacks

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../lib/load-lib.php';
// Instantiate the ErrorHandler to register handlers
new ErrorHandler();
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <!-- Meta tags for responsive design and SEO -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- External CSS for styling -->
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <!-- External JS for additional functionality -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/assets/js/header-scripts.js"></script>
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />

    <title>Dashboard</title>
</head>

<body>
    <header class="columns">
        <div class="column col-6">
            <a href="/home">
                <img src="/assets/images/logo.png" alt="Logo" class="img-responsive">
            </a>
        </div>

        <div class="column col-6 text-right">
            <!-- Logout form with CSRF protection -->
            <form action="/login.php" method="POST" class="form-inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button id="logout" class="btn btn-error" type="submit" name="logout">Logout</button>
            </form>
        </div>
    </header>

    <!-- Navigation tabs -->
    <ul class="tab tab-block">
        <li class="tab-item <?php if ($_SERVER['REQUEST_URI'] === '/home') echo 'active'; ?>"><a href="/home">Statuses</a></li>
        <li class="tab-item <?php if ($_SERVER['REQUEST_URI'] === '/accounts') echo 'active'; ?>"><a href="/accounts">Accts</a></li>
        <?php
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        ?>
            <li class="tab-item <?php if ($_SERVER['REQUEST_URI'] === '/users') echo 'active'; ?>"><a href="/users">Users</a></li>
        <?php
        }
        ?>
        <li class="tab-item <?php if ($_SERVER['REQUEST_URI'] === '/info') echo 'active'; ?>"><a href="/info">My Info</a></li>

        <li class="tab-item <?php if ($_SERVER['REQUEST_URI'] === '/feeds/' . htmlspecialchars($_SESSION['username']) . '/all') echo 'active'; ?>"><a href="/feeds/<?php echo htmlspecialchars($_SESSION['username']); ?>/all">Omni</a></li>
    </ul>

    <?php
    // Include the page content based on the $pageOutput variable
    if (isset($pageOutput)) {
        require_once $pageOutput;
    }
    ?>

    <footer class="columns">
        <div class="column col-12 text-center">
            <p>&copy;
                <?php echo date("Y"); ?> <a href="https://vontainment.com">Vontainment.com</a> All Rights Reserved.
            </p>
        </div>
    </footer>

    <?php echo UtilityHandler::displayAndClearMessages(); ?>
</body>

</html>
