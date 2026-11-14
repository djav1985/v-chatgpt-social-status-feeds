<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: AssetHelper.php
 * Description: Version local assets by content for reliable browser cache invalidation.
 */

namespace App\Helpers;

final class AssetHelper
{
    /**
     * Add a content hash to a local public asset URL.
     */
    public static function url(string $assetUrl): string
    {
        if (!str_starts_with($assetUrl, '/assets/')) {
            return $assetUrl;
        }

        $assetPath = dirname(__DIR__, 2) . '/public' . $assetUrl;
        if (!is_file($assetPath)) {
            return $assetUrl;
        }

        $hash = hash_file('sha256', $assetPath);
        if ($hash === false) {
            return $assetUrl;
        }

        return $assetUrl . '?v=' . substr($hash, 0, 12);
    }
}
