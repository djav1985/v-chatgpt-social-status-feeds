<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusViewTest.php
 * Description: Status dashboard view behavior tests.
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

final class StatusViewTest extends TestCase
{
    public function testStatusActionMarkupIsRenderedByViewAndEscapesStatusData(): void
    {
        $statusText = 'A "quote" & <script>alert(1)</script>';
        $imageUrl = 'https://example.test/images/demo/site/photo.png?x="';
        $output = $this->renderView('home', [
            'accountOwner' => 'demo-owner',
            'csrfToken' => 'csrf-token',
            'accountsData' => [[
                'name' => 'Site & <News>',
                'feedUrl' => '/feeds/demo-owner/site',
                'statuses' => [[
                    'status' => $statusText,
                    'status_image' => 'photo.png',
                    'created_at' => '2026-11-12 12:00:00',
                    'id' => 42,
                    'image_url' => $imageUrl,
                ]],
            ]],
        ]);

        self::assertStringContainsString('Copy text and download image', $output);
        self::assertStringContainsString('Share status', $output);
        self::assertStringContainsString('name="delete_status"', $output);
        self::assertStringContainsString(
            'data-text="' . htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') . '"',
            $output
        );
        self::assertStringContainsString(
            'data-url="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '"',
            $output
        );
        self::assertStringNotContainsString($statusText, $output);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $view, array $data): string
    {
        ob_start();
        try {
            Response::view($view, $data)->send();
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
