<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when cache operations fail.
 *
 * This exception is used for various cache-related errors such as:
 * - File cache read/write failures
 * - Cache corruption
 * - Callback execution failures
 * - Cache configuration issues
 *
 * @package PanicDevs\Modulite\Exceptions
 */
class CacheException extends Exception
{
    /**
     * Create a new cache exception instance.
     *
     * @param string $message Error message describing what went wrong
     * @param int $code Error code (default: 0)
     * @param Throwable|null $previous Previous exception for exception chaining
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for file cache read failures.
     */
    public static function fileReadFailed(string $path, Throwable $previous = null): self
    {
        return new self("Failed to read cache file: {$path}", 1001, $previous);
    }

    /**
     * Create exception for file cache write failures.
     */
    public static function fileWriteFailed(string $path, Throwable $previous = null): self
    {
        return new self("Failed to write cache file: {$path}", 1002, $previous);
    }

    /**
     * Create exception for corrupted cache data.
     */
    public static function corruptedCache(string $key, Throwable $previous = null): self
    {
        return new self("Cache data corrupted for key: {$key}", 1003, $previous);
    }

    /**
     * Create exception for invalid callback results.
     */
    public static function invalidCallbackResult(string $expected = 'array'): self
    {
        return new self("Cache callback must return {$expected}", 1004);
    }

    /**
     * Create exception for cache configuration errors.
     */
    public static function configurationError(string $setting, Throwable $previous = null): self
    {
        return new self("Invalid cache configuration: {$setting}", 1005, $previous);
    }
}
