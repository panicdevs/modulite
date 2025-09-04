<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use ReflectionClass;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Generator;
use Throwable;

/**
 * PanelScannerService handles discovery of Filament Panel classes.
 *
 * This service is responsible for:
 * - Scanning configured paths for PHP files
 * - Extracting class names using token parsing
 * - Filtering panel provider classes by naming conventions and inheritance
 * - Performance optimization with depth limits and exclusions
 * - Comprehensive error handling and logging
 *
 * @package PanicDevs\Modulite\Services
 */
class PanelScannerService implements PanelScannerInterface
{
    /**
     * Scanner configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Base application path.
     */
    protected string $basePath;

    /**
     * Module manager instance.
     */
    protected mixed $moduleManager;

    /**
     * Discovered classes cache for current scan.
     *
     * @var array<string, array<string>>
     */
    protected array $discoveredClasses = [];

    /**
     * Scan statistics for performance monitoring.
     *
     * @var array<string, mixed>
     */
    protected array $scanStats = [
        'files_scanned'     => 0,
        'classes_found'     => 0,
        'panels_discovered' => 0,
        'scan_time'         => 0,
        'errors'            => 0,
    ];

    /**
     * Create a new PanelScannerService instance.
     *
     * @param array<string, mixed> $config Scanner configuration
     * @param string $basePath Application base path
     * @param mixed $moduleManager nwidart module manager
     */
    public function __construct(array $config, string $basePath, mixed $moduleManager = null)
    {
        $this->config        = $config;
        $this->basePath      = mb_rtrim($basePath, '/');
        $this->moduleManager = $moduleManager;
    }

    /**
     * Discover all Filament Panel classes in configured locations.
     *
     * @return array<string> Array of fully qualified panel provider class names
     */
    public function discoverPanels(): array
    {
        $startTime = microtime(true);
        $this->resetScanStats();

        try
        {
            $this->logScanStart();

            $panelClasses  = [];
            $scanLocations = $this->resolveScanLocations();

            foreach ($scanLocations as $location)
            {
                $discovered   = $this->scanLocation($location);
                $panelClasses = array_merge($panelClasses, $discovered);
            }

            // Remove duplicates and ensure stable ordering
            $panelClasses = array_values(array_unique($panelClasses));

            $this->scanStats['panels_discovered'] = count($panelClasses);
            $this->scanStats['scan_time']         = microtime(true) - $startTime;

            $this->logScanComplete($panelClasses);

            return $panelClasses;

        } catch (Throwable $e)
        {
            $this->scanStats['scan_time'] = microtime(true) - $startTime;
            $this->logScanError($e);

            if ($this->shouldFailSilently())
            {
                return [];
            }

            throw new ScanException("Panel discovery failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Scan a specific location for panel classes.
     *
     * @param string $location Directory path to scan
     * @return array<string> Discovered panel class names
     */
    protected function scanLocation(string $location): array
    {
        if (!is_dir($location))
        {
            $this->logLocationSkipped($location, 'Directory does not exist');
            return [];
        }

        $panelClasses = [];

        try
        {
            $files = $this->getPhpFilesInLocation($location);

            foreach ($files as $file)
            {
                $discovered   = $this->scanFile($file);
                $panelClasses = array_merge($panelClasses, $discovered);
            }

        } catch (Throwable $e)
        {
            $this->logLocationError($location, $e);
            $this->scanStats['errors']++;

            if (!$this->shouldFailSilently())
            {
                throw new ScanException("Failed to scan location {$location}: {$e->getMessage()}", 0, $e);
            }
        }

        return $panelClasses;
    }

    /**
     * Scan a single PHP file for panel classes.
     *
     * @param string $filePath Path to PHP file
     * @return array<string> Panel class names found in file
     */
    protected function scanFile(string $filePath): array
    {
        $this->scanStats['files_scanned']++;

        try
        {
            $classes = $this->extractClassesFromFile($filePath);
            $this->scanStats['classes_found'] += count($classes);

            $panelClasses = [];

            foreach ($classes as $className)
            {
                if ($this->isPanelClass($className))
                {
                    $panelClasses[] = $className;
                }
            }

            if (!empty($panelClasses))
            {
                $this->logPanelsFound($filePath, $panelClasses);
            }

            return $panelClasses;

        } catch (Throwable $e)
        {
            $this->logFileError($filePath, $e);
            $this->scanStats['errors']++;

            if (!$this->shouldFailSilently())
            {
                throw new ScanException("Failed to scan file {$filePath}: {$e->getMessage()}", 0, $e);
            }

            return [];
        }
    }

    /**
     * Extract fully qualified class names from a PHP file.
     *
     * @param string $filePath Path to PHP file
     * @return array<string> Array of class names found in file
     */
    protected function extractClassesFromFile(string $filePath): array
    {
        // Use cache if available for current scan
        if (isset($this->discoveredClasses[$filePath]))
        {
            return $this->discoveredClasses[$filePath];
        }

        $code   = File::get($filePath);
        $tokens = token_get_all($code);

        if (!is_array($tokens))
        {
            return [];
        }

        $classes                            = $this->parseClassesFromTokens($tokens);
        $this->discoveredClasses[$filePath] = $classes;

        return $classes;
    }

    /**
     * Parse class names from PHP tokens.
     *
     * This method supports:
     * - Regular classes, interfaces, and traits
     * - Namespaced classes
     * - Anonymous class detection (skipped)
     * - Nested class structures
     *
     * @param array<mixed> $tokens PHP tokens from token_get_all()
     * @return array<string> Fully qualified class names
     */
    protected function parseClassesFromTokens(array $tokens): array
    {
        $namespace    = '';
        $collectNs    = false;
        $currentNs    = [];
        $classes      = [];
        $collectClass = false;
        $classTypes   = [T_CLASS, T_INTERFACE, T_TRAIT];

        for ($i = 0, $c = count($tokens); $i < $c; $i++)
        {
            $token = $tokens[$i];

            // Handle namespace declaration
            if (is_array($token) && T_NAMESPACE === $token[0])
            {
                $currentNs = [];
                $collectNs = true;
                continue;
            }

            // Collect namespace parts
            if ($collectNs)
            {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true))
                {
                    $currentNs[] = $token[1];
                } elseif (';' === $token || '{' === $token)
                {
                    $namespace = mb_trim(implode('', $currentNs));
                    $collectNs = false;
                }
                continue;
            }

            // Handle class/interface/trait declaration
            if (is_array($token) && in_array($token[0], $classTypes, true))
            {
                // Check if this is an anonymous class
                if ($this->isAnonymousClass($tokens, $i))
                {
                    continue;
                }

                $collectClass = true;
                continue;
            }

            // Collect class name
            if ($collectClass && is_array($token) && T_STRING === $token[0])
            {
                $className    = $token[1];
                $fqcn         = $this->buildFullyQualifiedClassName($namespace, $className);
                $classes[]    = $fqcn;
                $collectClass = false;
            }
        }

        return $classes;
    }

    /**
     * Check if a class token represents an anonymous class.
     *
     * @param array<mixed> $tokens All tokens
     * @param int $currentIndex Current token index
     */
    protected function isAnonymousClass(array $tokens, int $currentIndex): bool
    {
        // Look backwards for "new" keyword
        for ($i = $currentIndex - 1; $i >= 0; $i--)
        {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_FINAL, T_ABSTRACT], true))
            {
                continue;
            }

