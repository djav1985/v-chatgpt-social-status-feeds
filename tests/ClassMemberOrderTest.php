<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ClassMemberOrderTest.php
 * Description: PHPUnit tests for ClassMemberOrder.
 */

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
            '__construct', 'redirect', 'text', 'html', 'json', 'view', 'file',
            'getView', 'getViewData', 'getFile', 'withView', 'withViewData',
            'withFile', 'send', 'renderView',
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
