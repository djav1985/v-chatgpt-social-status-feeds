<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class DocumentationSchemaTest extends TestCase
{
    public function testQueueTableDocsIncludeProcessingColumn(): void
    {
        $readme = file_get_contents(__DIR__ . '/../README.md');

        $this->assertNotFalse($readme);
        $this->assertStringContainsString('processing BOOLEAN NOT NULL DEFAULT FALSE', $readme);
    }

    public function testQueueTableSchemaIncludesProcessingColumn(): void
    {
        $installSql = file_get_contents(__DIR__ . '/../root/install.sql');
        $upgradeSql = file_get_contents(__DIR__ . '/../root/upgrade.sql');

        $this->assertNotFalse($installSql);
        $this->assertNotFalse($upgradeSql);
        $this->assertStringContainsString('processing BOOLEAN NOT NULL DEFAULT FALSE', $installSql);
        $this->assertStringContainsString('processing BOOLEAN NOT NULL DEFAULT FALSE', $upgradeSql);
    }
}
