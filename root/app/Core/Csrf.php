<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Csrf.php
 * Description: CSRF validation helper
 */

namespace App\Core;

class Csrf
{
    /**
     * Validates a CSRF token against the session token.
     *
     * @param string $token The token provided by the client.
     * @return bool True when the token matches the session token.
     */
    public static function validate(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
