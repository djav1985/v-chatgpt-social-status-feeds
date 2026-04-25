<?php

declare(strict_types=1);

namespace Tests;

use App\Models\StatusJobModel;
use PHPUnit\Framework\TestCase;

final class StatusJobModelTest extends TestCase
{
    public function testGenerateJobIdReturnsUuidFormat(): void
    {
        $jobId = StatusJobModel::generateJobId();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $jobId
        );
    }
}
