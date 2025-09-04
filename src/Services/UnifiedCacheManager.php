<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services;

use Illuminate\Support\Facades\File;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use Throwable;

/**
 * UnifiedCacheManager provides a simple, robust cache system using a single file.
 *
 * This is similar to Laravel's bootstrap cache system, storing all Modulite
 * discovery data in a single PHP file for optimal performance.
 */
class UnifiedCacheManager implements CacheManagerInterface
{
    protected string $cacheFile;
    protected array $cache = [];
    protected bool $loaded = false;
    protected bool $enabled;
    protected int $ttl;
    protected bool $needsDelayedSave = false;

    /**
     * Static cache for production to avoid repeated file includes
     */
    protected static array $staticCache = [];

    public function __construct(array $config = [])
    {
        $this->cacheFile = $config['file'] ?? base_path('bootstrap/cache/modulite.php');
        $this->enabled   = $config['enabled'] ?? true;
        $this->ttl       = $config['ttl'] ?? 3600;
    }

    /**
     * Get an item from the cache with optimized performance.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Fast path: disabled cache
        if (!$this->enabled)
        {
            return $default;
        }

        $this->loadCache();

        // Fast path: key doesn't exist
        if (!isset($this->cache['data'][$key]))
        {
            return $default;
        }

        $item = $this->cache['data'][$key];

        // Ultra-fast path: never expires (production optimization)
        if ($this->ttl <= 0)
        {
            return $item['value'] ?? $default;
        }

        // Fast path: no expiration set
        if (!isset($item['expires']))
        {
            return $item['value'] ?? $default;
        }

        // Check expiration only when necessary
        if (time() > $item['expires'])
        {
            unset($this->cache['data'][$key]);
            // Defer cache save to avoid I/O during request
            if ('production' !== env('APP_ENV'))
            {
                $this->scheduleDelayedSave();
            }
            return $default;
        }

        return $item['value'] ?? $default;
    }

    /**
     * Store an item in the cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled)
        {
            return false;
        }

        $this->loadCache();

        $effectiveTtl = $ttl ?? $this->ttl;
        $expires      = $effectiveTtl > 0 ? time() + $effectiveTtl : null;

        $this->cache['data'][$key] = [
            'value'   => $value,
            'created' => time(),
            'expires' => $expires,
        ];

        return $this->saveCache();
    }

    /**
     * Remember an item in the cache.
     * Interface-compliant method that returns arrays.
     */
    public function remember(string $key, callable $callback): array
    {
        $value = $this->get($key);

        if (null !== $value)
        {
            return is_array($value) ? $value : [];
        }

        $value = $callback();
        $this->put($key, $value);

        return is_array($value) ? $value : [];
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): void
    {
        if (!$this->enabled)
        {
            return;
        }

        $this->loadCache();

        if (isset($this->cache['data'][$key]))
        {
            unset($this->cache['data'][$key]);
            $this->saveCache();
        }
    }

    /**
     * Clear all cached items.
     */
    public function flush(): void
    {
        $this->cache = [
            'version' => '1.0',
            'created' => time(),
            'data'    => [],
        ];

        if (File::exists($this->cacheFile))
        {
            File::delete($this->cacheFile);
        }
    }

    /**
     * Check if cache has an item.
     */
    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $this->loadCache();

        $stats = [
            'file_exists'   => File::exists($this->cacheFile),
            'file_size'     => File::exists($this->cacheFile) ? File::size($this->cacheFile) : 0,
            'enabled'       => $this->enabled,
            'total_items'   => count($this->cache['data'] ?? []),
            'cache_created' => $this->cache['created'] ?? null,
        ];

        if (isset($this->cache['data']))
        {
            $expired = 0;
            $valid   = 0;

            foreach ($this->cache['data'] as $item)
            {
                if (isset($item['expires']) && time() > $item['expires'])
                {
                    $expired++;
                } else
                {
                    $valid++;
                }
            }

            $stats['valid_items']   = $valid;
            $stats['expired_items'] = $expired;
        }

        return $stats;
    }

    /**
     * Load cache from file.
     */
    protected function loadCache(): void
    {
        if ($this->loaded || !$this->enabled)
        {
            return;
        }

        // Production optimization: use static cache to avoid repeated file includes
        $cacheKey = $this->cacheFile;
        if ('production' === env('APP_ENV') && isset(static::$staticCache[$cacheKey]))
        {
            $this->cache  = static::$staticCache[$cacheKey];
            $this->loaded = true;
            return;
        }

        if (!File::exists($this->cacheFile))
        {
            $this->cache = [
                'version' => '1.0',
                'created' => time(),
                'data'    => [],
            ];
        } else
        {
            try
            {
                $this->cache = include $this->cacheFile;

                // Validate cache structure
                if (!isset($this->cache['data']))
                {
                    $this->cache = [
                        'version' => '1.0',
                        'created' => time(),
                        'data'    => [],
                    ];
                }
            } catch (Throwable)
            {
                // Corrupted cache file, reset
                $this->cache = [
                    'version' => '1.0',
                    'created' => time(),
                    'data'    => [],
                ];
            }
        }

        // Store in static cache for production
        if ('production' === env('APP_ENV'))
        {
            static::$staticCache[$cacheKey] = $this->cache;
        }

        $this->loaded = true;
    }

    /**
     * Save cache to file.
     */
    protected function saveCache(): bool
    {
        if (!$this->enabled)
        {
            return false;
        }

        try
        {
            // Ensure cache directory exists
            $cacheDir = dirname($this->cacheFile);
            if (!File::isDirectory($cacheDir))
            {
                File::makeDirectory($cacheDir, 0755, true);
            }

            // Generate cache file content
            $content = "<?php\n\n// Modulite cache file - generated ".date('Y-m-d H:i:s')."\n";
            $content .= "// Do not modify this file manually\n\n";
            $content .= "return ".var_export($this->cache, true).";\n";

            return false !== File::put($this->cacheFile, $content);

        } catch (Throwable)
        {
            return false;
        }
    }

    /**
     * Get the cache file path.
     */
    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }

    /**
     * Check if cache is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if caching is currently enabled.
     * Interface-compliant method.
     */
    public function isCacheEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Temporarily enable caching for command operations.
     * Useful for development when cache is normally disabled.
     */
    public function enableTemporarily(): void
    {
        $this->enabled = true;
    }

    /**
     * Schedule a delayed save to avoid I/O during critical paths.
     */
    protected function scheduleDelayedSave(): void
    {
        if ($this->needsDelayedSave)
        {
            return; // Already scheduled
        }

        $this->needsDelayedSave = true;

        // Register shutdown function to save cache after response
        register_shutdown_function(function (): void
        {
            if ($this->needsDelayedSave)
            {
                $this->saveCache();
                $this->needsDelayedSave = false;
            }
        });
    }
}
