<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ConfigDefaultsTest.php
 * Description: Configuration fallback tests.
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ConfigDefaultsTest extends TestCase
{
    public function testDatabaseNameDefaultsToApplicationDatabaseWhenEnvironmentIsUnset(): void
    {
        $configPath = dirname(__DIR__) . '/root/config.php';
        $script = 'require ' . var_export($configPath, true) . '; echo DB_NAME;';
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-r', $script],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['OPENAI_API_KEY' => 'test-key']
        );

        self::assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, $errorOutput);
        self::assertSame('localhost3', $output);
    }
}
