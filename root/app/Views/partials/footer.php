<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: footer.php
 * Description: AI Social Status Generator
 */
?>

<footer class="columns">
    <div class="column col-12 text-center">
        <p>&copy; 2026
            <a href="https://vontainment.com">Vontainment.com</a>
            All Rights Reserved | Current Time: <?php echo date("g:i:s a T"); ?>
        </p>
    </div>
</footer>
<?php
    App\Helpers\MessageHelper::displayAndClearMessages(); ?>
</body>
</html>
