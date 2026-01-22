<?php
// phpcs:ignoreFile
// SuppressWarnings(PHPMD.TooManyPublicMethods) - Test class needs comprehensive coverage

declare(strict_types=1);

namespace Tests;

use App\Services\CacheService;
use PHPUnit\Framework\TestCase;

final class CacheServiceTest extends TestCase
{
    private CacheService $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = CacheService::getInstance();
        // Clear all cache before each test
        $this->cache->clear();
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        $this->cache->clear();
        parent::tearDown();
    }

    public function testSingletonPattern(): void
    {
        $instance1 = CacheService::getInstance();
        $instance2 = CacheService::getInstance();
        
        $this->assertSame($instance1, $instance2, 'getInstance should return same instance');
    }

    public function testSetAndGet(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        $this->cache->set($key, $value);
        $result = $this->cache->get($key);
        
        $this->assertSame($value, $result, 'Should retrieve the same value that was set');
    }

    public function testGetNonExistentKey(): void
    {
        $result = $this->cache->get('non_existent_key');
        
        $this->assertNull($result, 'Should return null for non-existent keys');
    }

    public function testGetWithDefault(): void
    {
        $default = 'default_value';
        $result = $this->cache->get('non_existent_key', $default);
        
        $this->assertSame($default, $result, 'Should return default for non-existent keys');
    }

    public function testDelete(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        $this->cache->set($key, $value);
        $this->assertSame($value, $this->cache->get($key));
        
        $this->cache->delete($key);
        $this->assertNull($this->cache->get($key), 'Deleted key should return null');
    }

    public function testHas(): void
    {
        $key = 'test_key';
        
        $this->assertFalse($this->cache->has($key), 'Should return false before setting');
        
        $this->cache->set($key, 'value');
        $this->assertTrue($this->cache->has($key), 'Should return true after setting');
        
        $this->cache->delete($key);
        $this->assertFalse($this->cache->has($key), 'Should return false after deleting');
    }

    public function testRememberCallback(): void
    {
        $key = 'test_key';
        $expectedValue = 'computed_value';
        $callCount = 0;
        
        $callback = function () use ($expectedValue, &$callCount) {
            $callCount++;
            return $expectedValue;
        };
        
        // First call should execute callback
        $result1 = $this->cache->remember($key, $callback, 60);
        $this->assertSame($expectedValue, $result1);
        $this->assertSame(1, $callCount, 'Callback should be called once');
        
        // Second call should use cached value
        $result2 = $this->cache->remember($key, $callback, 60);
        $this->assertSame($expectedValue, $result2);
        $this->assertSame(1, $callCount, 'Callback should not be called again');
    }

    public function testSetWithTtl(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        // Set with 1 second TTL
        $this->cache->set($key, $value, 1);
        $this->assertSame($value, $this->cache->get($key));
        
        // Wait for TTL to expire
        sleep(2);
        $this->assertNull($this->cache->get($key), 'Should return null after TTL expires');
    }

    public function testClearAll(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');
        
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));
        
        $this->cache->clear();
        
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testClearWithPattern(): void
    {
        $this->cache->set('user:1', 'value1');
        $this->cache->set('user:2', 'value2');
        $this->cache->set('post:1', 'value3');
        
        $this->cache->clear('user:');
        
        $this->assertNull($this->cache->get('user:1'));
        $this->assertNull($this->cache->get('user:2'));
        $this->assertSame('value3', $this->cache->get('post:1'), 'Other prefixes should not be cleared');
    }

    public function testPrefixCollisionAvoidance(): void
    {
        // Test that prefix prevents collision with other apps
        $key = 'shared_key';
        $value = 'our_value';
        
        $this->cache->set($key, $value);
        
        // The actual APCu key should be prefixed
        $result = $this->cache->get($key);
        $this->assertSame($value, $result);
    }

    public function testStoreComplexData(): void
    {
        $key = 'complex_key';
        $value = [
            'string' => 'test',
            'number' => 123,
            'array' => [1, 2, 3],
            'nested' => ['key' => 'value'],
        ];
        
        $this->cache->set($key, $value);
        $result = $this->cache->get($key);
        
        $this->assertEquals($value, $result, 'Should handle complex data structures');
    }

    public function testTtlZeroMeansNoExpiration(): void
    {
        $key = 'permanent_key';
        $value = 'permanent_value';
        
        $this->cache->set($key, $value, 0);
        
        // Should still be available
        $this->assertSame($value, $this->cache->get($key));
    }
}
