<?php
// phpcs:ignoreFile
// SuppressWarnings(PHPMD.TooManyPublicMethods) - Test class requires comprehensive coverage

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;
    private array $originalSession;
    private string|false $originalRemoteAddress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSession = $_SESSION ?? [];
        $this->originalRemoteAddress = $_SERVER['REMOTE_ADDR'] ?? false;
        $_SESSION = [];
        unset($_SERVER['REMOTE_ADDR']);
        $this->router = new Router();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
        if ($this->originalRemoteAddress === false) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddress;
        }
        parent::tearDown();
    }

    public function testRootRedirectsToHome(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testUnmatchedRouteReturnsNotFoundResponse(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/missing-route'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('404', $response->getView());
    }

    public function testUnsupportedMethodReturnsMethodNotAllowedResponse(): void
    {
        $response = $this->router->dispatch(new Request('POST', '/'));

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testUnauthenticatedRequestToProtectedRouteRedirectsToLogin(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/home'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testLoginRouteReturnsLoginView(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/login'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('login', $response->getView());
    }
}
