<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Support\FeedControllerTestDouble;

if (!defined('DOMAIN')) {
    define('DOMAIN', 'https://example.test');
}

class FeedControllerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/Support/FeedControllerTestDouble.php';
    }

    public function testAllAccountsFeedEscapesHtmlCharacters(): void
    {
        FeedControllerTestDouble::reset();

        $ownerInput = ' owner & friend ';
        $accountNameInput = ' all ';
        $owner = trim($ownerInput);
        $account = 'Acme & Co';
        $statusContent = 'Fresh <b>updates</b> & news';

        FeedControllerTestDouble::$accountsByOwner = [
            $owner => [
                ['account' => $account],
            ],
        ];

        FeedControllerTestDouble::$linksByOwner = [
            $owner => [
                $account => 'https://example.test/profile?name=' . $account,
            ],
        ];

        FeedControllerTestDouble::$statusesByOwner = [
            $owner => [
                $account => [
                    [
                        'account' => $account,
                        'status' => $statusContent,
                        'created_at' => '2024-01-01 12:00:00',
                        'status_image' => 'launch & learn.png',
                    ],
                ],
            ],
        ];

        $output = FeedControllerTestDouble::renderFeed($accountNameInput, $ownerInput);

        $descriptionExpectation = '<description><![CDATA[Fresh &lt;b&gt;updates&lt;/b&gt; &amp; news]]></description>';
        $enclosureExpectation = '<enclosure url="https://example.test/images/owner &amp; friend/Acme &amp; Co/'
            . 'launch &amp; learn.png"';

        $this->assertStringContainsString('<title>owner &amp; friend status feed</title>', $output);
        $this->assertStringContainsString('<title>Acme &amp; Co</title>', $output);
        $this->assertStringContainsString('<category>Acme &amp; Co</category>', $output);
        $this->assertStringContainsString('<link>https://example.test/profile?name=Acme &amp; Co</link>', $output);
        $this->assertStringContainsString($descriptionExpectation, $output);
        $this->assertStringContainsString($enclosureExpectation, $output);

        $this->assertSame([
            ['owner' => $owner, 'account' => $account],
        ], FeedControllerTestDouble::$requestedAccountLinks);

        $this->assertSame([
            ['owner' => $owner, 'account' => $account],
        ], FeedControllerTestDouble::$requestedStatusLookups);
    }
}
