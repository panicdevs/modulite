<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when panel scanning operations fail.
 *
 * This exception is used for various scanning-related errors such as:
 * - File system access failures
 * - Token parsing errors
 * - Reflection failures
 * - Directory traversal issues
 * - Configuration validation errors
 *
 * @package PanicDevs\Modulite\Exceptions
 */
class ScanException extends Exception
{
    /**
     * Create a new scan exception instance.
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
     * Create exception for file access failures.
     */
    public static function fileAccessFailed(string $path, Throwable $previous = null): self
    {
        return new self("Cannot access file: {$path}", 2001, $previous);
    }

    /**
     * Create exception for directory access failures.
     */
    public static function directoryAccessFailed(string $path, Throwable $previous = null): self
    {
        return new self("Cannot access directory: {$path}", 2002, $previous);
    }

    /**
     * Create exception for token parsing failures.
     */
    public static function tokenParsingFailed(string $file, Throwable $previous = null): self
    {
        return new self("Failed to parse PHP tokens in file: {$file}", 2003, $previous);
    }

    /**
     * Create exception for reflection failures.
     */
    public static function reflectionFailed(string $className, Throwable $previous = null): self
    {
        return new self("Failed to reflect class: {$className}", 2004, $previous);
    }

    /**
     * Create exception for invalid scan configuration.
     */
    public static function invalidConfiguration(string $setting, Throwable $previous = null): self
    {
        return new self("Invalid scan configuration: {$setting}", 2005, $previous);
    }

    /**
     * Create exception for pattern expansion failures.
     */
    public static function patternExpansionFailed(string $pattern, Throwable $previous = null): self
    {
        return new self("Failed to expand pattern: {$pattern}", 2006, $previous);
    }

    /**
     * Create exception for maximum error threshold reached.
     */
    public static function errorThresholdReached(int $maxErrors): self
    {
        return new self("Maximum scan errors reached: {$maxErrors}", 2007);
    }

    /**
     * Create exception for scan timeout.
     */
    public static function scanTimeout(float $maxTime): self
    {
        return new self("Scan operation timed out after {$maxTime} seconds", 2008);
    }
}
