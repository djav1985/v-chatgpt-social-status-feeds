<?php
/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: users.php
 * Description: AI Social Status Generator
 */
use App\Controllers\UsersController;

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: users.php
 * Description: AI Social Status Generator
 */

include 'layouts/header.php';
?>

<main class="container">
    <div class="columns">
        <!-- User Management Section -->
        <div class="account-left card column col-4 col-md-4 col-sm-12">
            <div class="account-header card-header">
                <h3 class="account-name card-title">Add/Update User</h3>
            </div>
            <form class="form-group columns" action="/users" method="POST">
                <!-- Username input field -->
                <label for="username">Username:</label>
                <input class="form-input" type="text" name="username" id="username" required>

                <!-- Password input field -->
                <label for="password">Password:</label>
                <input class="form-input" type="password" name="password" id="password">

                <!-- Total Accounts dropdown -->
                <label for="total-accounts">Total Accounts:</label>
                <select class="form-select" name="total-accounts" id="total-accounts" required>
                    <option value="3">3</option>
                    <option value="5">5</option>
                    <option value="10">10</option>
                </select>

                <!-- Max API Calls dropdown -->
                <label for="max-api-calls">Max API Calls:</label>
                <select class="form-select" name="max-api-calls" id="max-api-calls" required>
                    <option value="0">Off</option>
                    <option value="30">30</option>
                    <option value="90">90</option>
                    <option value="300">300</option>
                    <option value="9999999999">Unlimited</option>
                </select>

                <!-- Used API Calls dropdown -->
                <label for="used-api-calls">Used API Calls:</label>
                <select class="form-select" name="used-api-calls" id="used-api-calls" required>
                    <option value="0">0</option>
                </select>

                <!-- Expiry date input field -->
                <label for="expires">Expires:</label>
                <input class="form-input" type="date" name="expires" id="expires" required>

                <!-- Admin status dropdown -->
                <label for="admin">Admin:</label>
                <select class="form-select" name="admin" id="admin" required>
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>

                <!-- CSRF token for security -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button class="btn btn-primary btn-lg" type="submit" name="edit_users">Add/Update User</button>
            </form>
        </div>
        <!-- User List Section -->
        <div class="account-right card column col-8 col-md-8 col-sm-12">
            <div class="account-header card-header">
                <h3 class="account-name card-title">User List</h3>
            </div>
            <div class="columns">
                <?php echo UsersController::generateUserList(); ?>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const updateButtons = document.querySelectorAll('#update-btn');
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const usernameField = document.querySelector('#username');
                const totalAccountsSelect = document.querySelector('#total-accounts');
                const maxApiCallsSelect = document.querySelector('#max-api-calls');
                const usedApiCallsSelect = document.querySelector('#used-api-calls');
                const expiresField = document.querySelector('#expires');
                const adminSelect = document.querySelector('#admin');
                // Populate form fields with data attributes from the clicked button
                usernameField.value = this.dataset.username;
                totalAccountsSelect.value = this.dataset.totalAccounts;
                maxApiCallsSelect.value = this.dataset.maxApiCalls;
                usedApiCallsSelect.innerHTML =
                    `<option value="${this.dataset.usedApiCalls}">${this.dataset.usedApiCalls}</option><option value="0">0</option>`;
                expiresField.value = this.dataset.expires;
                adminSelect.value = this.dataset.admin;
                // Make username field readonly when updating
                usernameField.readOnly = true;
            });
        });
    });
</script>

<?php include 'layouts/footer.php'; ?>
