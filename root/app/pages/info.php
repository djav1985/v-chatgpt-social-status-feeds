<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: info.php
 * Description: Allows users to change their password.
 * License: MIT
 */

require_once __DIR__ . '/../helpers/info-helper.php';
?>

<main class="container">
    <div class="columns">
        <!-- User Profile Section -->
        <div class="info-left card column col-8 col-md-8 col-sm-12">
            <div class="info-header card-header">
                <h3 class="info-name card-title">Your Profile</h3>
            </div>
            <form class="form-group columns" method="post" id="update-profile-form" <?php echo generateProfileDataAttributes($_SESSION['username']); ?>>
                <div class="column col-6 col-md-6 col-sm-12">
                    <!-- Who input field -->
                    <label for="who">Who:</label>
                    <input class="form-input" type="text" name="who" id="who" placeholder="We are... (name)" maxlength="50" required>
                </div>
                <div class="column col-6 col-md-6 col-sm-12">
                    <!-- Where input field -->
                    <label for="where">Where:</label>
                    <input class="form-input" type="text" name="where" id="where" placeholder="We are located in... (florida)" maxlength="50" required>
                </div>
                <!-- What input field -->
                <label for="what">What:</label>
                <textarea class="form-input" rows="10" name="what" id="what" placeholder="We... (build things)" maxlength="1024" required></textarea>
                <!-- Goal input field -->
                <label for="goal">Goal:</label>
                <textarea class="form-input" rows="10" name="goal" id="goal" placeholder="Our goal is... (drive more sales)" maxlength="500" required></textarea>
                <input name="csrf_token" type="hidden" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input name="accountOwner" type="hidden" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                <input class="btn btn-primary btn-lg" type="submit" name="update_profile">
            </form>
        </div>
        <!-- Change Password Section -->
        <div class="info-right card column col-4 col-md-4 col-sm-12">
            <div class="info-header card-header">
                <h3 class="info-name card-title">Change Password</h3>
            </div>
            <form class="form-group" method="post">
                <!-- Username input field (readonly) -->
                <label for="username">Username:</label>
                <input class="form-input" type="text" name="username" id="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly required>
                <!-- New password input field -->
                <label for="password">New Password:</label>
                <input class="form-input" type="password" name="password" id="password" required>
                <!-- Confirm new password input field -->
                <label for="password2">Confirm New Password:</label>
                <input class="form-input" type="password" name="password2" id="password2" required>
                <!-- CSRF token for security -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="submit" class="btn btn-primary btn-lg" name="change_password">
            </form>
            <iframe style="margin: auto;" width="100%" height="340px" src="https://www.youtube.com/embed/Hj-XiPXyqCg?si=TPG2nQ_u1Iz3y_T1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        </div>
    </div>
    <!-- System Message Section -->
    <div class="columns">
        <div class="column col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Message</h3>
                </div>
                <div class="card-body">
                    <p><?php echo buildSystemMessage($_SESSION['username']); ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('#update-profile-form');
        if (form) {
            const who = form.dataset.who;
            const where = form.dataset.where;
            const what = form.dataset.what;
            const goal = form.dataset.goal;

            if (who) document.querySelector('#who').value = who;
            if (where) document.querySelector('#where').value = where;
            if (what) document.querySelector('#what').value = what;
            if (goal) document.querySelector('#goal').value = goal;
        }
    });
</script>
