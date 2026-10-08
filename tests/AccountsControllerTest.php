<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

}
