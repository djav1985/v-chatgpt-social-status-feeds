<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/pages/accounts.php
 * Description: ChatGPT API Status Generator
 */
?>

<main class="flex-container">
    <section id="left-col">
        <h3>Add/Update New Account</h3>
        <!-- Form for adding/updating account information -->
        <form class="edit-account-form" action="/accounts" method="POST">
            <!-- Input field for account name -->
            <label for="account">Account Name:</label>
            <input type="text" name="account" id="account" required>

            <!-- Dropdown to select the social media platform -->
            <label for="platform">Platform:</label>
            <select name="platform" id="platform" required>
                <option value="facebook">Facebook</option>
                <option value="twitter">Twitter</option>
                <option value="instagram">Instagram</option>
            </select>

            <!-- Textarea for entering a status update prompt -->
            <label for="add-prompt">Prompt:</label>
            <textarea name="prompt" id="add-prompt" required>Create a compelling status update...</textarea>

            <!-- Input field for adding a link -->
            <label for="link">Link:</label>
            <input type="url" name="link" id="link" required value="https://domain.com">

            <!-- Textarea for providing image instructions -->
            <label for="image_prompt">Image Instructions:</label>
            <textarea name="image_prompt" id="image_prompt" required>Include instructions to...</textarea>

            <!-- Multi-select dropdown for choosing days to schedule posts -->
            <label for="days">Days:</label>
            <select name="days[]" id="days" multiple required>
                <?php echo generateDaysOptions(); ?>
            </select>

            <!-- Multi-select dropdown for setting post schedule times -->
            <label for="cron">Post Schedule:</label>
            <select name="cron[]" id="cron" multiple required>
                <?php echo generateCronOptions(); ?>
            </select>

            <!-- Dropdown for including hashtags -->
            <label for="hashtags">Include Hashtags:</label>
            <select name="hashtags" id="hashtags" required>
                <option value="0" selected>No</option>
                <option value="1">Yes</option>
            </select>

            <!-- Hidden input to include CSRF token for security -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Submit button to add or update the account -->
            <button type="submit" class="edit-account-button green-button" name="edit_account">Add/Update Account</button>
        </form>

        <!-- Display any error messages and account details -->
        <div id="error-msg"><?php echo display_and_clear_messages(); ?></div>
        <?php echo generateAccountDetails(); ?>
    </section>

    <section id="right-col">
        <?php echo generateAccountList(); ?>
    </section>
</main>

<script>
    // Wait for the DOM content to be fully loaded before executing code
    document.addEventListener('DOMContentLoaded', function() {

        // Select all elements with the ID 'update-button' and iterate over each button
        const updateButtons = document.querySelectorAll('#update-button');
        updateButtons.forEach(button => {

            // Add click event listener to each update button
            button.addEventListener('click', function() {

                // Retrieve input fields from the page using their IDs
                const accountNameField = document.querySelector('#account');
                const promptField = document.querySelector('#add-prompt');
                const linkField = document.querySelector('#link');
                const imagePromptField = document.querySelector('#image_prompt');
                const hashtagsSelect = document.querySelector('#hashtags');
                const cronField = document.querySelector('#cron');
                const daysField = document.querySelector('#days');
                const platformSelect = document.querySelector('#platform');

                // Populate input fields with data attributes from the clicked button
                accountNameField.value = this.dataset.accountName;
                promptField.value = decodeURIComponent(this.dataset.prompt.replace(/\+/g, ' '));
                linkField.value = decodeURIComponent(this.dataset.link.replace(/\+/g, ' '));
                imagePromptField.value = decodeURIComponent(this.dataset.image_prompt.replace(/\+/g, ' '));
                hashtagsSelect.value = this.dataset.hashtags;

                // Clear all selections in multiselect fields before setting new options
                Array.from(cronField.options).forEach(option => {
                    option.selected = false;
                });
                Array.from(daysField.options).forEach(option => {
                    option.selected = false;
                });

                // Set selected options for multi-select 'cron' field based on dataset values
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

                // Set selected options for multi-select 'days' field based on dataset values
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

                // Select the appropriate platform based on dataset values
                platformSelect.value = this.dataset.platform;

                // Make the account name field read-only to prevent changes during updates
                accountNameField.readOnly = true;
            });
        });

        // Logic to handle special behavior when 'Everyday' is selected/deselected
        const daysField = document.querySelector('#days');
        daysField.addEventListener('change', function() {
            const selectedOptions = Array.from(daysField.selectedOptions).map(option => option.value);

            // If 'Everyday' is selected, deselect all other options
            if (selectedOptions.includes('everyday')) {
                Array.from(daysField.options).forEach(option => {
                    if (option.value !== 'everyday') {
                        option.selected = false;
                    }
                });
            }
            // If other options are selected, ensure 'Everyday' is not selected
            else if (selectedOptions.length > 0) {
                const everydayOption = daysField.querySelector('option[value="everyday"]');
                everydayOption.selected = false;
            }
        });
    });
</script>
