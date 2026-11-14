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
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>AI Status Admin Login</title>
    <link rel="stylesheet" href="/assets/lib/tabler/css/tabler.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/forms.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <script defer src="/assets/lib/tabler/js/tabler.min.js"></script>
    <script defer src="/assets/js/header-scripts.js"></script>
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="card login-card" aria-labelledby="login-title">
            <div class="card-body">
                <img class="login-logo" src="/assets/images/logo.png" alt="Vontainment">
                <h1 id="login-title" class="h2 text-center">Admin sign in</h1>
                <form action="/login" method="post">
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
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" type="password" name="password"
                            autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-secondary w-100" type="submit">Log in</button>
                </form>
            </div>
        </section>
    </main>
    <?php require __DIR__ . '/layouts/flash-messages.php'; ?>
</body>
</html>
