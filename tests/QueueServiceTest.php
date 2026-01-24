<?php
// phpcs:ignoreFile
// SuppressWarnings(PHPMD.TooManyPublicMethods) - Test class requires comprehensive coverage

declare(strict_types=1);

namespace Tests;

use App\Services\QueueService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class QueueServiceTest extends TestCase
{
    private function invokePrivateMethod(QueueService $service, string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(QueueService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($service, ...$args);
    }

    private function getPrivateProperty(QueueService $service, string $property): mixed
    {
        $reflection = new ReflectionClass(QueueService::class);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($service);
    }

    public function testNormalizeHoursFiltersInvalidValues(): void
    {
        $service = new QueueService();

        $result = $this->invokePrivateMethod($service, 'normalizeHours', '0,6,12,18,24');

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertContains(0, $result);
        $this->assertContains(6, $result);
        $this->assertContains(12, $result);
        $this->assertContains(18, $result);
        $this->assertNotContains(24, $result); // 24 is invalid (max is 23)
    }

    public function testNormalizeHoursRemovesDuplicates(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'normalizeHours', '0,6,6,12,12,18');
        
        $this->assertCount(4, $result);
        $this->assertEquals([0, 6, 12, 18], $result);
    }

    public function testNormalizeHoursHandlesEmptyString(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'normalizeHours', '');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testNormalizeHoursIgnoresNonNumeric(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'normalizeHours', '0,abc,12,xyz,18');
        
        $this->assertCount(3, $result);
        $this->assertContains(0, $result);
        $this->assertContains(12, $result);
        $this->assertContains(18, $result);
    }

    public function testNormalizeHoursHandlesBoundaryValues(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'normalizeHours', '-1,0,23,24');
        
        $this->assertCount(2, $result);
        $this->assertContains(0, $result);
        $this->assertContains(23, $result);
        $this->assertNotContains(-1, $result);
        $this->assertNotContains(24, $result);
    }

    public function testIsScheduledDayAllowedReturnsTrueForEveryday(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'isScheduledDayAllowed', ['everyday'], time());
        
        $this->assertTrue($result);
    }

    public function testIsScheduledDayAllowedReturnsTrueForEmptyArray(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'isScheduledDayAllowed', [], time());
        
        $this->assertTrue($result);
    }

    public function testIsScheduledDayAllowedReturnsTrueForMatchingDay(): void
    {
        $service = new QueueService();
        
        // Get a specific day
        $timestamp = strtotime('2024-01-15 12:00:00'); // Monday
        $result = $this->invokePrivateMethod($service, 'isScheduledDayAllowed', ['monday', 'wednesday'], $timestamp);
        
        $this->assertTrue($result);
    }

    public function testIsScheduledDayAllowedReturnsFalseForNonMatchingDay(): void
    {
        $service = new QueueService();
        
        // Get a specific day
        $timestamp = strtotime('2024-01-15 12:00:00'); // Monday
        $result = $this->invokePrivateMethod($service, 'isScheduledDayAllowed', ['tuesday', 'wednesday'], $timestamp);
        
        $this->assertFalse($result);
    }

    public function testScheduledTimestampForHourReturnsTimestampOnSameDay(): void
    {
        $service = new QueueService();
        
        // Reference time: Jan 15, 2024 14:30:00
        $reference = strtotime('2024-01-15 14:30:00');
        
        // Schedule for hour 16 (4pm)
        $result = $this->invokePrivateMethod($service, 'scheduledTimestampForHour', 16, $reference);
        
        // Should be same day at 16:00:00
        $this->assertSame('2024-01-15', date('Y-m-d', $result));
        $this->assertSame('16:00:00', date('H:i:s', $result));
    }

    public function testScheduledTimestampForHourHandlesMidnight(): void
    {
        $service = new QueueService();
        
        $reference = strtotime('2024-01-15 14:30:00');
        
        // Schedule for hour 0 (midnight)
        $result = $this->invokePrivateMethod($service, 'scheduledTimestampForHour', 0, $reference);
        
        // Should be same day at 00:00:00
        $this->assertSame('2024-01-15', date('Y-m-d', $result));
        $this->assertSame('00:00:00', date('H:i:s', $result));
    }

    public function testScheduledTimestampForHourHandlesEndOfDay(): void
    {
        $service = new QueueService();
        
        $reference = strtotime('2024-01-15 14:30:00');
        
        // Schedule for hour 23 (11pm)
        $result = $this->invokePrivateMethod($service, 'scheduledTimestampForHour', 23, $reference);
        
        // Should be same day at 23:00:00
        $this->assertSame('2024-01-15', date('Y-m-d', $result));
        $this->assertSame('23:00:00', date('H:i:s', $result));
    }

    public function testGenerateJobIdReturnsUuidFormat(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'generateJobId');

        $this->assertIsString($result);
        // UUID v4 format: 8-4-4-4-12 hex characters
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    public function testGenerateJobIdProducesUniqueIds(): void
    {
        $service = new QueueService();
        
        $id1 = $this->invokePrivateMethod($service, 'generateJobId');
        $id2 = $this->invokePrivateMethod($service, 'generateJobId');
        $id3 = $this->invokePrivateMethod($service, 'generateJobId');
        
        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($id1, $id3);
        $this->assertNotEquals($id2, $id3);
    }

    public function testFilterUnattemptedJobsReturnsAllWhenNoAttemptedIds(): void
    {
        $service = new QueueService();
        
        $jobs = [
            ['id' => '1', 'account' => 'test'],
            ['id' => '2', 'account' => 'test'],
            ['id' => '3', 'account' => 'test'],
        ];
        
        $result = $this->invokePrivateMethod($service, 'filterUnattemptedJobs', $jobs, []);
        
        $this->assertCount(3, $result);
        $this->assertSame($jobs, $result);
    }

    public function testFilterUnattemptedJobsFiltersAttemptedIds(): void
    {
        $service = new QueueService();
        
        $jobs = [
            ['id' => '1', 'account' => 'test'],
            ['id' => '2', 'account' => 'test'],
            ['id' => '3', 'account' => 'test'],
        ];
        
        $result = $this->invokePrivateMethod($service, 'filterUnattemptedJobs', $jobs, ['1', '3']);
        
        $this->assertCount(1, $result);
        $this->assertSame('2', $result[0]['id']);
    }

    public function testFilterUnattemptedJobsHandlesEmptyJobsList(): void
    {
        $service = new QueueService();
        
        $result = $this->invokePrivateMethod($service, 'filterUnattemptedJobs', [], ['1', '2']);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFilterUnattemptedJobsIgnoresJobsWithoutId(): void
    {
        $service = new QueueService();
        
        $jobs = [
            ['id' => '1', 'account' => 'test'],
            ['account' => 'test'], // Missing id
            ['id' => '2', 'account' => 'test'],
        ];
        
        // When attemptedIds has values, jobs without id or with empty id are filtered
        $result = $this->invokePrivateMethod($service, 'filterUnattemptedJobs', $jobs, ['3']);
        
        // Job without id should be filtered out (only jobs with id '1' and '2' remain)
        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('2', $result[1]['id']);
    }

    public function testJobTypeIsStoredInConstructor(): void
    {
        $service = new QueueService('run-queue');
        
        $jobType = $this->getPrivateProperty($service, 'jobType');
        
        $this->assertSame('run-queue', $jobType);
    }

    public function testJobTypeDefaultsToNull(): void
    {
        $service = new QueueService();
        
        $jobType = $this->getPrivateProperty($service, 'jobType');
        
        $this->assertNull($jobType);
    }
}
