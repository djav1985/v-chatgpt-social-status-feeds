<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: login.php
 * Description: AI Social Status Generator sign-in view.
 */

$csrfToken = (string) App\Core\SessionManager::getInstance()->get('csrf_token', '');
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>AI Status Admin Login</title>
    <link rel="stylesheet" href="/assets/lib/tabler/css/tabler.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/admin-base.css">
    <script defer src="/assets/lib/tabler/js/tabler.min.js"></script>
    <script defer src="/assets/js/header-scripts.js"></script>
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="card login-card">
            <div class="card-body">
                <a href="/" class="login-logo" aria-label="Vontainment home">
                    <img src="/assets/images/logo.png" alt="Vontainment">
                </a>
                <div class="page-pretitle text-center">AI Status Generator</div>
                <h1 class="login-title">Admin sign in</h1>
                <form method="post" action="/login">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input
                            class="form-control"
                            id="username"
                            type="text"
                            name="username"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input
                            class="form-control"
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
            </div>
        </section>
    </main>
    <?php require __DIR__ . '/layouts/flash-messages.php'; ?>
</body>
</html>
