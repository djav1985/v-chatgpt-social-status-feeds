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



require 'partials/header.php';
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
            $panelId = 'status-panel-' . $index;
            $actionsId = 'account-actions-' . $index;
            $hasStatuses = !empty($statuses);
            $openDisplay = $hasStatuses ? 'flex' : 'block';
            $isInitiallyOpen = $index === 0;
            $panelDisplay = $isInitiallyOpen ? $openDisplay : 'none';
            $buttonIcon = $isInitiallyOpen ? 'icon-arrow-up' : 'icon-arrow-right';
            $accountActionDisplay = $isInitiallyOpen ? 'flex' : 'none';
    ?>

        <div class="status-container card">
            <div class="status-header card-header">
                <button
                    class="collapse-button"
                    type="button"
                    onclick="toggleSection(this)"
                    aria-expanded="<?php echo $isInitiallyOpen ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo $panelId; ?>"
                    data-actions="<?php echo $actionsId; ?>"
                    data-default-open="<?php echo $isInitiallyOpen ? 'true' : 'false'; ?>"
                    data-account-name="<?php echo htmlspecialchars($accountName, ENT_QUOTES); ?>"
                >
                    <i class="icon <?php
 echo $buttonIcon ?>"></i>
                    <span class="visually-hidden">Toggle status campaign #<?php echo htmlspecialchars($accountName); ?></span>
                </button>
                <h3 class="status-campaign card-title">Status Campaign: #<?php
 echo htmlspecialchars($accountName) ?></h3>
            </div>

            <?php
 if (!empty($statuses)) : ?>
                <div
                    id="<?php echo $panelId; ?>"
                    class="status-content columns"
                    style="display: <?php echo $panelDisplay; ?>;"
                    data-open-display="flex"
                    aria-hidden="<?php echo $isInitiallyOpen ? 'false' : 'true'; ?>"
                >
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
                <div
                    id="<?php echo $panelId; ?>"
                    class="status-content empty"
                    style="display: <?php echo $panelDisplay; ?>;"
                    data-open-display="block"
                    aria-hidden="<?php echo $isInitiallyOpen ? 'false' : 'true'; ?>"
                >
                    <p class="empty-title">No statuses available.</p>
                </div>
            <?php
 endif; ?>

            <div
                id="<?php echo $actionsId; ?>"
                class="account-action-container"
                style="display: <?php echo $accountActionDisplay; ?>;"
                data-open-display="flex"
                aria-hidden="<?php echo $isInitiallyOpen ? 'false' : 'true'; ?>"
            >
                <button class="view-feed-button btn btn-primary" onclick="location.href='<?php
 echo $feedUrl ?>';">View Feed</button>
                <form class="account-action-form" action="/home" method="POST">
                    <input type="hidden" name="account" value="<?php
 echo htmlspecialchars($accountName, ENT_QUOTES) ?>">
                    <input type="hidden" name="username" value="<?php echo $accountOwnerEsc ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token'), ENT_QUOTES) ?>">
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
    function setDisplayState(element, shouldShow) {
        if (!element) return;

        const openDisplay = element.dataset.openDisplay || 'block';
        element.style.display = shouldShow ? openDisplay : 'none';
        element.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
    }

    function toggleSection(button) {
        const statusContainer = button.closest('.status-container');
        if (!statusContainer) return;

        const panelId = button.getAttribute('aria-controls');
        const statusContent = document.getElementById(panelId);
        const accountActionContainer = document.getElementById(button.dataset.actions);
        if (!statusContent || !accountActionContainer) return;

        const isCurrentlyOpen = button.getAttribute('aria-expanded') === 'true';

        document.querySelectorAll('.collapse-button').forEach(btn => {
            const isTargetButton = btn === button;
            const shouldOpen = isTargetButton ? !isCurrentlyOpen : false;
            btn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = shouldOpen ? 'icon icon-arrow-up' : 'icon icon-arrow-right';
            }

            const content = document.getElementById(btn.getAttribute('aria-controls'));
            const actions = document.getElementById(btn.dataset.actions);
            setDisplayState(content, shouldOpen);
            setDisplayState(actions, shouldOpen);
        });

        const accountName = button.dataset.accountName;
        if (!isCurrentlyOpen) {
            localStorage.setItem('openAccount', accountName);
        } else {
            localStorage.removeItem('openAccount');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const storedAccount = localStorage.getItem('openAccount');
        const buttons = document.querySelectorAll('.collapse-button');

        buttons.forEach(button => {
            const accountName = button.dataset.accountName;
            const panel = document.getElementById(button.getAttribute('aria-controls'));
            const actions = document.getElementById(button.dataset.actions);
            const shouldOpen = storedAccount
                ? storedAccount === accountName
                : button.dataset.defaultOpen === 'true';

            button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

            const icon = button.querySelector('i');
            if (icon) {
                icon.className = shouldOpen ? 'icon icon-arrow-up' : 'icon icon-arrow-right';
            }

            setDisplayState(panel, shouldOpen);
            setDisplayState(actions, shouldOpen);
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
                    navigator.share && navigator.canShare && navigator.canShare(shareData)
                        ? (await navigator.share(shareData),
                            console.log('Content shared successfully'))
                        : (console.log('Sharing not supported.'),
                            showToast('Sharing not supported on this device.'));
                } catch (error) {
                    console.error('Error sharing:', error);
                    showToast('Error occurred while sharing.');
                }
            });
        });
    });
</script>

<?php
 require 'partials/footer.php'; ?>
