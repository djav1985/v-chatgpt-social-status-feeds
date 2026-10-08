<?php
// phpcs:ignoreFile
// SuppressWarnings(PHPMD.TooManyPublicMethods) - Test class requires comprehensive coverage

declare(strict_types=1);

namespace Tests;

use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class SessionManagerTest extends TestCase
{
    private SessionManager $session;
    private string|false $originalUserAgent;

    protected function setUp(): void
    {
        parent::setUp();

        // Snapshot the user-agent so tests that overwrite it can't bleed into each other.
        $this->originalUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? false;

        // Reads start the session on demand, so start it here to keep $_SESSION fixtures intact.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];
        $this->session = SessionManager::getInstance();
    }

    protected function tearDown(): void
    {
        // Restore the user-agent to prevent order-dependent test failures.
        if ($this->originalUserAgent !== false) {
            $_SERVER['HTTP_USER_AGENT'] = $this->originalUserAgent;
        } else {
            unset($_SERVER['HTTP_USER_AGENT']);
        }

        $_SESSION = [];
        parent::tearDown();
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = SessionManager::getInstance();
        $instance2 = SessionManager::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testGetReturnsValueFromSession(): void
    {
        $_SESSION['test_key'] = 'test_value';
        
        $result = $this->session->get('test_key');
        
        $this->assertSame('test_value', $result);
    }

    public function testGetReturnsDefaultWhenKeyNotExists(): void
    {
        $result = $this->session->get('non_existent_key', 'default_value');
        
        $this->assertSame('default_value', $result);
    }

    public function testGetReturnsNullWhenKeyNotExistsAndNoDefault(): void
    {
        $result = $this->session->get('non_existent_key');
        
        $this->assertNull($result);
    }

    public function testSetStoresValueInSession(): void
    {
        $this->session->set('test_key', 'test_value');
        
        $this->assertSame('test_value', $_SESSION['test_key']);
    }

    public function testSetOverwritesExistingValue(): void
    {
        $_SESSION['test_key'] = 'old_value';
        
        $this->session->set('test_key', 'new_value');
        
        $this->assertSame('new_value', $_SESSION['test_key']);
    }

    public function testSetHandlesDifferentDataTypes(): void
    {
        $testCases = [
            'string' => 'test_string',
            'integer' => 123,
            'float' => 123.45,
            'boolean' => true,
            'array' => ['a', 'b', 'c'],
            'null' => null,
        ];
        
        foreach ($testCases as $key => $value) {
            $this->session->set($key, $value);
            $this->assertSame($value, $this->session->get($key));
        }
    }

    public function testIsValidReturnsFalseWhenNotLoggedIn(): void
    {
        $_SESSION['logged_in'] = false;
        $_SESSION['timeout'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $result = $this->session->isValid();
        
        $this->assertFalse($result);
    }

    public function testIsValidReturnsTrueWhenLoggedIn(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['timeout'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $result = $this->session->isValid();
        
        $this->assertTrue($result);
    }

    public function testIsValidUpdatesTimeout(): void
    {
        $_SESSION['logged_in'] = true;
        $oldTimeout = time() - 100;
        $_SESSION['timeout'] = $oldTimeout;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $this->session->isValid();
        
        $newTimeout = $_SESSION['timeout'];
        $this->assertGreaterThan($oldTimeout, $newTimeout);
    }

    public function testIsValidReturnsFalseWhenTimeoutExceeded(): void
    {
        $_SESSION['logged_in'] = true;
        // Set timeout to 2000 seconds ago (exceeds default 1800 limit)
        $_SESSION['timeout'] = time() - 2000;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $result = $this->session->isValid();
        
        $this->assertFalse($result);
    }

    public function testIsValidReturnsFalseWhenUserAgentChanged(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['timeout'] = time();
        $_SESSION['user_agent'] = 'OldUserAgent/1.0';
        $_SERVER['HTTP_USER_AGENT'] = 'NewUserAgent/2.0';
        
        $result = $this->session->isValid();
        
        $this->assertFalse($result);
    }

    public function testIsValidHandlesMissingUserAgent(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['timeout'] = time();
        unset($_SESSION['user_agent']);
        unset($_SERVER['HTTP_USER_AGENT']);
        
        // Should not crash
        $result = $this->session->isValid();
        
        $this->assertTrue($result);
    }

    public function testDestroyEmptiesSessionData(): void
    {
        $_SESSION['test_key'] = 'test_value';
        $_SESSION['another_key'] = 'another_value';
        
        $this->session->destroy();

        $this->assertEmpty($_SESSION);
    }

    public function testComplexDataStructuresCanBeStored(): void
    {
        $complexData = [
            'nested' => [
                'array' => [1, 2, 3],
                'string' => 'test',
            ],
            'integer' => 123,
        ];
        
        $this->session->set('complex', $complexData);
        $result = $this->session->get('complex');
        
        $this->assertSame($complexData, $result);
    }

    // -------------------------------------------------------------------------
    // requireAuth() — returns bool, no longer calls exit() on unauthenticated
    // -------------------------------------------------------------------------

    public function testRequireAuthReturnsTrueWhenLoggedIn(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['timeout']   = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $result = $this->session->requireAuth();

        $this->assertTrue($result);
    }

    public function testRequireAuthReturnsFalseWhenNotLoggedIn(): void
    {
        $_SESSION['logged_in'] = false;
        $_SESSION['timeout']   = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $result = $this->session->requireAuth();

        $this->assertFalse($result);
    }

    public function testRequireAuthReturnsFalseWhenSessionExpired(): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['timeout']   = time() - 2000; // exceeds the 1800 s default
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $result = $this->session->requireAuth();

        $this->assertFalse($result);
    }

    public function testRequireAuthReturnsFalseWhenUserAgentMismatch(): void
    {
        $_SESSION['logged_in']  = true;
        $_SESSION['timeout']    = time();
        $_SESSION['user_agent'] = 'OldAgent/1.0';
        $_SERVER['HTTP_USER_AGENT'] = 'NewAgent/2.0';

        $result = $this->session->requireAuth();

        $this->assertFalse($result);
    }
}
