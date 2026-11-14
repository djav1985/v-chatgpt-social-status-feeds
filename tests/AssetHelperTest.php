<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class AssetHelperTest extends TestCase
{
    public function testStatusHeaderUsesContentVersionedScriptUrl(): void
    {
        $header = file_get_contents(dirname(__DIR__) . '/root/app/Views/partials/header.php');

        self::assertIsString($header);
        self::assertStringContainsString(
            "AssetHelper::url('/assets/js/header-scripts.js')",
            $header
        );
        self::assertStringNotContainsString(
            '<script src="assets/js/header-scripts.js"></script>',
            $header
        );
    }

    public function testUrlAddsContentHashToLocalAsset(): void
    {
        $assetPath = dirname(__DIR__) . '/root/public/assets/js/header-scripts.js';
        $hash = hash_file('sha256', $assetPath);

        self::assertIsString($hash);
        self::assertSame(
            '/assets/js/header-scripts.js?v=' . substr($hash, 0, 12),
            \App\Helpers\AssetHelper::url('/assets/js/header-scripts.js')
        );
        self::assertSame(
            'https://example.test/app.js',
            \App\Helpers\AssetHelper::url('https://example.test/app.js')
        );
    }
}
