<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/pages/info.php
 * Description: ChatGPT API Status Generator
*/
?>

<main class="flex-container">
    <section id="left-col">
        <h3>Change Password</h3>
        <form action="/info" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly required>
            <label for="password">New Password:</label>
            <input type="password" name="password" id="password" required>
            <label for="password2">Confirm New Password:</label>
            <input type="password" name="password2" id="password2" required>
            <button type="submit" class="green-button" name="change_password">Change Password</button>
        </form>
        <?php echo display_and_clear_messages(); ?>
    </section>
</main>
