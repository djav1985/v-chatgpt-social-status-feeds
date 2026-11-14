<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: header.php
 * Description: Shared Tabler admin shell.
 */

$currentPath = $requestPath ?? '/';
$session = App\Core\SessionManager::getInstance();
$username = (string) $session->get('username', '');
$allFeedsPath = '/feeds/' . rawurlencode($username) . '/all';
$csrfToken = (string) $session->get('csrf_token', '');
$navigation = [
    '/home' => 'Statuses',
    '/accounts' => 'Accts',
];
if ($session->get('is_admin')) {
    $navigation['/users'] = 'Users';
}
$navigation['/info'] = 'My Info';
$navigation[$allFeedsPath] = 'Omni';
$activeSection = $navigation[$currentPath] ?? 'Menu';
$activeSectionEsc = htmlspecialchars($activeSection, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Status Admin</title>
    <link rel="stylesheet" href="/assets/lib/tabler/css/tabler.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <script defer src="/assets/lib/tabler/js/tabler.min.js"></script>
    <script defer src="/assets/js/header-scripts.js"></script>
</head>
<body>
<div class="page">
    <header class="navbar navbar-expand-md admin-header">
        <div class="container-xl">
            <a class="admin-logo" href="/home" aria-label="Status Admin home">
                <img src="/assets/images/logo.png" alt="Vontainment">
            </a>
            <div class="admin-header-actions">
                <form action="/login" method="post" class="m-0 desktop-logout">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <button id="logout" class="btn btn-logout" type="submit" name="logout">Logout</button>
                </form>
                <button
                    class="mobile-menu-toggle"
                    type="button"
                    data-menu-section="<?php echo $activeSectionEsc; ?>"
                    aria-label="Open <?php echo $activeSectionEsc; ?> navigation menu"
                    aria-expanded="false"
                    aria-controls="admin-nav-links"
                    hidden
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 6h16M4 12h16M4 18h16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </header>
    <div class="admin-nav-wrap">
        <div class="container-xl">
            <nav id="admin-nav-links" class="nav nav-tabs admin-nav" aria-label="Admin sections">
                <?php foreach ($navigation as $path => $label) : ?>
                    <?php $isActive = $currentPath === $path; ?>
                    <a
                        class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                        href="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $isActive ? 'aria-current="page"' : ''; ?>
                    >
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
                <form action="/login" method="post" class="mobile-nav-logout">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <button class="btn btn-logout" type="submit" name="logout">Logout</button>
                </form>
            </nav>
        </div>
    </div>
    <div class="page-wrapper">
