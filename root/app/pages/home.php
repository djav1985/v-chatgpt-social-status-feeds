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
    $accountOwner = $_SESSION['username'];
    $accounts = getAllUserAccts($accountOwner);

    if (empty($accounts)) {
        echo '<div id="no-account"><p>Please set up an account!</p></div>';
        return;
    }

    foreach ($accounts as $account) {
        $accountName = $account->account;
        $acctInfo = getAcctInfo($accountOwner, $accountName);
        $statuses = getStatusInfo($accountOwner, $accountName);
        $feedUrl = htmlspecialchars("/feeds.php?user={$accountOwner}&acct={$accountName}");
    ?>
        <div class="status-container">
            <h3>Status Campaign: #<?= htmlspecialchars($accountName) ?></h3>
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
    <?php } ?>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.share-buttons .blue-button').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('data-text') ? 'copy' : 'download';
                if (action === 'copy') {
                    const text = this.getAttribute('data-text');
                    navigator.clipboard.writeText(text).then(() => alert('Text copied to clipboard!'));
                } else {
                    const url = this.getAttribute('data-url');
                    const filename = this.getAttribute('data-filename');
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
        });
    });
</script>
