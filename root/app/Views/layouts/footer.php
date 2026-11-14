<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: footer.php
 * Description: Shared Tabler admin footer.
 */

?>
    </div>
    <footer class="admin-footer">
        <div class="container-xl">
            <p>
                &copy; <?php echo date('Y'); ?>
                <a href="https://vontainment.com">Vontainment.com</a>
                All Rights Reserved | Current Time:
                <?php echo htmlspecialchars(date('g:i:s a T'), ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </footer>
    <?php require __DIR__ . '/flash-messages.php'; ?>
</div>
</body>
</html>
