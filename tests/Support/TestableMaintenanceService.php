<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\MaintenanceService;

final class TestableMaintenanceService extends MaintenanceService
{
    public ?string $imageDirectoryOverride = null;

    public bool $lockAvailable = true;
    public bool $lockReleased = false;

    protected function getImageDirectory(): string
    {
        if ($this->imageDirectoryOverride !== null) {
            return $this->imageDirectoryOverride;
        }

        return parent::getImageDirectory();
    }

    protected function claimWorkerLock(): bool
    {
        return $this->lockAvailable;
    }

    protected function releaseWorkerLock(): void
    {
        $this->lockReleased = true;
    }
}
