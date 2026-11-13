<?php

declare(strict_types=1);

namespace Tests;

use App\Core\ErrorManager;
use App\Core\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ClassMemberOrderTest extends TestCase
{
    public function testResponsePublicStaticMethodsPrecedePublicAndPrivateMethods(): void
    {
        $this->assertMethodOrder(Response::class, [
            'redirect', 'text', 'html', 'json', 'view', 'file',
            'getStatusCode', 'getReasonPhrase', 'send',
            'normalizeHeaderName', 'renderView',
        ]);
    }

    public function testErrorManagerMethodsFollowRequestedVisibilityOrder(): void
    {
        $this->assertMethodOrder(ErrorManager::class, [
            '__construct', 'getInstance', 'handle', 'log', 'logger',
            'handleError', 'handleException', 'handleShutdown', 'resolveLogFile',
        ]);
    }

/**
     * @param class-string $className
     * @param list<string> $expectedOrder
     */
    private function assertMethodOrder(string $className, array $expectedOrder): void
    {
        $reflection = new ReflectionClass($className);
        $positions = [];

        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $className) {
                $positions[$method->getName()] = $method->getStartLine();
            }
        }

        $actual = $expectedOrder;
        usort($actual, static fn (string $left, string $right): int => $positions[$left] <=> $positions[$right]);

        self::assertSame($expectedOrder, $actual);
    }
}
