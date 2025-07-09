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
        <p>&copy; 
            <?php echo date("Y"); ?> 
            <a href="https://vontainment.com">Vontainment.com</a> 
            All Rights Reserved.
        </p>
    </div>
</footer>
<?php
  echo App\Core\ErrorHandler::displayAndClearMessages(); ?>
</body>
</html>
