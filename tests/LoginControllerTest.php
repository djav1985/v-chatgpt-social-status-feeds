<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\LoginController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LoginControllerTest extends TestCase
{
    public function testValidateLoginInputUsesArrayPayload(): void
    {
        $class = new ReflectionClass(LoginController::class);
        $method = $class->getMethod('validateLoginInput');
        $method->setAccessible(true);

        $validErrors = $method->invoke(null, 'testuser', 'password123!');
        $this->assertSame([], $validErrors);

        $invalidErrors = $method->invoke(null, 'TestUser', '');
        $this->assertNotEmpty($invalidErrors);
        $this->assertContains('Password is required.', $invalidErrors);
    }
}
