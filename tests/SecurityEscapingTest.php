<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SecurityEscapingTest extends TestCase
{
    public function testAccountAttributeEscapingUsesEntQuotes(): void
    {
        $class = new ReflectionClass(\App\Controllers\AccountsController::class);
        $method = $class->getMethod('escapeAttribute');
        $method->setAccessible(true);

        $input = '"onmouseover="alert(1)" & <script>';
        $expected = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        $this->assertSame($expected, $method->invoke(null, $input));
    }

    public function testSystemMessageEscapingUsesEntQuotes(): void
    {
        $class = new ReflectionClass(\App\Controllers\InfoController::class);
        $method = $class->getMethod('escapeSystemMessage');
        $method->setAccessible(true);

        $input = "Welcome to \"System\" <b>Notice</b> & enjoy";
        $expected = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        $this->assertSame($expected, $method->invoke(null, $input));
    }
}
