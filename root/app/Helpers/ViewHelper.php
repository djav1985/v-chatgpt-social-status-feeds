<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ViewHelper.php
 * Description: Helper functions for views to ensure consistent output escaping
 */

if (!function_exists('esc')) {
    /**
     * Escape output for safe HTML rendering.
     * Wrapper for htmlspecialchars with ENT_QUOTES by default.
     *
     * @param mixed $value Value to escape
     * @param int $flags Optional flags for htmlspecialchars
     * @return string Escaped string
     */
    function esc($value, int $flags = ENT_QUOTES): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, $flags, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    /**
     * Escape output for HTML attributes.
     * Alias for esc() for clarity when used in attributes.
     *
     * @param mixed $value Value to escape
     * @return string Escaped string
     */
    function esc_attr($value): string
    {
        return esc($value, ENT_QUOTES);
    }
}
