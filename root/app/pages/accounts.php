<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../app/pages/accounts.php
 * Description: ChatGPT API Status Generator
 */

?>

<main class="flex-container">
    <section id="left-col">
        <h3>Add/Update New Account</h3>
        <form class="edit-account-form" action="/accounts" method="POST">
            <label for="account">Account Name:</label>
            <input type="text" name="account" id="account" required>
            <label for="platform">Platform:</label>
            <select name="platform" id="platform" required>
                <option value="facebook">Facebook</option>
                <option value="twitter">Twitter</option>
                <option value="instagram">Instagram</option>
            </select>
            <label for="add-prompt">Prompt:</label>
            <textarea name="prompt" id="add-prompt" required>Create a compelling status update about...</textarea>
            <label for="link">Link:</label>
            <input type="text" name="link" id="link" required value="https://domain.com">
            <label for="image_prompt">Image Prompt:</label>
            <textarea name="image_prompt" id="image_prompt" required>Create an image of...</textarea> <!-- Changed input to textarea -->
            <label for="days">Days:</label> <!-- Added days label -->
            <select name="days[]" id="days" multiple required> <!-- Multi-select dropdown for days -->
                <option value="everyday" selected>Everyday</option>
                <option value="sunday">Sunday</option>
                <option value="monday">Monday</option>
                <option value="tuesday">Tuesday</option>
                <option value="wednesday">Wednesday</option>
                <option value="thursday">Thursday</option>
                <option value="friday">Friday</option>
                <option value="saturday">Saturday</option>
            </select>
            <label for="cron">Post Schedule:</label> <!-- Changed cron label to Post Schedule -->
            <select name="cron[]" id="cron" multiple> <!-- Multi-select dropdown for Post Schedule -->
                <option value="off" selected>Off</option> <!-- Added 'Off' option with empty value -->
                <?php
                for ($hour = 6; $hour <= 22; $hour++) {
                    $amPm = ($hour < 12) ? 'am' : 'pm';
                    $displayHour = ($hour <= 12) ? $hour : $hour - 12;
                    $displayTime = "{$displayHour} {$amPm}";
                    $value = ($hour < 10) ? "0{$hour}" : "{$hour}";
                    echo "<option value=\"{$value}\">{$displayTime}</option>";
                }
                ?>
            </select>
            <div class="hashtags">
                <label for="hashtags">Include Hashtags:</label>
                <input type="checkbox" name="hashtags" id="hashtags">
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit" class="edit-account-button green-button" name="edit_account">Add/Update Account</button>
        </form>
        <div id="error-msg"><?php echo display_and_clear_messages(); ?></div>
        <?php echo generateAccountDetails(); ?>
    </section>

    <section id="right-col">
        <?php
        $username = $_SESSION['username'];
        $accounts = getAllUserAccts($username);

        foreach ($accounts as $account) :
            $accountName = $account->account;
            $accountData = getAcctInfo($username, $accountName);

            $dataAttributes = "data-account-name=\"{$accountName}\" ";
            $dataAttributes .= "data-prompt=\"" . htmlspecialchars($accountData->prompt) . "\" ";
            $dataAttributes .= "data-link=\"" . htmlspecialchars($accountData->link) . "\" ";
            $dataAttributes .= "data-image_prompt=\"" . htmlspecialchars($accountData->image_prompt) . "\" ";
            $dataAttributes .= "data-hashtags=\"" . ($accountData->hashtags ? 'true' : 'false') . "\" ";
            $dataAttributes .= "data-cron=\"" . htmlspecialchars(implode(',', explode(',', $accountData->cron))) . "\" ";
            $dataAttributes .= "data-days=\"" . htmlspecialchars(implode(',', explode(',', $accountData->days))) . "\" ";
            $dataAttributes .= "data-platform=\"" . htmlspecialchars($accountData->platform) . "\""; // Ensure this is correctly added

        ?>

            <div class="item-box">
                <h3><?php echo htmlspecialchars($accountName); ?></h3>
                <button class="update-account-button green-button" id="update-button" <?php echo $dataAttributes; ?>>Update</button>
                <form class="delete-account-form" action="/accounts" method="POST">
                    <input type="hidden" name="account" value="<?php echo htmlspecialchars($accountName); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button class="delete-account-button red-button" name="delete_account">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
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
                const imagePromptField = document.querySelector('#image_prompt');
                const hashtagCheckbox = document.querySelector('#hashtags');
                const cronField = document.querySelector('#cron');
                const daysField = document.querySelector('#days');
                const platformSelect = document.querySelector('#platform');

                accountNameField.value = this.dataset.accountName;
                promptField.value = decodeURIComponent(this.dataset.prompt.replace(/\+/g, ' '));
                linkField.value = decodeURIComponent(this.dataset.link.replace(/\+/g, ' '));
                imagePromptField.value = decodeURIComponent(this.dataset.image_prompt.replace(/\+/g, ' '));
                hashtagCheckbox.checked = this.dataset.hashtags === 'true';

                // Clear all selections before setting new ones
                Array.from(cronField.options).forEach(option => {
                    option.selected = false;
                });
                Array.from(daysField.options).forEach(option => {
                    option.selected = false;
                });

                // Set selected options for multi-select cron and days fields
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

                // Set the account name field as readonly when updating
                accountNameField.readOnly = true;
            });
        });

        // Handle the logic for selecting/deselecting 'Everyday' option
        const daysField = document.querySelector('#days');
        daysField.addEventListener('change', function() {
            const selectedOptions = Array.from(daysField.selectedOptions).map(option => option.value);
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
