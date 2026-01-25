<?php

declare(strict_types=1);

namespace Tests;

use App\Services\MaintenanceService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MaintenanceServiceTest extends TestCase
{
    private function getPrivateProperty(MaintenanceService $service, string $property): mixed
    {
        $reflection = new ReflectionClass(MaintenanceService::class);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($service);
    }

    public function testConstructorStoresJobType(): void
    {
        $service = new MaintenanceService('daily');

        $jobType = $this->getPrivateProperty($service, 'jobType');

        $this->assertSame('daily', $jobType);
    }

    public function testConstructorWithNullJobType(): void
    {
        $service = new MaintenanceService();

        $jobType = $this->getPrivateProperty($service, 'jobType');

        $this->assertNull($jobType);
    }

    public function testJobTypeCanBeMonthly(): void
    {
        $service = new MaintenanceService('monthly');

        $jobType = $this->getPrivateProperty($service, 'jobType');

        $this->assertSame('monthly', $jobType);
    }

    public function testWorkerLockDefaultsToNull(): void
    {
        $service = new MaintenanceService();

        $workerLock = $this->getPrivateProperty($service, 'workerLock');

        $this->assertNull($workerLock);
    }

    /**
     * Test that the service maintains singleton-like behavior for worker locks.
     */
    public function testMultipleInstancesCanHaveDifferentJobTypes(): void
    {
        $daily = new MaintenanceService('daily');
        $monthly = new MaintenanceService('monthly');

        $dailyJobType = $this->getPrivateProperty($daily, 'jobType');
        $monthlyJobType = $this->getPrivateProperty($monthly, 'jobType');

        $this->assertSame('daily', $dailyJobType);
        $this->assertSame('monthly', $monthlyJobType);
        $this->assertNotEquals($dailyJobType, $monthlyJobType);
    }

    /**
     * Test that worker lock is initially null for each instance.
     */
    public function testEachInstanceStartsWithNullWorkerLock(): void
    {
        $service1 = new MaintenanceService('daily');
        $service2 = new MaintenanceService('monthly');

        $lock1 = $this->getPrivateProperty($service1, 'workerLock');
        $lock2 = $this->getPrivateProperty($service2, 'workerLock');

        $this->assertNull($lock1);
        $this->assertNull($lock2);
    }

    /**
     * Test that different job types can be instantiated.
     */
    public function testVariousJobTypesCanBeInstantiated(): void
    {
        $jobTypes = ['daily', 'monthly', 'custom-job', null];

        foreach ($jobTypes as $jobType) {
            $service = new MaintenanceService($jobType);
            $storedJobType = $this->getPrivateProperty($service, 'jobType');

            $this->assertSame($jobType, $storedJobType);
        }
    }

    /**
     * Test that service can be constructed without any parameters.
     */
    public function testDefaultConstructorWorks(): void
    {
        $service = new MaintenanceService();

        $this->assertInstanceOf(MaintenanceService::class, $service);
    }

    /**
     * Test that job type is stored as expected.
     */
    public function testJobTypeStorageWithDifferentValues(): void
    {
        $testCases = [
            'daily' => 'daily',
            'monthly' => 'monthly',
            'test-job' => 'test-job',
            '' => '',
        ];

        foreach ($testCases as $input => $expected) {
            $service = new MaintenanceService($input);
            $jobType = $this->getPrivateProperty($service, 'jobType');

            $this->assertSame($expected, $jobType);
        }
    }
}
