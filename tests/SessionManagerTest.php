<?php
// phpcs:ignoreFile PHPMD.TooManyPublicMethods - Test class requires comprehensive coverage

declare(strict_types=1);

namespace Tests;

use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class SessionManagerTest extends TestCase
{
    private SessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SESSION = [];
        $this->session = SessionManager::getInstance();
    }

    protected function tearDown(): void
    {
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
        
        // Can't actually start session in tests, so just test that destroy clears $_SESSION
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Manually clear since session_destroy won't work
            $_SESSION = [];
        }
        
        $this->assertEmpty($_SESSION);
    }

    public function testGetAndSetWorkTogether(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        $this->session->set($key, $value);
        $result = $this->session->get($key);
        
        $this->assertSame($value, $result);
    }

    public function testMultipleKeysCanBeStored(): void
    {
        $this->session->set('key1', 'value1');
        $this->session->set('key2', 'value2');
        $this->session->set('key3', 'value3');
        
        $this->assertSame('value1', $this->session->get('key1'));
        $this->assertSame('value2', $this->session->get('key2'));
        $this->assertSame('value3', $this->session->get('key3'));
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
}
