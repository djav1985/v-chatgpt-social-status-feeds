<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../app/pages/home.php
 * Description: ChatGPT API Status Generator
 */
?>

<main class="container">
    <?php
    // Retrieve the username of the logged-in user
    $accountOwner = $_SESSION['username'];

    // Fetch all accounts associated with the user
    $accounts = getAllUserAccts($accountOwner);

    // Check if there are no accounts set up
    if (empty($accounts)) {
        echo '<div id="no-account"><p>Please set up an account!</p></div>';
        return; // Exit the script if no accounts are found
    }

    // Iterate through each account to display its information
    foreach ($accounts as $index => $account) {
        $accountName = $account->account;
        $acctInfo = getAcctInfo($accountOwner, $accountName); // Get account information
        $statuses = getStatusInfo($accountOwner, $accountName); // Get status information for the account
        $feedUrl = htmlspecialchars("/feeds.php?user={$accountOwner}&acct={$accountName}"); // Create a feed URL
        $isOpen = $index === 0 ? 'block' : 'none'; // Only open the first status container by default
        $buttonText = $index === 0 ? '-' : '+'; // Set button text based on whether the section is open or closed
    ?>
        <div class="status-container">
            <div class="status-header">
                <button class="collapse-button" onclick="toggleSection(this)">
                    <?= $buttonText ?> <!-- Display the toggle button text -->
                </button>
                <h3>Status Campaign: #<?= htmlspecialchars($accountName) ?></h3> <!-- Display the account name -->
            </div>
            <div class="status-content" style="display: <?= $isOpen ?>;"> <!-- Status content visibility -->
                <?php if (!empty($statuses)) : ?> <!-- Check if there are statuses available -->
                    <ul>
                        <?php foreach ($statuses as $status) : ?> <!-- Iterate through each status -->
                            <?php if (!empty($status->status)) : ?> <!-- Check if the status is not empty -->
                                <li>
                                    <img src="<?= htmlspecialchars($status->status_image ? "images/{$accountOwner}/{$accountName}/{$status->status_image}" : 'assets/images/default.png') ?>" class="status-image"> <!-- Display status image -->
                                    <p class="status-text">
                                        <?= htmlspecialchars($status->status) ?> <!-- Display the status text -->
                                    </p>
                                    <strong class="status-info">
                                        <?= date('m/d/y g:ia', strtotime($status->created_at)) ?> <!-- Display the creation date of the status -->
                                    </strong>
                                    <?php echo shareButton($status->status, $status->status_image, $accountOwner, $accountName, $status->id); ?> <!-- Display share button -->
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div id="no-status">
                        <p>No statuses available.</p> <!-- Message when no statuses are found -->
                    </div>
                <?php endif; ?>

                <div class="account-action-container">
                    <button class="view-feed-button blue-button" onclick="location.href='<?= $feedUrl ?>';">View Feed</button> <!-- Button to view the feed -->
                    <form class="account-action-form" action="/home" method="POST">
                        <input type="hidden" name="account" value="<?= htmlspecialchars($accountName) ?>"> <!-- Hidden field for account name -->
                        <input type="hidden" name="username" value="<?= htmlspecialchars($accountOwner) ?>"> <!-- Hidden field for username -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- Hidden field for CSRF token -->
                        <button type="submit" class="generate-status-button green-button" name="generate_status">Generate Status</button> <!-- Button to generate a new status -->
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.combined-button').forEach(button => {
            button.addEventListener('click', async () => {
                const text = button.getAttribute('data-text');
                const imageUrl = button.getAttribute('data-url');

                try {
                    // Copy text to clipboard
                    await navigator.clipboard.writeText(text);
                    showToast('Text copied to clipboard'); // Show a toast message

                    // Fetch the image and download it
                    const response = await fetch(imageUrl);
                    const blob = await response.blob();
                    const file = new File([blob], 'image.png', {
                        type: blob.type
                    });

                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(file);
                    a.download = 'image.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);

                    console.log('Image downloaded');
                } catch (error) {
                    console.error('Error:', error); // Log any errors that occur
                }
            });
        });

        document.querySelectorAll('.share-button').forEach(button => {
            button.addEventListener('click', async () => {
                const text = button.getAttribute('data-text');
                const imageUrl = button.getAttribute('data-url');

                try {
                    // Copy text to clipboard
                    await navigator.clipboard.writeText(text);
                    showToast('Text copied to clipboard'); // Show a toast message

                    const response = await fetch(imageUrl);
                    const blob = await response.blob();
                    const file = new File([blob], 'image.png', {
                        type: blob.type
                    });

                    const shareData = {
                        text: text,
                        files: [file]
                    };

                    if (navigator.canShare && navigator.canShare(shareData)) {
                        await navigator.share(shareData);
                        console.log('Thanks for sharing!');
                    } else {
                        console.log('Your system doesn\'t support sharing these files.');
                    }
                } catch (error) {
                    console.error('Error sharing:', error); // Log any errors that occur
                }
            });
        });
    });

    // Function to toggle the visibility of status content sections
    function toggleSection(button) {
        const statusContainer = button.closest('.status-container');
        const statusContent = statusContainer.querySelector('.status-content');
        const allStatusContents = document.querySelectorAll('.status-content');
        const allButtons = document.querySelectorAll('.collapse-button');

        // Close all other status content sections
        allStatusContents.forEach((content) => {
            if (content !== statusContent) {
                content.style.display = 'none';
            }
        });

        // Reset all buttons except the clicked one
        allButtons.forEach((btn) => {
            if (btn !== button) {
                btn.textContent = '+';
            }
        });

        const isOpen = statusContent.style.display === 'block';
        statusContent.style.display = isOpen ? 'none' : 'block'; // Toggle the current section's visibility
        button.textContent = isOpen ? '+' : '-'; // Update button text based on the section state
    }

    // Function to show a toast message
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        setTimeout(() => {
            toast.classList.remove('show');
            document.body.removeChild(toast);
        }, 3000);
    }
</script>
