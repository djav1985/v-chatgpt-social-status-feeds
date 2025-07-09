<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: home.php
 * Description: AI Social Status Generator
 */



require 'layouts/header.php';
?>

<main class="container">
    <?php
    $accountOwnerEsc = htmlspecialchars($accountOwner, ENT_QUOTES);

    if (empty($accountsData)) {
        echo '<div id="no-account" class="empty"><p class="empty-title">Please set up an account!</p></div>';
    } else {
        foreach ($accountsData as $index => $acct) :
            $accountName = htmlspecialchars($acct['name'], ENT_QUOTES);
            $acctInfo = $acct['info'];
            $statuses = $acct['statuses'];
            $feedUrl = htmlspecialchars($acct['feedUrl'], ENT_QUOTES);
            $isOpen = $index === 0 ? 'flex' : 'none';
            $buttonIcon = $index === 0 ? 'icon-arrow-up' : 'icon-arrow-right';
            $accountActionDisplay = $index === 0 ? 'flex' : 'none';
    ?>

        <div class="status-container card">
            <div class="status-header card-header">
                <button class="collapse-button" onclick="toggleSection(this)">
                    <i class="icon <?php
 echo $buttonIcon ?>"></i>
                </button>
                <h3 class="status-campaign card-title">Status Campaign: #<?php
 echo htmlspecialchars($accountName) ?></h3>
            </div>

            <?php
 if (!empty($statuses)) : ?>
                <div class="status-content columns" style="display: <?php
 echo $isOpen ?>;">
            <?php foreach ($statuses as $status) : ?>
                        <?php if (!empty($status['status'])) : ?>
                            <div class="status-wrapper column col-3 col-md-4 col-sm-6 col-xs-12">
                                <div class="status-item card">
                                    <img src="<?php echo htmlspecialchars($status['status_image'] ? "images/{$accountOwnerEsc}/{$accountName}/{$status['status_image']}" : 'assets/images/default.png'); ?>" class="status-image img-responsive" loading="lazy">
                                    <p class="status-text">
                                        <?php echo htmlspecialchars($status['status']); ?>
                                    </p>
                                    <div class="status-meta">
                                        <strong class="status-info">
                                            <?php echo date('m/d/y g:ia', strtotime($status['created_at'])); ?>
                                        </strong>
                                        <?php echo $status['share_button']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php
 else : ?>
                <div id="no-status" class="empty">
                    <p class="empty-title">No statuses available.</p>
                </div>
            <?php
 endif; ?>

            <div class="account-action-container" style="display: <?php
 echo $accountActionDisplay ?>;">
                <button class="view-feed-button btn btn-primary" onclick="location.href='<?php
 echo $feedUrl ?>';">View Feed</button>
                <form class="account-action-form" action="/home" method="POST">
                    <input type="hidden" name="account" value="<?php
 echo htmlspecialchars($accountName, ENT_QUOTES) ?>">
                    <input type="hidden" name="username" value="<?php echo $accountOwnerEsc ?>">
                    <input type="hidden" name="csrf_token" value="<?php
 echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                    <button type="submit" class="generate-status-button btn btn-success" name="generate_status">Generate Status</button>
                </form>
            </div>
        </div>
    <?php
        endforeach;
    }
    ?>
</main>

