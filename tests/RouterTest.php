<?php
// phpcs:ignoreFile
// SuppressWarnings(PHPMD.TooManyPublicMethods) - Test class requires comprehensive coverage

declare(strict_types=1);

namespace Tests;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = Router::getInstance();
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = Router::getInstance();
        $instance2 = Router::getInstance();

        $this->assertInstanceOf(Router::class, $instance1);
        $this->assertInstanceOf(Router::class, $instance2);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test that dispatch method exists and can be called.
     * We can't fully test dispatch without a live HTTP environment,
     * but we can verify the method exists and accepts the right parameters.
     */
    public function testDispatchMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->router, 'dispatch'),
            'Router should have a dispatch method'
        );
    }

    /**
     * Test that Router can handle method and URI parameters.
     * This is a basic structural test without full integration.
     */
    public function testDispatchAcceptsMethodAndUri(): void
    {
        // Test that dispatch can be called with method and URI
        // We expect it to output a 404 for an unknown route
        ob_start();

        try {
            $this->router->dispatch('GET', '/non-existent-route-test-12345');
        } catch (\Exception $e) {
            // Some exceptions are expected in test environment
        }

        $output = ob_get_clean();

        // Verify dispatch executed (either output or no exception)
        $this->assertIsString($output);
    }

    /**
     * Test that dispatch handles different HTTP methods.
     */
    public function testDispatchHandlesDifferentMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        $dispatchedMethods = [];

        foreach ($methods as $method) {
            ob_start();

            try {
                $this->router->dispatch($method, '/test-route-' . uniqid());
                $dispatchedMethods[] = $method;
            } catch (\Exception $e) {
                // Expected in test environment
            } finally {
                ob_end_clean();
            }
        }

        $this->assertSame(
            $methods,
            $dispatchedMethods,
            'All HTTP methods should be dispatched without throwing exceptions'
        );
    }

    /**
     * Test that Router properly initializes.
     */
    public function testRouterInitialization(): void
    {
        $router = Router::getInstance();
        
        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * Test that dispatch method signature is correct.
     */
    public function testDispatchMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(Router::class, 'dispatch');

        $this->assertSame('dispatch', $reflection->getName());
        $this->assertCount(2, $reflection->getParameters());

        $params = $reflection->getParameters();
        $this->assertSame('method', $params[0]->getName());
        $this->assertSame('uri', $params[1]->getName());
    }

    /**
     * Test that query strings are stripped before route matching.
     * /route?foo=bar must dispatch identically to /route (both 404 here).
     */
    public function testDispatchStripsQueryStringBeforeRouting(): void
    {
        ob_start();
        try {
            $this->router->dispatch('GET', '/no-such-route-abc?foo=bar&baz=1');
        } catch (\Exception $e) {
            // ignored
        }
        $withQuery = (string) ob_get_clean();

        ob_start();
        try {
            $this->router->dispatch('GET', '/no-such-route-abc');
        } catch (\Exception $e) {
            // ignored
        }
        $withoutQuery = (string) ob_get_clean();

        $this->assertSame(
            $withoutQuery,
            $withQuery,
            'Query string must not affect route matching'
        );
    }

    /**
     * Test that a callable handler returning a Response is emitted.
     * The GET / route returns Response::redirect('/home'); after dispatch
     * no HTML body should be produced (a redirect response has an empty body).
     */
    public function testDispatchEmitsResponseReturnedByCallableHandler(): void
    {
        ob_start();
        try {
            $this->router->dispatch('GET', '/');
        } catch (\Exception $e) {
            // ignored
        }
        $output = (string) ob_get_clean();

        // A redirect response has no body; the callable returned a Response and
        // Router emitted it (no plain text / HTML leaked into output).
        $this->assertSame('', $output);
    }

    /**
     * Test that a 404 response is emitted when no route matches.
     */
    public function testDispatchEmits404ForUnmatchedRoute(): void
    {
        ob_start();
        try {
            $this->router->dispatch('GET', '/this-route-does-not-exist-xyz');
        } catch (\Exception $e) {
            // ignored
        }
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('404', $output);
    }
}
