<?php

declare(strict_types=1);

namespace Tests;

use App\Services\StatusService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class StatusServiceTest extends TestCase
{
    private function invokePrivateMethod(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(StatusService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null, ...$args);
    }

    public function testNormalizeIdentifierPreservesSpecialCharacters(): void
    {
        $result = $this->invokePrivateMethod('normalizeIdentifier', "  Foo & Bar  ");

        $this->assertSame('Foo & Bar', $result);
    }

    public function testSanitizePathSegmentRemovesTraversalCharacters(): void
    {
        $result = $this->invokePrivateMethod('sanitizePathSegment', ' ../Foo\\Bar ');

        $this->assertSame('Bar', $result);
    }

    public function testSanitizePathSegmentRejectsEmptyValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->invokePrivateMethod('sanitizePathSegment', '   ');
    }

    public function testGenerateImageFilenameReturnsRandomPngName(): void
    {
        $result = $this->invokePrivateMethod('generateImageFilename');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\\.png$/', $result);
    }
}
