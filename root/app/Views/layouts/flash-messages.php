<?php

/** @var list<string> $flashMessages */
$flashMessages = App\Helpers\MessageHelper::getAndClearMessages();
?>
<div id="notification-stack" class="app-toast-stack" aria-live="polite" aria-relevant="additions text">
    <?php foreach ($flashMessages as $flashMessage) : ?>
        <div class="alert alert-info app-toast" role="status">
            <span><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></span>
            <button class="btn-close" type="button" data-toast-dismiss aria-label="Dismiss notification"></button>
        </div>
    <?php endforeach; ?>
</div>
