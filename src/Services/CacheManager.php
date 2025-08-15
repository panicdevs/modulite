<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Exceptions\CacheException;
use Throwable;

/**
 * CacheManager handles multi-layer caching for Modulite panel discovery.
 *
 * This service provides:
 * - Memory caching for current request
 * - File-based caching similar to Laravel's bootstrap cache
 * - Standard Laravel cache drivers (Redis, Memcached, etc.)
 * - Cache invalidation strategies
 * - Performance optimization
 *
 * @package PanicDevs\Modulite\Services
 */
class CacheManager implements CacheManagerInterface
{
    /**
     * In-memory cache for the current request.
     *
     * @var array<string, mixed>
     */
    protected array $memoryCache = [];

    /**
     * Cache configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Laravel cache repository instance.
     */
    protected CacheRepository $cache;

    /**
     * Create a new CacheManager instance.
     *
     * @param array<string, mixed> $config Modulite cache configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->cache = Cache::store($this->config['driver'] ?? 'file');
    }

    /**
     * Retrieve cached panel classes using multi-layer cache strategy.
     *
     * @param string $key Cache key identifier
     * @param callable(): array<string> $callback Callback to generate data if cache miss
     * @return array<string> Array of panel class names
     *
     * @throws CacheException When cache operations fail
     */
    public function remember(string $key, callable $callback): array
    {
        if (!$this->isCacheEnabled()) {
            return $this->executeCallback($callback);
        }

        // Layer 1: Memory cache (fastest)
        if ($this->hasMemoryCache($key)) {
            $this->logCacheHit('memory', $key);
            return $this->getMemoryCache($key);
        }

        // Layer 2: File cache (fast, persistent)
        if ($this->hasFileCache($key)) {
            $data = $this->getFileCache($key);
            $this->setMemoryCache($key, $data);
            $this->logCacheHit('file', $key);
            return $data;
        }

        // Layer 3: Laravel cache (Redis, Memcached, etc.)
        $cacheKey = $this->buildCacheKey($key);
        $cachedData = $this->cache->get($cacheKey);

        if (null !== $cachedData) {
            $this->setMemoryCache($key, $cachedData);
            $this->setFileCache($key, $cachedData);
            $this->logCacheHit('store', $key);
            return $cachedData;
        }

        // Cache miss - generate data
        $data = $this->executeCallback($callback);
        $this->storeInAllLayers($key, $data);

        return $data;
    }

    /**
     * Store data in all available cache layers.
     *
     * @param string $key Cache key
     * @param array<string> $data Data to cache
     */
    protected function storeInAllLayers(string $key, array $data): void
    {
        $this->setMemoryCache($key, $data);

        if ($this->isFileCacheEnabled()) {
            $this->setFileCache($key, $data);
        }

        $cacheKey = $this->buildCacheKey($key);
        $ttl = $this->getCacheTtl();

        $this->cache->put($cacheKey, $data, $ttl);

        $this->logCacheWrite($key);
    }

    /**
     * Invalidate all cache layers for a specific key.
     *
     * @param string $key Cache key to invalidate
     */
    public function forget(string $key): void
    {
        // Clear memory cache
        unset($this->memoryCache[$key]);

        // Clear file cache
        if ($this->isFileCacheEnabled()) {
            $this->clearFileCache($key);
        }

        // Clear Laravel cache
        $cacheKey = $this->buildCacheKey($key);
        $this->cache->forget($cacheKey);

        $this->logCacheInvalidation($key);
    }

    /**
     * Clear all Modulite caches.
     */
    public function flush(): void
    {
        // Clear memory cache
        $this->memoryCache = [];

        // Clear file cache
        if ($this->isFileCacheEnabled()) {
            $this->clearAllFileCache();
        }

        // Clear Laravel cache with prefix
        $prefix = $this->config['prefix'] ?? 'modulite';
        $this->cache->flush(); // Note: This flushes the entire cache store

        $this->logCacheFlush();
    }

    /**
     * Check if cache is enabled based on configuration.
     */
    public function isCacheEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Check if file cache is enabled.
     */
    protected function isFileCacheEnabled(): bool
    {
        return ($this->config['file_cache']['enabled'] ?? true) && $this->isCacheEnabled();
    }

    /**
     * Check if memory cache contains the key.
     */
    protected function hasMemoryCache(string $key): bool
    {
        return isset($this->memoryCache[$key]);
    }

    /**
     * Get data from memory cache.
     *
     * @return array<string>
     */
    protected function getMemoryCache(string $key): array
    {
        return $this->memoryCache[$key] ?? [];
    }

    /**
     * Set data in memory cache with size limit.
     *
     * @param array<string> $data
     */
    protected function setMemoryCache(string $key, array $data): void
    {
        $maxItems = $this->config['memory_cache']['max_items'] ?? 1000;

        if (count($this->memoryCache) >= $maxItems) {
            // Remove oldest entry (simple FIFO)
            array_shift($this->memoryCache);
        }

        $this->memoryCache[$key] = $data;
    }

    /**
     * Check if file cache exists and is valid.
     */
    protected function hasFileCache(string $key): bool
    {
        if (!$this->isFileCacheEnabled()) {
            return false;
        }

        $filePath = $this->getFileCachePath($key);

        if (!File::exists($filePath)) {
            return false;
        }

        // Check TTL if auto-invalidation is enabled
        if ($this->config['invalidation']['auto_invalidate_on_file_change'] ?? false) {
            $fileTime = File::lastModified($filePath);
            $ttl = $this->getCacheTtl();

            if ($ttl > 0 && (time() - $fileTime) > $ttl) {
                File::delete($filePath);
                return false;
            }
        }

        return true;
    }

