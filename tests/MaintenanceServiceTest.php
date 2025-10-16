<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/TestableMaintenanceService.php';

use Tests\Support\TestableMaintenanceService;

final class MaintenanceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    public function testPurgeImagesHandlesMissingDirectory(): void
    {
        $service = new TestableMaintenanceService();
        $tempDir = sys_get_temp_dir() . '/maintenance-service-missing-' . uniqid('', true);
        $service->imageDirectoryOverride = $tempDir;

        $this->assertTrue($service->purgeImages());
        $this->assertDirectoryExists($tempDir);

        $iterator = new \FilesystemIterator($tempDir);
        $this->assertCount(0, iterator_to_array($iterator));

        $this->assertTrue(@rmdir($tempDir));
    }
}