            if (is_array($token) && T_NEW === $token[0])
            {
                return true;
            }

            break;
        }

        return false;
    }

    /**
     * Build fully qualified class name from namespace and class name.
     */
    protected function buildFullyQualifiedClassName(string $namespace, string $className): string
    {
        if (empty($namespace))
        {
            return $className;
        }

        return mb_ltrim($namespace.'\\'.$className, '\\');
    }

    /**
     * Check if a class is a panel provider class using naming conventions and inheritance.
     */
    protected function isPanelClass(string $className): bool
    {
        try
        {
            // Check if class exists (avoid autoload failures)
            if (!class_exists($className))
            {
                return false;
            }

            $reflection = new ReflectionClass($className);

            // Only instantiable classes can be panel providers
            if (!$reflection->isInstantiable())
            {
                return false;
            }

            // Check naming convention: must contain "Panel" and end with "Provider"
            $classBaseName = $reflection->getShortName();
            if (!str_contains($classBaseName, 'Panel') || !str_ends_with($classBaseName, 'Provider'))
            {
                return false;
            }

            // Check if it extends PanelProvider (if available)
            $parentClass = $reflection->getParentClass();
            if ($parentClass && str_contains($parentClass->getName(), 'PanelProvider'))
            {
                return true;
            }

            // Check if it has panel-related methods (duck typing)
            $panelMethods = ['panel', 'configure', 'boot'];
            foreach ($panelMethods as $method)
            {
                if ($reflection->hasMethod($method))
                {
                    return true;
                }
            }

            return false;

        } catch (Throwable $e)
        {
            // Log reflection errors if configured
            if ($this->config['error_handling']['log_reflection_errors'] ?? true)
            {
                $this->logReflectionError($className, $e);
            }

            return false;
        }
    }

    /**
     * Resolve scan locations from configuration patterns.
     *
     * @return array<string> Absolute paths to scan
     */
    protected function resolveScanLocations(): array
    {
        $locations         = $this->config['panels']['locations'] ?? [];
        $resolvedLocations = [];

        foreach ($locations as $pattern)
        {
            $resolved          = $this->resolveLocationPattern($pattern);
            $resolvedLocations = array_merge($resolvedLocations, $resolved);
        }

        // Remove duplicates and ensure directories exist
        $resolvedLocations = array_unique($resolvedLocations);
        $resolvedLocations = array_filter($resolvedLocations, 'is_dir');

        return array_values($resolvedLocations);
    }

    /**
     * Resolve a location pattern to absolute paths.
     *
     * @return array<string> Array of resolved absolute paths
     */
    protected function resolveLocationPattern(string $pattern): array
    {
        // Convert relative pattern to absolute
        if (!str_starts_with($pattern, '/'))
        {
            $pattern = $this->basePath.'/'.mb_ltrim($pattern, '/');
        }

        // Handle wildcard patterns
        if (str_contains($pattern, '*'))
        {
            return $this->expandWildcardPattern($pattern);
        }

        // Single directory
        return [$pattern];
    }

    /**
     * Expand wildcard patterns to concrete paths.
     *
     * @return array<string> Array of expanded paths
     */
    protected function expandWildcardPattern(string $pattern): array
    {
        $paths = [];

        try
        {
            $globPaths = glob($pattern, GLOB_ONLYDIR);

            if (false !== $globPaths)
            {
                $paths = $globPaths;
            }
        } catch (Throwable $e)
        {
            $this->logPatternError($pattern, $e);
        }

        return $paths;
    }

    /**
     * Get PHP files in a location with filters and depth limits.
     *
     * @return Generator<string> Generator yielding file paths
     */
    protected function getPhpFilesInLocation(string $location): Generator
    {
        $maxDepth       = $this->config['panels']['scanning']['max_depth'] ?? 5;
        $excludedDirs   = $this->config['panels']['scanning']['excluded_directories'] ?? [];
        $followSymlinks = $this->config['panels']['scanning']['follow_symlinks'] ?? false;

        $flags = RecursiveDirectoryIterator::SKIP_DOTS;
        if ($followSymlinks)
        {
            $flags |= RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($location, $flags),
                function (SplFileInfo $file) use ($excludedDirs)
                {
                    if ($file->isDir())
                    {
                        $name = $file->getFilename();

                        // Skip excluded directories
                        if (in_array($name, $excludedDirs, true) || str_starts_with($name, '.'))
                        {
                            return false;
                        }
                    }

                    return $file->isDir() || 'php' === $file->getExtension();
                }
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        if ($maxDepth > 0)
        {
            $iterator->setMaxDepth($maxDepth);
        }

        foreach ($iterator as $file)
        {
            /** @var SplFileInfo $file */
            if ($file->isFile() && 'php' === $file->getExtension())
            {
                yield $file->getPathname();
            }
        }
    }

    /**
     * Reset scan statistics.
     */
    protected function resetScanStats(): void
    {
        $this->scanStats = [
            'files_scanned'     => 0,
            'classes_found'     => 0,
            'panels_discovered' => 0,
            'scan_time'         => 0,
            'errors'            => 0,
        ];

        $this->discoveredClasses = [];
    }

    /**
     * Get scan statistics.
     *
     * @return array<string, mixed>
     */
    public function getScanStats(): array
    {
        return $this->scanStats;
    }

    /**
     * Check if errors should be handled silently.
     */
    protected function shouldFailSilently(): bool
    {
        return $this->config['error_handling']['fail_silently'] ?? false;
    }

    // Logging methods...

    protected function logScanStart(): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->info('Modulite panel discovery started');
        }
    }

    /**
     * @param array<string> $panels
     */
    protected function logScanComplete(array $panels): void
    {
        if ($this->shouldLog())
        {
            $stats = $this->scanStats;
            Log::channel($this->getLogChannel())->info('Modulite panel discovery completed', [
                'panels_found'  => count($panels),
                'files_scanned' => $stats['files_scanned'],
                'classes_found' => $stats['classes_found'],
                'scan_time'     => round($stats['scan_time'], 3),
                'errors'        => $stats['errors'],
            ]);
        }
    }

    protected function logScanError(Throwable $e): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->error('Modulite panel discovery failed', [
                'error'      => $e->getMessage(),
                'scan_stats' => $this->scanStats,
            ]);
        }
    }

    protected function logLocationSkipped(string $location, string $reason): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->debug("Skipping location: {$location}", ['reason' => $reason]);
        }
    }

    protected function logLocationError(string $location, Throwable $e): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->warning("Error scanning location: {$location}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function logFileError(string $filePath, Throwable $e): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->warning("Error scanning file: {$filePath}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string> $panels
     */
    protected function logPanelsFound(string $filePath, array $panels): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->debug("Found panels in {$filePath}", [
                'panels' => $panels,
            ]);
        }
    }

    protected function logReflectionError(string $className, Throwable $e): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->debug("Reflection error for class: {$className}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function logPatternError(string $pattern, Throwable $e): void
    {
        if ($this->shouldLog())
        {
            Log::channel($this->getLogChannel())->warning("Error expanding pattern: {$pattern}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function shouldLog(): bool
    {
        return config('modulite.logging.enabled', false);
    }

    protected function getLogChannel(): string
    {
        return config('modulite.logging.channel', 'default');
    }
}