    /**
     * Get data from file cache.
     *
     * @return array<string>
     *
     * @throws CacheException When file cache is corrupted
     */
    protected function getFileCache(string $key): array
    {
        $filePath = $this->getFileCachePath($key);

        try {
            $content = File::get($filePath);
            $data = include $filePath;

            if (!is_array($data)) {
                throw new CacheException("File cache corrupted: {$filePath}");
            }

            return $data;
        } catch (Throwable $e) {
            // Remove corrupted cache file
            File::delete($filePath);
            throw new CacheException("Failed to read file cache: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Set data in file cache.
     *
     * @param array<string> $data
     *
     * @throws CacheException When file cache write fails
     */
    protected function setFileCache(string $key, array $data): void
    {
        if (!$this->isFileCacheEnabled()) {
            return;
        }

        $filePath = $this->getFileCachePath($key);
        $backupPath = $this->getFileCacheBackupPath($key);

        try {
            // Create backup of existing cache
            if (File::exists($filePath) && File::exists(dirname($backupPath))) {
                File::copy($filePath, $backupPath);
            }

            // Ensure directory exists
            File::ensureDirectoryExists(dirname($filePath));

            // Generate PHP cache file content
            $content = $this->generateFileCacheContent($data);

            // Atomic write using temporary file
            $tempPath = $filePath.'.tmp';
            File::put($tempPath, $content);
            File::move($tempPath, $filePath);

            // Set appropriate permissions
            chmod($filePath, 0644);

        } catch (Throwable $e) {
            // Restore from backup if available
            if (File::exists($backupPath)) {
                File::copy($backupPath, $filePath);
            }

            throw new CacheException("Failed to write file cache: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generate PHP file content for cache.
     *
     * @param array<string> $data
     */
    protected function generateFileCacheContent(array $data): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $count = count($data);

        $export = var_export($data, true);

        return <<<PHP
<?php
/**
 * Modulite Panel Cache
 * Generated: {$timestamp}
 * Panel Count: {$count}
 * 
 * This file is automatically generated by Modulite.
 * Do not modify this file manually.
 */

return {$export};
PHP;
    }

    /**
     * Clear file cache for specific key.
     */
    protected function clearFileCache(string $key): void
    {
        $filePath = $this->getFileCachePath($key);
        $backupPath = $this->getFileCacheBackupPath($key);

        File::delete([$filePath, $backupPath]);
    }

    /**
     * Clear all file caches.
     */
    protected function clearAllFileCache(): void
    {
        $pattern = dirname($this->getFileCachePath('*')).'/modulite_*.php';
        $files = File::glob($pattern);

        File::delete($files);
    }

    /**
     * Get file cache path for a key.
     */
    protected function getFileCachePath(string $key): string
    {
        $basePath = $this->config['file_cache']['path'] ?? base_path('bootstrap/cache/modulite_panels.php');

        if ('panels' === $key) {
            return $basePath;
        }

        $dir = dirname($basePath);
        $filename = 'modulite_'.md5($key).'.php';

        return $dir.'/'.$filename;
    }

    /**
     * Get file cache backup path for a key.
     */
    protected function getFileCacheBackupPath(string $key): string
    {
        $basePath = $this->config['file_cache']['backup_path'] ?? base_path('bootstrap/cache/modulite_panels_backup.php');

        if ('panels' === $key) {
            return $basePath;
        }

        $dir = dirname($basePath);
        $filename = 'modulite_'.md5($key).'_backup.php';

        return $dir.'/'.$filename;
    }

    /**
     * Build cache key with prefix.
     */
    protected function buildCacheKey(string $key): string
    {
        $prefix = $this->config['prefix'] ?? 'modulite';
        return $prefix.':'.$key;
    }

    /**
     * Get cache TTL in seconds.
     */
    protected function getCacheTtl(): int
    {
        return $this->config['ttl'] ?? 3600;
    }

    /**
     * Execute callback and handle any exceptions.
     *
     * @return array<string>
     *
     * @throws CacheException When callback execution fails
     */
    protected function executeCallback(callable $callback): array
    {
        try {
            $result = $callback();

            if (!is_array($result)) {
                throw new CacheException('Callback must return an array');
            }

            return $result;
        } catch (Throwable $e) {
            throw new CacheException("Callback execution failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Log cache hit for debugging.
     */
    protected function logCacheHit(string $layer, string $key): void
    {
        if ($this->shouldLog('cache_hits')) {
            Log::channel($this->getLogChannel())->debug("Modulite cache hit [{$layer}]: {$key}");
        }
    }

    /**
     * Log cache write for debugging.
     */
    protected function logCacheWrite(string $key): void
    {
        if ($this->shouldLog('cache_writes')) {
            Log::channel($this->getLogChannel())->debug("Modulite cache write: {$key}");
        }
    }

    /**
     * Log cache invalidation.
     */
    protected function logCacheInvalidation(string $key): void
    {
        if ($this->shouldLog('cache_invalidation')) {
            Log::channel($this->getLogChannel())->info("Modulite cache invalidated: {$key}");
        }
    }

    /**
     * Log cache flush.
     */
    protected function logCacheFlush(): void
    {
        if ($this->shouldLog('cache_flush')) {
            Log::channel($this->getLogChannel())->info('Modulite cache flushed');
        }
    }

    /**
     * Check if logging is enabled for specific event.
     */
    protected function shouldLog(string $event): bool
    {
        $loggingConfig = config('modulite.logging', []);

        return ($loggingConfig['enabled'] ?? false) &&
               ($loggingConfig['log_'.$event] ?? false);
    }

    /**
     * Get logging channel.
     */
    protected function getLogChannel(): string
    {
        return config('modulite.logging.channel', 'default');
    }
}
