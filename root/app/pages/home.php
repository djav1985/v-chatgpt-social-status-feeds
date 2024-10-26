<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/pages/home.php
 * Description: ChatGPT API Status Generator
 */
?>

<main class="container">
    <?php
    $accountOwner = $_SESSION['username'];
    $accounts = getAllUserAccts($accountOwner);

    if (empty($accounts)) {
        echo '<div id="no-account"><p>Please set up an account!</p></div>';
        return;
    }

    foreach ($accounts as $index => $account) {
        $accountName = $account->account;
        $acctInfo = getAcctInfo($accountOwner, $accountName);
        $statuses = getStatusInfo($accountOwner, $accountName);
        $feedUrl = htmlspecialchars("/feeds.php?user={$accountOwner}&acct={$accountName}");
        $isOpen = $index === 0 ? 'block' : 'none';
        $buttonText = $index === 0 ? '-' : '+';
    ?>
        <div class="status-container">
            <div class="status-header">
                <button class="collapse-button" onclick="toggleSection(this)">
                    <?= $buttonText ?>
                </button>
                <h3>Status Campaign: #<?= htmlspecialchars($accountName) ?></h3>
            </div>
            <div class="status-content" style="display: <?= $isOpen ?>;">
                <?php if (!empty($statuses)) : ?>
                    <ul>
                        <?php foreach ($statuses as $status) : ?>
                            <?php if (!empty($status->status)) : ?>
                                <li>
                                    <img src="<?= htmlspecialchars($status->status_image ? "images/{$accountOwner}/{$accountName}/{$status->status_image}" : 'assets/images/default.png') ?>" class="status-image">
                                    <p class="status-text">
                                        <?= htmlspecialchars($status->status) ?>
                                    </p>
                                    <strong class="status-info">
                                        <?= date('m/d/y g:ia', strtotime($status->created_at)) ?>
                                    </strong>
                                    <?php echo shareButton($status->status, $status->status_image, $accountOwner, $accountName, $status->id); ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div id="no-status">
                        <p>No statuses available.</p>
                    </div>
                <?php endif; ?>

                <div class="account-action-container">
                    <button class="view-feed-button blue-button" onclick="location.href='<?= $feedUrl ?>';">View Feed</button>
                    <form class="account-action-form" action="/home" method="POST">
                        <input type="hidden" name="account" value="<?= htmlspecialchars($accountName) ?>">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($accountOwner) ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="generate-status-button green-button" name="generate_status">Generate Status</button>
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
                    showToast('Text copied to clipboard');

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
                    console.error('Error:', error);
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
                    showToast('Text copied to clipboard');

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
                    console.error('Error sharing:', error);
                }
            });
        });
    });

    function toggleSection(button) {
        const statusContainer = button.closest('.status-container');
        const statusContent = statusContainer.querySelector('.status-content');
        const allStatusContents = document.querySelectorAll('.status-content');
        const allButtons = document.querySelectorAll('.collapse-button');

        allStatusContents.forEach((content) => {
            if (content !== statusContent) {
                content.style.display = 'none';
            }
        });

        allButtons.forEach((btn) => {
            if (btn !== button) {
                btn.textContent = '+';
            }
        });

        const isOpen = statusContent.style.display === 'block';
        statusContent.style.display = isOpen ? 'none' : 'block';
        button.textContent = isOpen ? '+' : '-';
    }

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
