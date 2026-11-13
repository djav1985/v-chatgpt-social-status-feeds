<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: header.php
 * Description: AI Social Status Generator
 */

?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre.min.css">
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-exp.min.css">
    <link rel="stylesheet" href="https://unpkg.com/spectre.css/dist/spectre-icons.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="assets/js/header-scripts.js"></script>
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <title>Dashboard</title>
</head>
<body>
<header class="columns">
    <div class="column col-6">
        <div class="logo">
            <a href="/home">
                <img src="/assets/images/logo.png" alt="Logo" class="img-responsive">
            </a>
        </div>
    </div>
    <div class="column col-6 text-right">
        <form action="/login" method="POST" class="form-inline">
            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token')); ?>">
            <button id="logout" class="btn btn-error" type="submit" name="logout">Logout</button>
        </form>
    </div>
</header>
<ul class="tab tab-block">
    <?php $currentPath = $requestPath ?? '/'; ?>
    <li class="tab-item <?php if ($currentPath === '/home') {
        echo 'active';
                        } ?>">
        <a href="/home">Statuses</a>
    </li>
    <li class="tab-item <?php if ($currentPath === '/accounts') {
        echo 'active';
                        } ?>">
        <a href="/accounts">Accts</a>
    </li>
    <?php if (App\Core\SessionManager::getInstance()->get('is_admin')) : ?>
        <li class="tab-item <?php if ($currentPath === '/users') {
            echo 'active';
                            } ?>">
            <a href="/users">Users</a>
        </li>
    <?php endif; ?>
    <li class="tab-item <?php if ($currentPath === '/info') {
        echo 'active';
                        } ?>">
        <a href="/info">My Info</a>
    </li>
    <?php
    $username = htmlspecialchars(App\Core\SessionManager::getInstance()->get('username'));
    $allFeedsPath = '/feeds/' . $username . '/all';
    ?>
    <li class="tab-item <?php if ($currentPath === $allFeedsPath) {
        echo 'active';
                        } ?>">
        <a href="<?php echo $allFeedsPath; ?>">Omni</a>
    </li>
</ul>
