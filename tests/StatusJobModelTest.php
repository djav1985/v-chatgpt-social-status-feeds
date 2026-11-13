<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusJobModelTest.php
 * Description: PHPUnit tests for StatusJobModel.
 */

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
