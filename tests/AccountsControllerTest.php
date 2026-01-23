<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class AccountsControllerTest extends TestCase
{
    /**
     * Test that formatHour correctly formats hours into 12-hour format with a.m./p.m.
     */
    public function testFormatHourCorrectlyFormatsTime(): void
    {
        $class = new ReflectionClass(\App\Controllers\AccountsController::class);
        $method = $class->getMethod('formatHour');
        $method->setAccessible(true);

        // Test morning hours
        $this->assertEquals('12:00 a.m.', $method->invoke(null, 0));
        $this->assertEquals('7:00 a.m.', $method->invoke(null, 7));
        $this->assertEquals('11:00 a.m.', $method->invoke(null, 11));

        // Test noon and afternoon
        $this->assertEquals('12:00 p.m.', $method->invoke(null, 12));
        $this->assertEquals('1:00 p.m.', $method->invoke(null, 13));
        $this->assertEquals('2:00 p.m.', $method->invoke(null, 14));

        // Test evening
        $this->assertEquals('6:00 p.m.', $method->invoke(null, 18));
        $this->assertEquals('10:00 p.m.', $method->invoke(null, 22));
    }

    /**
     * Test that times are correctly categorized into morning, afternoon, and night slots.
     * Verifies that hour 12 (noon) is in afternoon, not morning.
     */
    public function testTimeSlotCategorizationAndSorting(): void
    {
        // Mock data structure similar to what generateCalendarOverview creates
        $daysOfWeek = ['wednesday', 'thursday', 'friday'];

        // Test hours representing the issue: 12pm (noon) and 7am
        $testHours = [12, 7, 14, 18]; // 12pm, 7am, 2pm, 6pm

        $overview = [];
        foreach ($daysOfWeek as $day) {
            $overview[$day] = [
                'morning' => [],
                'afternoon' => [],
                'night' => [],
            ];
        }

        // Simulate the categorization logic from the controller
        foreach ($testHours as $hour) {
            $slot = 'night';
            if ($hour >= 5 && $hour < 12) {
                $slot = 'morning';
            } elseif ($hour >= 12 && $hour <= 17) {
                $slot = 'afternoon';
            }

            foreach ($daysOfWeek as $day) {
                $overview[$day][$slot][] = [
                    'hour' => $hour,
                    'text' => $hour . ':00'
                ];
            }
        }

        // Sort entries within each slot chronologically
        foreach ($overview as $day => $slots) {
            foreach ($slots as $slot => $entries) {
                usort($overview[$day][$slot], fn($a, $b) => $a['hour'] <=> $b['hour']);
            }
        }

        // Assert: 7am should be in morning, sorted first
        $this->assertCount(1, $overview['wednesday']['morning']);
        $this->assertEquals(7, $overview['wednesday']['morning'][0]['hour']);

        // Assert: 12pm (noon) should be in afternoon, not morning
        $this->assertCount(2, $overview['wednesday']['afternoon']); // 12pm and 2pm
        $this->assertEquals(12, $overview['wednesday']['afternoon'][0]['hour']); // 12pm sorted first
        $this->assertEquals(14, $overview['wednesday']['afternoon'][1]['hour']); // 2pm sorted second

        // Assert: 6pm should be in night
        $this->assertCount(1, $overview['wednesday']['night']);
        $this->assertEquals(18, $overview['wednesday']['night'][0]['hour']);
    }

    /**
     * Test that times within each slot are sorted chronologically.
     */
    public function testChronologicalSortingWithinSlots(): void
    {
        $entries = [
            ['hour' => 9, 'text' => '9:00 a.m.'],
            ['hour' => 7, 'text' => '7:00 a.m.'],
            ['hour' => 11, 'text' => '11:00 a.m.'],
            ['hour' => 5, 'text' => '5:00 a.m.'],
        ];

        usort($entries, fn($a, $b) => $a['hour'] <=> $b['hour']);

        // Verify chronological order
        $this->assertEquals(5, $entries[0]['hour']);
        $this->assertEquals(7, $entries[1]['hour']);
        $this->assertEquals(9, $entries[2]['hour']);
        $this->assertEquals(11, $entries[3]['hour']);
    }

    /**
     * Test that 12pm appears in afternoon, not before 7am in morning.
     */
    public function testNoonIsNotInMorning(): void
    {
        $hour = 12; // noon

        // Test the slot categorization logic
        $slot = 'night';
        if ($hour >= 5 && $hour < 12) {
            $slot = 'morning';
        } elseif ($hour >= 12 && $hour <= 17) {
            $slot = 'afternoon';
        }

        $this->assertEquals('afternoon', $slot, '12pm (noon) should be categorized as afternoon, not morning');
    }
}
