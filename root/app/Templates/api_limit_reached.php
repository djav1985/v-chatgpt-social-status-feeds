<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: api_limit_reached.php
 * Description: Email template partial for API call limit notification.
 */
?>
<p>Hi <?= htmlspecialchars($username) ?>,</p>
<p>Your API call limit has been reached.</p>
