<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: accounts.php
 * Description: AI Social Status Generator
 */



require 'partials/header.php';
?>

<main class="container">
    <div class="columns">
        <!-- Account Management Section -->
        <div class="account-left card column col-4 col-md-4 col-sm-12">
            <div class="account-header card-header">
                <h3 class="account-name card-title">Manage Accounts</h3>
            </div>
            <form class="form-group columns" action="/accounts" method="POST">
                <!-- Account name input field -->
                <label for="account">Account Name:</label>
                <input class="form-input" type="text" name="account" id="account" required>

                <!-- Platform selection dropdown -->
                <label for="platform">Platform:</label>
                <select class="form-select" name="platform" id="platform" required>
                    <option value="facebook">Facebook</option>
                    <option value="twitter">Twitter</option>
                    <option value="instagram">Instagram</option>
                    <option value="google-business">Google Business</option>
                </select>
                <!-- Prompt textarea -->
                <label for="add-prompt">Prompt:</label>
                <textarea class="form-input" rows="5" name="prompt" id="add-prompt" required>Create a compelling status update...</textarea>
                <!-- Link input field -->
                <label for="link">Link:</label>
                <input class="form-input" type="url" name="link" id="link" required value="https://domain.com">
                <!-- Days selection dropdown (multiple) -->
                <label for="days">Days:</label>
                <select class="form-select" name="days[]" id="days" multiple required>
                    <?php echo $daysOptions; ?>
                </select>
                <!-- Post schedule selection dropdown (multiple) -->
                <label for="cron">Post Schedule:</label>
                <select class="form-select" name="cron[]" id="cron" multiple required>
                    <?php echo $cronOptions; ?>
                </select>
                <!-- Hashtags inclusion dropdown -->
                <label for="hashtags">Include Hashtags:</label>
                <select class="form-select" name="hashtags" id="hashtags" required>
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
                <input type="hidden" name="csrf_token" value="<?php
 echo $_SESSION['csrf_token']; ?>">
                <button type="submit" class="btn btn-primary btn-lg" name="edit_account">Add/Update Account</button>
            </form>
        </div>
        <!-- Account List Section -->
        <div class="account-right card column col-8 col-md-8 col-sm-12">
            <div class="account-header card-header">
                <h3 class="account-name card-title">Account List</h3>
            </div>
            <div class="columns">
                <?php echo $accountList; ?>
            </div>
        </div>
</div>
    <section class="calendar-overview">
        <h3>Calendar Overview</h3>
        <?php echo $calendarOverview; ?>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const updateButtons = document.querySelectorAll('#update-button');
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const accountNameField = document.querySelector('#account');
                const promptField = document.querySelector('#add-prompt');
                const linkField = document.querySelector('#link');
                const hashtagsSelect = document.querySelector('#hashtags');
                const cronField = document.querySelector('#cron');
                const daysField = document.querySelector('#days');
                const platformSelect = document.querySelector('#platform');

                // Populate form fields with the selected account's data
                accountNameField.value = this.dataset.accountName;
                promptField.value = decodeURIComponent(this.dataset.prompt.replace(/\+/g, ' '));
                linkField.value = decodeURIComponent(this.dataset.link.replace(/\+/g, ' '));
                hashtagsSelect.value = this.dataset.hashtags;

                // Clear previous selections
                Array.from(cronField.options).forEach(option => {
                    option.selected = false;
                });
                Array.from(daysField.options).forEach(option => {
                    option.selected = false;
                });

                // Select the appropriate cron options
                const selectedCronValues = this.dataset.cron ? this.dataset.cron.split(',') : [];
                if (selectedCronValues.length === 0 || selectedCronValues.includes("off")) {
                    const offOption = cronField.querySelector('option[value="off"]');
                    offOption.selected = true;
                } else {
                    selectedCronValues.forEach(value => {
                        const option = cronField.querySelector(`option[value="${value}"]`);
                        if (option) {
                            option.selected = true;
                        }
                    });
                }

                // Select the appropriate days options
                const selectedDaysValues = this.dataset.days ? this.dataset.days.split(',') : [];
                if (selectedDaysValues.length === 0 || selectedDaysValues.includes("everyday")) {
                    const everydayOption = daysField.querySelector('option[value="everyday"]');
                    everydayOption.selected = true;
                } else {
                    selectedDaysValues.forEach(value => {
                        const option = daysField.querySelector(`option[value="${value}"]`);
                        if (option) {
                            option.selected = true;
                        }
                    });
                }

                platformSelect.value = this.dataset.platform;
                accountNameField.readOnly = true;
            });
        });

        const daysField = document.querySelector('#days');
        daysField.addEventListener('change', function() {
            const selectedOptions = Array.from(daysField.selectedOptions).map(option => option.value);

            // Ensure only 'everyday' or specific days are selected, not both
            if (selectedOptions.includes('everyday')) {
                Array.from(daysField.options).forEach(option => {
                    if (option.value !== 'everyday') {
                        option.selected = false;
                    }
                });
            } else if (selectedOptions.length > 0) {
                const everydayOption = daysField.querySelector('option[value="everyday"]');
                everydayOption.selected = false;
            }
        });
    });
</script>

<?php
 require 'partials/footer.php'; ?>
