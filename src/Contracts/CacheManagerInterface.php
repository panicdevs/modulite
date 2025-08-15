<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Contracts;

/**
 * Interface for Modulite cache management.
 *
 * This interface defines the contract for caching discovered Filament panels
 * with support for multiple cache layers and invalidation strategies.
 *
 * @package PanicDevs\Modulite\Contracts
 */
interface CacheManagerInterface
{
    /**
     * Retrieve cached data or generate it using the provided callback.
     *
     * This method implements a multi-layer cache strategy:
     * 1. Memory cache (fastest, current request only)
     * 2. File cache (fast, persistent across requests)
     * 3. Laravel cache (configurable driver: Redis, Memcached, etc.)
     *
     * @param string $key Unique cache key identifier
     * @param callable(): array<string> $callback Function to generate data on cache miss
     * @return array<string> Array of discovered panel class names
     */
    public function remember(string $key, callable $callback): array;

    /**
     * Invalidate cached data for a specific key.
     *
     * This method removes data from all cache layers:
     * - Memory cache for current request
     * - File-based cache
     * - Laravel cache store
     *
     * @param string $key Cache key to invalidate
     */
    public function forget(string $key): void;

    /**
     * Clear all cached data across all layers.
     *
     * This is useful for:
     * - Module installations/uninstallations
     * - Development environment resets
     * - Cache corruption recovery
     */
    public function flush(): void;

    /**
     * Check if caching is currently enabled.
     *
     * Caching can be disabled globally through configuration
     * or temporarily for debugging purposes.
     *
     * @return bool True if caching is enabled, false otherwise
     */
    public function isCacheEnabled(): bool;
}
