<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services;

use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PanicDevs\Modulite\Attributes\FilamentPage;
use PanicDevs\Modulite\Attributes\FilamentResource;
use PanicDevs\Modulite\Attributes\FilamentWidget;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use PanicDevs\Modulite\Exceptions\ScanException;
use ReflectionClass;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Generator;
use Throwable;

/**
 * ComponentScannerService handles discovery of Filament components.
 *
 * This service is responsible for:
 * - Scanning configured paths for Filament components
 * - Extracting class names using token parsing
 * - Filtering classes by component type (Resource, Page, Widget)
 * - Performance optimization with depth limits and exclusions
 * - Comprehensive error handling and logging
 *
 * @package PanicDevs\Modulite\Services
 */
class ComponentScannerService implements ComponentScannerInterface
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
     * Discovered components cache for current scan.
     *
     * @var array<string, array<string, array<string>>>
     */
    protected array $discoveredComponents = [];

    /**
     * Scan statistics for performance monitoring.
     *
     * @var array<string, mixed>
     */
    protected array $scanStats = [
        'files_scanned'         => 0,
        'classes_found'         => 0,
        'components_discovered' => 0,
        'scan_time'             => 0,
        'errors'                => 0,
    ];

    /**
     * Component type mapping to attribute classes.
     *
     * @var array<string, string>
     */
    protected array $componentTypeMap = [
        'resources' => FilamentResource::class,
        'pages'     => FilamentPage::class,
        'widgets'   => FilamentWidget::class,
    ];

    /**
     * Component type mapping to base classes (fallback detection).
     *
     * @var array<string, string>
     */
    protected array $baseClassMap = [
        'resources' => Resource::class,
        'pages'     => Page::class,
        'widgets'   => Widget::class,
    ];

    /**
     * Create a new ComponentScannerService instance.
     *
     * @param array<string, mixed> $config Scanner configuration
     * @param string $basePath Application base path
     * @param mixed $moduleManager nwidart module manager
     */
    public function __construct(array $config, string $basePath, mixed $moduleManager = null)
    {
        $this->config = $config;
        $this->basePath = mb_rtrim($basePath, '/');
        $this->moduleManager = $moduleManager;
    }

    /**
     * {@inheritDoc}
     */
    public function discoverComponents(string $panelName): array
    {
        $startTime = microtime(true);
        $this->resetScanStats();

        try {
            $this->logScanStart($panelName);

            $allComponents = [];
            $scanLocations = $this->resolveScanLocations($panelName);

            foreach ($scanLocations as $location) {
                $discovered = $this->scanLocationForComponents($location);
                $allComponents = $this->mergeComponents($allComponents, $discovered);
            }

            // Remove duplicates and ensure stable ordering
            $allComponents = $this->cleanupComponents($allComponents);

            $this->scanStats['components_discovered'] = $this->countTotalComponents($allComponents);
            $this->scanStats['scan_time'] = microtime(true) - $startTime;

            $this->logScanComplete($panelName, $allComponents);

            return $allComponents;

        } catch (Throwable $e) {
            $this->scanStats['scan_time'] = microtime(true) - $startTime;
            $this->logScanError($panelName, $e);

            if ($this->shouldFailSilently()) {
                return [];
            }

            throw new ScanException("Component discovery failed for panel {$panelName}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function discoverComponentType(string $panelName, string $componentType): array
    {
        if (!isset($this->componentTypeMap[$componentType])) {
            throw new ScanException("Invalid component type: {$componentType}");
        }

        $allComponents = $this->discoverComponents($panelName);
        return $allComponents[$componentType] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getScanStats(): array
    {
        return $this->scanStats;
    }

    /**
     * {@inheritDoc}
     */
    public function isComponentType(string $className, string $componentType): bool
    {
        if (!isset($this->componentTypeMap[$componentType])) {
            return false;
        }

        try {
            if (!class_exists($className)) {
                return false;
            }

            $reflection = new ReflectionClass($className);

            // Check if class is instantiable first
            if (!$reflection->isInstantiable()) {
                return false;
            }

            // Primary check: Look for component attribute
            $attributeClass = $this->componentTypeMap[$componentType];
            $attributes = $reflection->getAttributes($attributeClass);

            if (!empty($attributes)) {
                // Found the attribute, check if it's enabled for the panel
                $attribute = $attributes[0]->newInstance();
                return $attribute->isEnabled();
            }

            // Fallback check: Check if it extends the base class
            $baseClass = $this->baseClassMap[$componentType] ?? null;
            return (bool) ($baseClass && $reflection->isSubclassOf($baseClass))



            ;

        } catch (Throwable $e) {
            $this->logComponentCheckError($className, $componentType, $e);
            return false;
        }
    }

    /**
     * Check if a component is enabled for a specific panel.
     *
     * @param string $className Component class name
     * @param string $componentType Component type
     * @param string $panelId Panel identifier
     * @return bool True if component should be registered with panel
     */
    public function isComponentEnabledForPanel(string $className, string $componentType, string $panelId): bool
    {
        try {
            if (!class_exists($className)) {
                return false;
            }

            $reflection = new ReflectionClass($className);
            $attributeClass = $this->componentTypeMap[$componentType] ?? null;

            if (!$attributeClass) {
                return true; // No attribute type defined, assume enabled
            }

            $attributes = $reflection->getAttributes($attributeClass);

            if (!empty($attributes)) {
                $attribute = $attributes[0]->newInstance();
                return $attribute->isEnabledForPanel($panelId);
            }

            // No attribute found, check fallback
            return $this->isComponentType($className, $componentType);

        } catch (Throwable $e) {
            $this->logComponentCheckError($className, $componentType, $e);
            return false;
        }
    }

    /**
     * Get component priority from attribute.
     *
     * @param string $className Component class name
     * @param string $componentType Component type
     * @return int Component priority (higher = first)
     */
    public function getComponentPriority(string $className, string $componentType): int
    {
        try {
            if (!class_exists($className)) {
                return 0;
            }

            $reflection = new ReflectionClass($className);
            $attributeClass = $this->componentTypeMap[$componentType] ?? null;

            if (!$attributeClass) {
                return 0;
            }

            $attributes = $reflection->getAttributes($attributeClass);

            if (!empty($attributes)) {
                $attribute = $attributes[0]->newInstance();
                return $attribute->getPriority();
            }

            // Fallback: Check for static getPriority method
            if (method_exists($className, 'getPriority')) {
                return $className::getPriority();
            }

            return 0;

        } catch (Throwable $e) {
            $this->logComponentCheckError($className, $componentType, $e);
            return 0;
        }
    }

    /**
     * Filter components by panel and sort by priority.
     *
     * @param array<string, array<string>> $components Components by type
     * @param string $panelId Panel identifier
     * @return array<string, array<string>> Filtered and sorted components
     */
    public function filterAndSortComponentsForPanel(array $components, string $panelId): array
    {
        $filtered = [];

        foreach ($components as $type => $classList) {
            $enabledComponents = [];

            foreach ($classList as $className) {
                if ($this->isComponentEnabledForPanel($className, $type, $panelId)) {
                    $enabledComponents[] = [
                        'class'    => $className,
                        'priority' => $this->getComponentPriority($className, $type)
                    ];
                }
            }

            // Sort by priority (higher first), then by name
            usort($enabledComponents, function ($a, $b) {
                if ($a['priority'] === $b['priority']) {
                    return strcmp($a['class'], $b['class']);
                }
                return $b['priority'] <=> $a['priority'];
            });

            $filtered[$type] = array_column($enabledComponents, 'class');
        }

        return $filtered;
    }

    /**
     * Resolve scan locations for a specific panel.
     *
     * @param string $panelName Panel name to resolve locations for
     * @return array<string> Array of resolved absolute paths
     */
    protected function resolveScanLocations(string $panelName): array
    {
        $locations = $this->config['component_scan']['locations'] ?? [];
        $resolvedLocations = [];

        foreach ($locations as $pattern) {
            $resolved = $this->resolveLocationPattern($pattern, $panelName);
            $resolvedLocations = array_merge($resolvedLocations, $resolved);
        }

        // Remove duplicates and ensure directories exist
        $resolvedLocations = array_unique($resolvedLocations);
        $resolvedLocations = array_filter($resolvedLocations, 'is_dir');

        return array_values($resolvedLocations);
    }

    /**
     * Resolve a location pattern to absolute paths for a panel.
     *
     * @param string $pattern Location pattern with placeholders
     * @param string $panelName Panel name to substitute
     * @return array<string> Array of resolved absolute paths
     */
    protected function resolveLocationPattern(string $pattern, string $panelName): array
    {
        // Replace panel name placeholder
        $pattern = str_replace('{PanelName}', $panelName, $pattern);

        // Convert relative pattern to absolute
        if (!str_starts_with($pattern, '/')) {
            $pattern = $this->basePath.'/'.mb_ltrim($pattern, '/');
        }

        // Handle wildcard patterns
        if (str_contains($pattern, '*')) {
            return $this->expandWildcardPattern($pattern);
        }

        // Single directory
        return [$pattern];
    }

    /**
     * Expand wildcard patterns to concrete paths.
     *
     * @param string $pattern Pattern with wildcards
     * @return array<string> Array of expanded paths
     */
    protected function expandWildcardPattern(string $pattern): array
    {
        $paths = [];

        try {
            $globPaths = glob($pattern, GLOB_ONLYDIR);

            if (false !== $globPaths) {
                $paths = $globPaths;
            }

        } catch (Throwable $e) {
            $this->logPatternExpansionError($pattern, $e);
        }

        return $paths;
    }

    /**
     * Scan a specific location for component classes.
     *
     * @param string $location Directory path to scan
     * @return array<string, array<string>> Discovered components by type
     */
    protected function scanLocationForComponents(string $location): array
    {
        if (!is_dir($location)) {
            $this->logLocationSkipped($location, 'Directory does not exist');
            return [];
        }

        $components = [
            'resources' => [],
            'pages'     => [],
            'widgets'   => []
        ];

        try {
            $files = $this->getPhpFilesInLocation($location);

            foreach ($files as $file) {
                $discovered = $this->scanFileForComponents($file);
                $components = $this->mergeComponents($components, $discovered);
            }

        } catch (Throwable $e) {
            $this->logLocationError($location, $e);
            $this->scanStats['errors']++;

            if (!$this->shouldFailSilently()) {
                throw new ScanException("Failed to scan location {$location}: {$e->getMessage()}", 0, $e);
            }
        }

        return $components;
    }

    /**
     * Scan a single PHP file for component classes.
     *
     * @param string $filePath Path to PHP file
     * @return array<string, array<string>> Component classes found in file by type
     */
    protected function scanFileForComponents(string $filePath): array
    {
        $this->scanStats['files_scanned']++;

        try {
            $classes = $this->extractClassesFromFile($filePath);
            $this->scanStats['classes_found'] += count($classes);

            $components = [
                'resources' => [],
                'pages'     => [],
                'widgets'   => []
            ];

            foreach ($classes as $className) {
                foreach ($this->componentTypeMap as $type => $attributeClass) {
                    if ($this->isComponentType($className, $type)) {
                        $components[$type][] = $className;
                        break; // A class can only be one component type
                    }
                }
            }

            return $components;

        } catch (Throwable $e) {
            $this->logFileError($filePath, $e);
            $this->scanStats['errors']++;

            if (!$this->shouldFailSilently()) {
                throw new ScanException("Failed to scan file {$filePath}: {$e->getMessage()}", 0, $e);
            }

            return [
                'resources' => [],
                'pages'     => [],
                'widgets'   => []
            ];
        }
    }

    /**
     * Extract class names from a PHP file using token parsing.
     *
     * @param string $filePath Path to PHP file
     * @return array<string> Array of fully qualified class names
     */
    protected function extractClassesFromFile(string $filePath): array
    {
        $content = File::get($filePath);
        $tokens = token_get_all($content);
        $classes = [];

        $namespace = '';
        $className = '';
        $braceLevel = 0;
        $inClass = false;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $namespace = $this->parseNamespace($tokens, $i);
                        break;

                    case T_CLASS:
                        $className = $this->parseClassName($tokens, $i);
                        if ($className) {
                            $fullyQualified = $namespace ? $namespace.'\\'.$className : $className;
                            $classes[] = $fullyQualified;
                            $inClass = true;
                        }
                        break;
                }
            } elseif ('{' === $token) {
                if ($inClass) {
                    $braceLevel++;
                }
            } elseif ('}' === $token) {
                if ($inClass) {
                    $braceLevel--;
                    if (0 === $braceLevel) {
                        $inClass = false;
                        $className = '';
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Parse namespace from tokens.
     */
    protected function parseNamespace(array $tokens, int &$index): string
    {
        $namespace = '';

        for ($i = $index + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                $namespace .= $token[1];
            } elseif (';' === $token) {
                break;
            }
        }

        return mb_trim($namespace);
    }

    /**
     * Parse class name from tokens.
     */
    protected function parseClassName(array $tokens, int &$index): string
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if (is_array($token) && T_STRING === $token[0]) {
                return $token[1];
            }
        }

        return '';
    }

    /**
     * Get PHP files in a location with filtering.
     *
     * @param string $location Directory to scan
     * @return Generator<string> Generator yielding file paths
     */
    protected function getPhpFilesInLocation(string $location): Generator
    {
        $maxDepth = $this->config['component_scan']['max_depth'] ?? 10;
        $excludedDirs = $this->config['component_scan']['excluded_directories'] ?? [];
        $followSymlinks = $this->config['component_scan']['follow_symlinks'] ?? false;

        $flags = RecursiveDirectoryIterator::SKIP_DOTS;
        if ($followSymlinks) {
            $flags |= RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($location, $flags),
                function (SplFileInfo $file) use ($excludedDirs) {
                    if ($file->isDir()) {
                        $name = $file->getFilename();

                        // Skip excluded directories
                        if (in_array($name, $excludedDirs, true) || str_starts_with($name, '.')) {
                            return false;
                        }
                    }

                    return $file->isDir() || 'php' === $file->getExtension();
                }
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        if ($maxDepth > 0) {
            $iterator->setMaxDepth($maxDepth);
        }

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && 'php' === $file->getExtension()) {
                yield $file->getPathname();
            }
        }
    }

    /**
     * Merge two component arrays.
     */
    protected function mergeComponents(array $target, array $source): array
    {
        foreach ($source as $type => $components) {
            if (!isset($target[$type])) {
                $target[$type] = [];
            }
            $target[$type] = array_merge($target[$type], $components);
        }

        return $target;
    }

    /**
     * Clean up components array by removing duplicates.
     */
    protected function cleanupComponents(array $components): array
    {
        foreach ($components as $type => $classList) {
            $components[$type] = array_values(array_unique($classList));
        }

        return $components;
    }

    /**
     * Count total components across all types.
     */
    protected function countTotalComponents(array $components): int
    {
        $total = 0;
        foreach ($components as $type => $classList) {
            $total += count($classList);
        }
        return $total;
    }

    /**
     * Reset scan statistics.
     */
    protected function resetScanStats(): void
    {
        $this->scanStats = [
            'files_scanned'         => 0,
            'classes_found'         => 0,
            'components_discovered' => 0,
            'scan_time'             => 0,
            'errors'                => 0,
        ];
    }

    /**
     * Check if scanner should fail silently.
     */
    protected function shouldFailSilently(): bool
    {
        return $this->config['error_handling']['fail_silently'] ?? false;
    }

    // Logging methods...
    protected function logScanStart(string $panelName): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->info("Starting component discovery for panel: {$panelName}");
        }
    }

    protected function logScanComplete(string $panelName, array $components): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            $total = $this->countTotalComponents($components);
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->info("Component discovery completed for panel: {$panelName}", [
                    'components_found' => $total,
                    'scan_time'        => $this->scanStats['scan_time'],
                    'files_scanned'    => $this->scanStats['files_scanned']
                ]);
        }
    }

    protected function logScanError(string $panelName, Throwable $e): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->error("Component discovery failed for panel: {$panelName}", [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine()
                ]);
        }
    }

    protected function logLocationSkipped(string $location, string $reason): void
    {
        if (($this->config['logging']['enabled'] ?? false) && ($this->config['development']['verbose_logging'] ?? false)) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->debug("Skipped component scan location: {$location}", ['reason' => $reason]);
        }
    }

    protected function logLocationError(string $location, Throwable $e): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->warning("Component scan location error: {$location}", [
                    'error' => $e->getMessage()
                ]);
        }
    }

    protected function logFileError(string $filePath, Throwable $e): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->warning("Component scan file error: {$filePath}", [
                    'error' => $e->getMessage()
                ]);
        }
    }

    protected function logComponentCheckError(string $className, string $componentType, Throwable $e): void
    {
        if (($this->config['logging']['enabled'] ?? false) && ($this->config['development']['verbose_logging'] ?? false)) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->debug("Component type check error: {$className}", [
                    'component_type' => $componentType,
                    'error'          => $e->getMessage()
                ]);
        }
    }

    protected function logPatternExpansionError(string $pattern, Throwable $e): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            Log::channel($this->config['logging']['channel'] ?? 'default')
                ->warning("Pattern expansion error: {$pattern}", [
                    'error' => $e->getMessage()
                ]);
        }
    }
}
