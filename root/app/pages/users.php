<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/pages/users.php
 * Description: ChatGPT API Status Generator
 */
?>

<main class="flex-container">
    <section id="left-col">
        <h3>Add/Update User</h3>
        <form class="edit-user-form" action="/users" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <label for="total-accounts">Total Accounts:</label>
            <select name="total-accounts" id="total-accounts" required>
                <?php for ($i = 1; $i <= 10; $i++) : ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <label for="max-api-calls">Max API Calls:</label>
            <select name="max-api-calls" id="max-api-calls" required>
                <option value="0">Off</option>
                <option value="30">30</option>
                <option value="60">60</option>
                <option value="90">90</option>
                <option value="120">120</option>
                <option value="150">150</option>
                <option value="9999999999">Unlimited</option>
            </select>
            <label for="used-api-calls">Used API Calls:</label>
            <select name="used-api-calls" id="used-api-calls" required>
                <option value="0">0</option>
            </select>
            <label for="expires">Expires:</label>
            <input type="date" name="expires" id="expires" required>
            <label for="admin">Admin:</label>
            <select name="admin" id="admin" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button class="edit-user-button green-button" type="submit" name="edit_users">Add/Update User</button>
        </form>
        <div id="error-msg"><?php echo display_and_clear_messages(); ?></div>
    </section>
    <section id="right-col">
        <?php echo generateUserList(); ?>
    </section>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const updateButtons = document.querySelectorAll('#update-btn');
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const usernameField = document.querySelector('#username');
                const passwordField = document.querySelector('#password');
                const totalAccountsSelect = document.querySelector('#total-accounts');
                const maxApiCallsSelect = document.querySelector('#max-api-calls');
                const usedApiCallsSelect = document.querySelector('#used-api-calls');
                const expiresField = document.querySelector('#expires');
                const adminSelect = document.querySelector('#admin');
                // Set form fields from data attributes
                usernameField.value = this.dataset.username;
                passwordField.value = decodeURIComponent(this.dataset.password);
                totalAccountsSelect.value = this.dataset.totalAccounts;
                maxApiCallsSelect.value = this.dataset.maxApiCalls;
                usedApiCallsSelect.innerHTML =
                    `<option value="${this.dataset.usedApiCalls}">${this.dataset.usedApiCalls}</option><option value="0">0</option>`;
                expiresField.value = this.dataset.expires;
                adminSelect.value = this.dataset.admin;
                // Set the username field as readonly when updating
                usernameField.readOnly = true;
            });
        });
    });
</script>
