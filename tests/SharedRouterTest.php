<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedRouterTest.php
 * Description: Shared PHPUnit tests for Router.
 */

// SuppressWarnings(PHPMD.TooManyPublicMethods) - Test class requires comprehensive coverage

declare(strict_types=1);

namespace Tests;

use Tests\Support\SharedRequestFactory;
use App\Core\Router;
use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class SharedRouterTest extends TestCase
{
    private Router $router;
    private array $originalSession;
    private array $originalServer;
    public function testRootRedirectsToHome(): void
    {
        $this->authenticate();
        $request = SharedRequestFactory::create(
            'GET',
            '/',
            [],
            [],
            ['HTTP_USER_AGENT' => 'phpunit-agent']
        );
        $response = $this->router->dispatch($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testUnmatchedRouteReturnsNotFoundResponse(): void
    {
        $this->authenticate();
        $request = SharedRequestFactory::create(
            'GET',
            '/missing-route',
            [],
            [],
            ['HTTP_USER_AGENT' => 'phpunit-agent']
        );
        $response = $this->router->dispatch($request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
        $this->assertSame('404', $response->getView());
    }

    public function testUnsupportedMethodReturnsMethodNotAllowedResponse(): void
    {
        $this->authenticate();
        $request = SharedRequestFactory::create(
            'POST',
            '/',
            [],
            ['csrf_token' => 'router-test-token'],
            ['HTTP_USER_AGENT' => 'phpunit-agent']
        );
        $response = $this->router->dispatch($request);
        $this->assertSame(405, $response->getStatusCode());
    }

    public function testUnauthenticatedRequestToProtectedRouteRedirectsToLogin(): void
    {
        $response = $this->router->dispatch(SharedRequestFactory::create('GET', '/home'));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testLoginRouteReturnsLoginView(): void
    {
        $response = $this->router->dispatch(SharedRequestFactory::create('GET', '/login'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('login', $response->getView());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSession = $_SESSION ?? [];
        $this->originalServer = $_SERVER;
        $_SESSION = [];
        unset($_SERVER['REMOTE_ADDR']);
        $this->router = new Router();
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    private function authenticate(): void
    {
        SessionManager::getInstance()->set('logged_in', true);
        SessionManager::getInstance()->set('user_agent', 'phpunit-agent');
        SessionManager::getInstance()->set('timeout', time());
        SessionManager::getInstance()->set('csrf_token', 'router-test-token');
    }
}
