<div class="column col-6 col-xl-6 col-md-12 col-sm-12">
    <div class="card account-list-card">
        <div class="card-header account-card">
            <div class="card-title h5">
                #<?php echo htmlspecialchars($accountName); ?>
            </div>
            <br>
            <p><strong>Prompt:</strong> <?php echo htmlspecialchars($accountData->prompt); ?></p>
            <p><strong>Days:</strong> <?php echo htmlspecialchars($daysStr); ?></p>
            <p><strong>Times:</strong> <?php echo htmlspecialchars($timesStr); ?></p>
            <p><strong>Link:</strong> <a href="<?php echo htmlspecialchars($accountData->link); ?>" target="_blank">
            <?php echo htmlspecialchars($accountData->link); ?></a></p>
        </div>
        <div class="card-body button-group">
            <button class="btn btn-primary" id="update-button" <?php echo $dataAttributes; ?>>Update</button>
            <form class="delete-account-form" action="/accounts" method="POST">
                <input type="hidden" name="account" value="<?php echo htmlspecialchars($accountName); ?>">
                <?php
                $csrfToken = htmlspecialchars(
                    App\Core\SessionManager::getInstance()->get('csrf_token'),
                    ENT_QUOTES
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