<script>
    /**
     * Toggles the visibility of the status content and account action container.
     * Ensures only one section is open at a time.
     * Saves the toggle state in localStorage.
     * @param {HTMLElement} button - The button that was clicked to toggle the section.
     */
    function toggleSection(button) {
        const statusContainer = button.closest('.status-container');
        if (!statusContainer) return;

        const statusContent = statusContainer.querySelector('.status-content') || statusContainer.querySelector('#no-status');
        const accountActionContainer = statusContainer.querySelector('.account-action-container');
        if (!statusContent || !accountActionContainer) return;

        const allStatusContents = document.querySelectorAll('.status-content, #no-status');
        const allAccountActionContainers = document.querySelectorAll('.account-action-container');
        const allButtons = document.querySelectorAll('.collapse-button');

        // Close all other sections
        allStatusContents.forEach(content => {
            if (content !== statusContent) content.style.display = 'none';
        });

        allAccountActionContainers.forEach(container => {
            if (container !== accountActionContainer) container.style.display = 'none';
        });

        allButtons.forEach(btn => {
            if (btn !== button) btn.querySelector('i').className = 'icon icon-arrow-right';
        });

        // Toggle the clicked section
        const isOpen = statusContent.style.display === 'flex';
        statusContent.style.display = isOpen ? 'none' : 'flex';
        accountActionContainer.style.display = isOpen ? 'none' : 'flex';
        button.querySelector('i').className = isOpen ? 'icon icon-arrow-right' : 'icon icon-arrow-up';

        // Save the state to localStorage (only one open at a time)
        const accountName = statusContainer.querySelector('.status-campaign').textContent.trim();
        localStorage.clear(); // Clear all previous entries
        if (!isOpen) {
            localStorage.setItem(accountName, true);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const allStatusContents = document.querySelectorAll('.status-content, #no-status');
        const allAccountActionContainers = document.querySelectorAll('.account-action-container');
        const allButtons = document.querySelectorAll('.collapse-button');

        allStatusContents.forEach((content, index) => {
            const statusContainer = content.closest('.status-container');
            const accountName = statusContainer.querySelector('.status-campaign').textContent.trim();
            const isOpen = localStorage.getItem(accountName) === 'true';

            content.style.display = isOpen ? 'flex' : 'none';
            allAccountActionContainers[index].style.display = isOpen ? 'flex' : 'none';
            allButtons[index].querySelector('i').className = isOpen ? 'icon icon-arrow-up' : 'icon icon-arrow-right';
        });

        document.querySelectorAll('.copy-button').forEach(button => {
            button.addEventListener('click', async () => {
                const text = button.getAttribute('data-text');
                const imageUrl = button.getAttribute('data-url');

                try {
                    // Copy text to clipboard
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(text);
                        showToast('Text copied to clipboard');
                    } else {
                        const tempTextarea = document.createElement('textarea');
                        tempTextarea.value = text;
                        document.body.appendChild(tempTextarea);
                        tempTextarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempTextarea);
                        showToast('Text copied to clipboard (fallback)');
                    }

                    // Download image
                    const response = await fetch(imageUrl, {
                        mode: 'cors'
                    });
                    if (!response.ok) throw new Error('Image download failed');

                    const blob = await response.blob();
                    const blobUrl = URL.createObjectURL(blob);

                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = 'image.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(blobUrl);

                    console.log('Image downloaded successfully');
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Error copying or downloading.');
                }
            });
        });

        document.querySelectorAll('.share-button').forEach(button => {
            button.addEventListener('click', async () => {
                const text = button.getAttribute('data-text');
                const imageUrl = button.getAttribute('data-url');

                try {
                    // Copy text to clipboard
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(text);
                        showToast('Text copied to clipboard');
                    } else {
                        const tempTextarea = document.createElement('textarea');
                        tempTextarea.value = text;
                        document.body.appendChild(tempTextarea);
                        tempTextarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempTextarea);
                        showToast('Text copied to clipboard (fallback)');
                    }

                    // Fetch image for sharing
                    const response = await fetch(imageUrl, {
                        mode: 'cors'
                    });
                    if (!response.ok) throw new Error('Image fetch failed');

                    const blob = await response.blob();
                    const file = new File([blob], 'image.png', {
                        type: blob.type
                    });

                    const shareData = {
                        text: text,
                        files: [file]
                    };

                    // Share content if supported
                    if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
                        await navigator.share(shareData);
                        console.log('Content shared successfully');
                    } else {
                        console.log('Sharing not supported.');
                        showToast('Sharing not supported on this device.');
                    }
                } catch (error) {
                    console.error('Error sharing:', error);
                    showToast('Error occurred while sharing.');
                }
            });
        });
    });
</script>

<?php
 require 'layouts/footer.php'; ?>
