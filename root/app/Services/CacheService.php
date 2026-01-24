<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: CacheService.php
 * Description: Singleton cache service with APCu support and in-memory fallback.
 */

namespace App\Services;

use App\Core\ErrorManager;

class CacheService
{
    /**
     * Singleton instance.
     */
    private static ?self $instance = null;

    /**
     * Cache key prefix to avoid collisions.
     */
    private const PREFIX = 'socialrss:';

    /**
     * Whether APCu is available and enabled.
     */
    private bool $apcuAvailable;

    /**
     * In-memory cache fallback.
     *
     * @var array<string, array{value: mixed, expires: int}>
     */
    private static array $memoryCache = [];

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
        $this->apcuAvailable = extension_loaded('apcu') && \apcu_enabled();
        
        if (!$this->apcuAvailable) {
            ErrorManager::getInstance()->log(
                'APCu is not available. Using in-memory cache fallback.',
                'info'
            );
        }
    }

    /**
     * Retrieve the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieve a cached value by key.
     *
     * @param string $key The cache key.
     * @param mixed $default Default value if key is not found.
     * @return mixed The cached value or default.
     */
    public function get(string $key, $default = null)
    {
        $prefixedKey = self::PREFIX . $key;

        if ($this->apcuAvailable) {
            $success = false;
            $value = \apcu_fetch($prefixedKey, $success);
            
            if ($success) {
                return $value;
            }
        } else {
            if (isset(self::$memoryCache[$prefixedKey])) {
                $entry = self::$memoryCache[$prefixedKey];
                
                if ($entry['expires'] === 0 || $entry['expires'] > time()) {
                    return $entry['value'];
                }
                
                unset(self::$memoryCache[$prefixedKey]);
            }
        }

        return $default;
    }

    /**
     * Store a value in the cache with a TTL.
     *
     * @param string $key The cache key.
     * @param mixed $value The value to cache.
     * @param int $ttl Time-to-live in seconds (0 = never expires).
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $prefixedKey = self::PREFIX . $key;

        if ($this->apcuAvailable) {
            $result = \apcu_store($prefixedKey, $value, $ttl);
            
            if (!$result) {
                ErrorManager::getInstance()->log(
                    "Failed to store key '{$key}' in APCu cache.",
                    'warning'
                );
            }
            
            return $result;
        } else {
            $expires = $ttl > 0 ? time() + $ttl : 0;
            self::$memoryCache[$prefixedKey] = [
                'value' => $value,
                'expires' => $expires,
            ];
            
            return true;
        }
    }

    /**
     * Delete a specific key from the cache.
     *
     * @param string $key The cache key to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key): bool
    {
        $prefixedKey = self::PREFIX . $key;

        if ($this->apcuAvailable) {
            return \apcu_delete($prefixedKey);
        } else {
            if (isset(self::$memoryCache[$prefixedKey])) {
                unset(self::$memoryCache[$prefixedKey]);
                return true;
            }
            
            return false;
        }
    }

    /**
     * Clear all cache entries or entries matching a pattern.
     *
     * @param string|null $pattern Optional pattern to match keys (only prefix matching supported).
     * @return bool True on success, false on failure.
     */
    public function clear(?string $pattern = null): bool
    {
        // Clear in-memory cache
        if ($pattern === null) {
            self::$memoryCache = [];
        } else {
            // Clear matching keys from memory cache
            $prefixedPattern = self::PREFIX . $pattern;
            foreach (array_keys(self::$memoryCache) as $key) {
                if (strpos($key, $prefixedPattern) === 0) {
                    unset(self::$memoryCache[$key]);
                }
            }
        }

        if (!$this->apcuAvailable) {
            return true;
        }

        return $this->clearApcuCache($pattern);
    }

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key The cache key to check.
     * @return bool True if key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        $prefixedKey = self::PREFIX . $key;

        if ($this->apcuAvailable) {
            return \apcu_exists($prefixedKey);
        } else {
            if (isset(self::$memoryCache[$prefixedKey])) {
                $entry = self::$memoryCache[$prefixedKey];
                
                if ($entry['expires'] === 0 || $entry['expires'] > time()) {
                    return true;
                }
                
                unset(self::$memoryCache[$prefixedKey]);
            }
            
            return false;
        }
    }

    /**
     * Cache-aside pattern: retrieve from cache or generate and store.
     *
     * @param string $key The cache key.
     * @param int $ttl Time-to-live in seconds.
     * @param callable $callback Callback to generate value if not cached.
     * @return mixed The cached or generated value.
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $sentinel = new \stdClass();
        $value = $this->get($key, $sentinel);
        
        if ($value !== $sentinel) {
            return $value;
        }

        try {
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        } catch (\Throwable $e) {
            ErrorManager::getInstance()->log(
                "Cache remember callback failed for key '{$key}': {$e->getMessage()}",
                'error'
            );
            
            throw $e;
        }
    }

    /**
     * Clear APCu cache entries.
     *
     * @param string|null $pattern Optional pattern to match keys
     * @return bool True on success, false on failure
     */
    private function clearApcuCache(?string $pattern): bool
    {
        $prefixedPattern = $pattern === null 
            ? self::PREFIX 
            : self::PREFIX . $pattern;

        try {
            $iterator = new \APCUIterator('/^' . preg_quote($prefixedPattern, '/') . '/');
            return $this->deleteApcuEntries($iterator);
        } catch (\Throwable $e) {
            ErrorManager::getInstance()->log(
                "Failed to clear APCu cache with pattern '{$prefixedPattern}': {$e->getMessage()}",
                'error'
            );
            return false;
        }
    }
    
    /**
     * Delete APCu entries from iterator.
     *
     * @param \APCUIterator $iterator Iterator over cache entries
     * @return bool True if all deletions succeeded
     */
    private function deleteApcuEntries(\APCUIterator $iterator): bool
    {
        $deleted = true;

        foreach ($iterator as $entry) {
            if (!\apcu_delete($entry['key'])) {
                $deleted = false;
                ErrorManager::getInstance()->log(
                    "Failed to delete APCu cache key: {$entry['key']}",
                    'warning'
                );
            }
        }

        return $deleted;
    }

}
