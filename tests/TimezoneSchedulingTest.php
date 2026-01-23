<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestableQueueService;

require_once __DIR__ . '/Support/TestableQueueService.php';

class TimezoneSchedulingTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    /**
     * Test that scheduling respects the configured timezone.
     * This test verifies the fix for the issue where jobs scheduled for 7am, 12pm, 2pm EST
     * were actually executing at 2am, 7am, 9am EST because the system was using UTC.
     */
    public function testSchedulingRespectsConfiguredTimezone(): void
    {
        // Set timezone to EST (same as DEFAULT_TIMEZONE in config.php)
        date_default_timezone_set('America/New_York');

        $service = new TestableQueueService();
        // Simulate midnight EST on Friday, January 23, 2026
        $service->fakeNow = strtotime('2026-01-23 00:00:00 EST');

        // Schedule jobs for 7am, 12pm (noon), 2pm
        $hours = [7, 12, 14];
        $scheduledTimestamps = [];

        foreach ($hours as $hour) {
            $timestamp = $service->callScheduledTimestampForHour($hour, $service->fakeNow);
            $scheduledTimestamps[$hour] = $timestamp;
        }

        // Verify each scheduled time is correct in EST
        $this->assertEquals(
            '2026-01-23 07:00:00',
            date('Y-m-d H:i:s', $scheduledTimestamps[7]),
            'Hour 7 should schedule for 7:00 AM EST'
        );

        $this->assertEquals(
            '2026-01-23 12:00:00',
            date('Y-m-d H:i:s', $scheduledTimestamps[12]),
            'Hour 12 should schedule for 12:00 PM EST (noon)'
        );

        $this->assertEquals(
            '2026-01-23 14:00:00',
            date('Y-m-d H:i:s', $scheduledTimestamps[14]),
            'Hour 14 should schedule for 2:00 PM EST'
        );
    }

    /**
     * Test that the timezone issue doesn't occur when fillQueue runs.
     */
    public function testFillQueueUsesCorrectTimezone(): void
    {
        date_default_timezone_set('America/New_York');

        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2026-01-23 00:00:00 EST');
        $service->accounts = [
            (object) [
                'username' => 'testuser',
                'account' => 'web-design',
                'cron' => '07,12,14', // 7am, 12pm, 2pm
                'days' => 'friday',
            ],
        ];

        $service->fillQueue();

        // Should have created 3 jobs
        $this->assertCount(3, $service->storedJobs);

        // Verify the actual scheduled times
        $scheduledTimes = array_map(
            fn($job) => date('Y-m-d H:i:s', $job['scheduledAt']),
            $service->storedJobs
        );

        $this->assertContains('2026-01-23 07:00:00', $scheduledTimes, 'Should schedule 7:00 AM EST');
        $this->assertContains('2026-01-23 12:00:00', $scheduledTimes, 'Should schedule 12:00 PM EST');
        $this->assertContains('2026-01-23 14:00:00', $scheduledTimes, 'Should schedule 2:00 PM EST');
    }

    /**
     * Test that demonstrates the bug when timezone is not set correctly.
     * This test documents the WRONG behavior for comparison.
     */
    public function testDemonstrateTimezoneIssueWithUTC(): void
    {
        // Simulate what happens if system runs in UTC but user expects EST
        date_default_timezone_set('UTC');

        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2026-01-23 00:00:00 UTC');

        // User wants 7am, 12pm, 2pm EST but hours are stored as integers
        $hours = [7, 12, 14];
        $scheduledTimestamps = [];

        foreach ($hours as $hour) {
            $timestamp = $service->callScheduledTimestampForHour($hour, $service->fakeNow);
            $scheduledTimestamps[$hour] = $timestamp;

            // Convert to EST to see what time it would actually execute
            $oldTz = date_default_timezone_get();
            date_default_timezone_set('America/New_York');
            $estTime = date('Y-m-d H:i:s', $timestamp);
            date_default_timezone_set($oldTz);

            // Document the wrong times
            if ($hour === 7) {
                $this->assertEquals('2026-01-23 02:00:00', $estTime, 'Hour 7 UTC = 2am EST (WRONG!)');
            } elseif ($hour === 12) {
                $this->assertEquals('2026-01-23 07:00:00', $estTime, 'Hour 12 UTC = 7am EST (WRONG!)');
            } elseif ($hour === 14) {
                $this->assertEquals('2026-01-23 09:00:00', $estTime, 'Hour 14 UTC = 9am EST (WRONG!)');
            }
        }
    }
}
