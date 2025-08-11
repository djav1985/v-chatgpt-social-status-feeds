<div class="column col-6 col-xl-12 col-md-12 col-sm-12">
    <div class="card account-list-card">
        <div class="card-header account-card">
            <div class="card-title h5"><?php echo htmlspecialchars($user->username); ?></div>
            <br>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user->email); ?></p>
            <p><strong>Max API Calls:</strong> <?php echo htmlspecialchars($user->max_api_calls); ?></p>
            <p><strong>Used API Calls:</strong> <?php echo htmlspecialchars($user->used_api_calls); ?></p>
            <p><strong>Expires:</strong> <?php echo htmlspecialchars($user->expires); ?></p>
        </div>
        <div class="card-body button-group">
            <button class="btn btn-primary" id="update-btn" <?php echo $dataAttributes; ?>>Update</button>
            <form class="delete-user-form" action="/users" method="POST">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user->username); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token')); ?>">
                <button class="btn btn-error" name="delete_user">Delete</button>
            </form>
            <?php if ($user->username !== App\Core\SessionManager::getInstance()->get('username')) : ?>
                <form class="login-as-form" action="/users" method="POST">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user->username); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token')); ?>">
                    <button class="btn btn-primary" name="login_as">Login</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
