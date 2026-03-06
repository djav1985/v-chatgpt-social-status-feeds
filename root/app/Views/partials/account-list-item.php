<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: account-list-item.php
 * Description: View partial for rendering a single account list card.
 */
?>
<div class="column col-6 col-xl-6 col-md-12 col-sm-12">
    <div class="card account-list-card">
        <div class="card-header account-card">
            <div class="card-title h5">
                #<?php echo htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <br>
            <p><strong>Prompt:</strong>
                <?php echo htmlspecialchars($accountData->prompt, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p><strong>Days:</strong> <?php echo htmlspecialchars($daysStr, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Times:</strong> <?php echo htmlspecialchars($timesStr, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Link:</strong>
                <a href="<?php echo htmlspecialchars($accountData->link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo htmlspecialchars($accountData->link, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>
        </div>
        <div class="card-body button-group">
            <button class="btn btn-primary" id="update-button" <?php echo $dataAttributes; ?>>Update</button>
            <form class="delete-account-form" action="/accounts" method="POST">
                <input
                    type="hidden"
                    name="account"
                    value="<?php echo htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8'); ?>"
                >
                <?php
                $csrfToken = htmlspecialchars(
                    App\Core\SessionManager::getInstance()->get('csrf_token'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo $csrfToken; ?>"
                >
                <button class="btn btn-error" name="delete_account">Delete</button>
            </form>
        </div>
    </div>
</div>
