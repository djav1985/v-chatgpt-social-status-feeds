<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: EncryptionHelper.php
 * Description: Cryptographic utility methods.
 */

namespace App\Helpers;

class EncryptionHelper
{
    /**
     * Return cryptographically secure random bytes.
     *
     * @param int $length Number of bytes to generate.
     * @return string
     * @throws \InvalidArgumentException When $length is not positive.
     * @throws \RuntimeException When secure random bytes cannot be generated.
     */
    public static function bytes(int $length): string
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length must be greater than zero.');
        }

        if (!\function_exists('random_bytes')) {
            throw new \RuntimeException('random_bytes() is unavailable on this PHP runtime.');
        }

        try {
            /** @var string $bytes */
            $bytes = \call_user_func('random_bytes', $length);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to generate cryptographically secure random bytes.');
        }

        if (strlen($bytes) !== $length) {
            throw new \RuntimeException('Unable to generate cryptographically secure random bytes.');
        }

        return $bytes;
    }
}
