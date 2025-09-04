<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Filament\Resources\Resource;
use Filament\Pages\Page;
use Filament\Widgets\Widget;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\ModuleResolverInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Service for discovering Filament components (pages, widgets, resources) in modules.
 *
 * Provides dynamic discovery of Filament components across modules with
 * intelligent caching and validation.
 */
class ComponentDiscoveryService implements ComponentScannerInterface
{
    protected CacheManagerInterface $cache;
    protected ModuleResolverInterface $moduleResolver;
    protected array $stats = [
        'scanned_modules' => 0,
        'scanned_files'   => 0,
        'found_resources' => 0,
        'found_pages'     => 0,
        'found_widgets'   => 0,
        'scan_time'       => 0,
    ];

    public function __construct(
        CacheManagerInterface $cache = null,
        ModuleResolverInterface $moduleResolver = null
    ) {
        $this->cache          = $cache ?: app(CacheManagerInterface::class);
        $this->moduleResolver = $moduleResolver ?: app(ModuleResolverInterface::class);
    }

    /**
     * Discover components for a specific panel.
     */
    public function discoverComponentsForPanel(string $panelId): array
    {
        return $this->discoverComponents($panelId);
    }

    /**
     * Discover all Filament components in configured scan locations with optimizations.
     * Implementation of ComponentScannerInterface method.
     */
    public function discoverComponents(string $panelName): array
    {
        $cacheKey = "panel_components:{$panelName}";

        // Fast path: try cache first with minimal overhead
        $cached = $this->cache->get($cacheKey);
        if (null !== $cached)
        {
            return $cached;
        }

        // Only measure time in development
        $startTime = config('app.debug', false) ? microtime(true) : 0;

        // Discover components efficiently
        $components = $this->performOptimizedDiscovery($panelName);

        // Update stats only in development
        if (config('app.debug', false))
        {
            $this->stats['scan_time'] = microtime(true) - $startTime;
        }

        // Store the results with appropriate TTL
        $this->cache->put($cacheKey, $components);

        return $components;
    }

    /**
     * Perform optimized component discovery.
     */
    protected function performOptimizedDiscovery(string $panelName): array
    {
        // Check if any component types are enabled before scanning
        $enabledTypes = $this->getEnabledComponentTypes();

        $components = [
            'resources' => [],
            'pages'     => [],
            'widgets'   => [],
        ];

        // Only discover enabled types to avoid unnecessary work
        if ($enabledTypes['resources'] ?? true)
        {
            $components['resources'] = $this->discoverResources($panelName)->toArray();
        }

        if ($enabledTypes['pages'] ?? true)
        {
            $components['pages'] = $this->discoverPages($panelName)->toArray();
        }

        if ($enabledTypes['widgets'] ?? true)
        {
            $components['widgets'] = $this->discoverWidgets($panelName)->toArray();
        }

        return $components;
    }

    /**
     * Get enabled component types with caching.
     */
    protected function getEnabledComponentTypes(): array
    {
        static $enabledTypes = null;

        if (null === $enabledTypes)
        {
            $enabledTypes = [
                'resources' => $this->isComponentTypeEnabled('resources'),
                'pages'     => $this->isComponentTypeEnabled('pages'),
                'widgets'   => $this->isComponentTypeEnabled('widgets'),
            ];
        }

        return $enabledTypes;
    }

    /**
     * Discover components of a specific type.
     */
    public function discoverComponentType(string $panelName, string $componentType): array
    {
        return match ($componentType)
        {
            'resources' => $this->discoverResources($panelName)->toArray(),
            'pages'     => $this->discoverPages($panelName)->toArray(),
            'widgets'   => $this->discoverWidgets($panelName)->toArray(),
            default     => [],
        };
    }

    /**
     * Get statistics from the last scan operation.
     */
    public function getScanStats(): array
    {
        return $this->stats;
    }

    /**
     * Check if a class is a valid Filament component of the specified type.
     */
    public function isComponentType(string $className, string $componentType): bool
    {
        return $this->isValidComponent($className, $componentType);
    }

    /**
     * Discover all resources from enabled modules.
     */
    public function discoverResources(?string $panelId = null): Collection
    {
        return $this->discoverComponentsByType('resources', $panelId);
    }

    /**
     * Discover all pages from enabled modules.
     */
    public function discoverPages(?string $panelId = null): Collection
    {
        return $this->discoverComponentsByType('pages', $panelId);
    }

    /**
     * Discover all widgets from enabled modules.
     */
    public function discoverWidgets(?string $panelId = null): Collection
    {
        return $this->discoverComponentsByType('widgets', $panelId);
    }

    /**
     * Discover components from a specific module.
     */
    public function discoverComponentsFromModule(string $moduleName, ?string $panelId = null): array
    {
        $components = [
            'resources' => collect(),
            'pages'     => collect(),
            'widgets'   => collect(),
        ];

        $scanLocations = $this->getScanLocations($panelId);

        foreach ($scanLocations as $location)
        {
            foreach (['resources', 'pages', 'widgets'] as $type)
            {
                if (!$this->isComponentTypeEnabled($type))
                {
                    continue;
                }

                $typePath = $this->resolveComponentPath($moduleName, $location, $type, $panelId);

                if (File::isDirectory($typePath))
                {
                    $found             = $this->scanDirectoryForComponents($typePath, $moduleName, $type);
                    $components[$type] = $components[$type]->merge($found);
                }
            }
        }

        return [
            'resources' => $components['resources']->unique(),
            'pages'     => $components['pages']->unique(),
            'widgets'   => $components['widgets']->unique(),
        ];
    }

    /**
     * Check if a class is a valid Filament component - simplified without attributes.
     */
    public function isValidComponent(string $className, string $type): bool
    {
        if (!class_exists($className))
        {
            return false;
        }

        try
        {
            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait())
            {
                return false;
            }

            // Simple inheritance-based validation without attribute checking
            return match ($type)
            {
                'resources' => $this->isValidResourceComponent($reflection),
                'pages'     => $this->isValidPageComponent($reflection),
                'widgets'   => $this->isValidWidgetComponent($reflection),
                default     => false,
            };

        } catch (ReflectionException $e)
        {
            return false;
        }
    }

    /**
     * Check if a class is a valid resource component.
     * This allows for custom resource classes that might not directly extend Resource.
     */
    protected function isValidResourceComponent(ReflectionClass $reflection): bool
    {
        // First check if it's a subclass of Resource (the common case)
        if ($reflection->isSubclassOf(Resource::class))
        {
            return true;
        }

        // Check if strict inheritance is required
        if (config('modulite.components.types.resources.strict_inheritance', false))
        {
            return false;
        }

        // Check if custom base classes are allowed
        if (!config('modulite.components.types.resources.allow_custom_base_classes', true))
        {
            return false;
        }

        // Check for required resource methods (duck typing approach)
        $requiredMethods = ['getModel', 'form', 'table'];
        foreach ($requiredMethods as $method)
        {
            if (!$reflection->hasMethod($method))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a class is a valid page component.
     * This allows for custom page classes that might not directly extend Page.
     */
    protected function isValidPageComponent(ReflectionClass $reflection): bool
    {
        // First check if it's a subclass of Page (the common case)
        if ($reflection->isSubclassOf(Page::class))
        {
            return true;
        }

        // Check if strict inheritance is required
        if (config('modulite.components.types.pages.strict_inheritance', false))
        {
            return false;
        }

        // Check if custom base classes are allowed
        if (!config('modulite.components.types.pages.allow_custom_base_classes', true))
        {
            return false;
        }

        // Check for page characteristics (duck typing approach)
        // Pages typically implement Livewire Component interface
        if ($reflection->implementsInterface(\Livewire\Component::class))
        {
            return true;
        }

        // Check for common page methods
        $pageMethods   = ['render', 'mount'];
        $hasPageMethod = false;
        foreach ($pageMethods as $method)
        {
            if ($reflection->hasMethod($method))
            {
                $hasPageMethod = true;
                break;
            }
        }

        return $hasPageMethod;
    }

    /**
     * Check if a class is a valid widget component.
     * This allows for custom widget classes that might not directly extend Widget.
     */
    protected function isValidWidgetComponent(ReflectionClass $reflection): bool
    {
        // First check if it's a subclass of Widget (the common case)
        if ($reflection->isSubclassOf(Widget::class))
        {
            return true;
        }

        // Check if strict inheritance is required
        if (config('modulite.components.types.widgets.strict_inheritance', false))
        {
            return false;
        }

        // Check if custom base classes are allowed
        if (!config('modulite.components.types.widgets.allow_custom_base_classes', true))
        {
            return false;
        }

        // Check for widget characteristics (duck typing approach)
        // Widgets typically implement Livewire Component interface
        if ($reflection->implementsInterface(\Livewire\Component::class))
        {
            return true;
        }

        // Check for common widget methods
        $widgetMethods   = ['render', 'getData'];
        $hasWidgetMethod = false;
        foreach ($widgetMethods as $method)
        {
            if ($reflection->hasMethod($method))
            {
                $hasWidgetMethod = true;
                break;
            }
        }

        return $hasWidgetMethod;
    }

    /**
     * Refresh the component discovery cache.
     */
    public function refreshCache(): void
    {
        // Clear all cached data
        $this->cache->flush();
    }



    /**
     * Discover components by type across all enabled modules.
     */
    protected function discoverComponentsByType(string $type, ?string $panelId = null): Collection
    {
        $cacheKey = $panelId ? "discovered_{$type}:{$panelId}" : "discovered_{$type}";

        // Check cache first
        $cached = $this->cache->get($cacheKey);
        if (null !== $cached)
        {
            return collect($cached);
        }

        // Compute the value
        if (!$this->isComponentTypeEnabled($type))
        {
            return collect();
        }

        $components     = collect();
        $enabledModules = $this->getEnabledModules();

        $this->stats['scanned_modules'] = $enabledModules->count();

        foreach ($enabledModules as $moduleName)
        {
            $moduleComponents = $this->discoverComponentsFromModule($moduleName, $panelId);
            $components       = $components->merge($moduleComponents[$type]);
        }

        $this->stats["found_{$type}"] = $components->count();

        $uniqueComponents = $components->unique();

        // Store the result
        $this->cache->put($cacheKey, $uniqueComponents->toArray());

        return $uniqueComponents;
    }

    /**
     * Scan a directory for component classes.
     */
    protected function scanDirectoryForComponents(string $path, string $moduleName, string $type): Collection
    {
        $components = collect();
        $iterator   = $this->createDirectoryIterator($path);

        foreach ($iterator as $file)
        {
            if (!$file->isFile() || 'php' !== $file->getExtension())
            {
                continue;
            }

            $this->stats['scanned_files']++;

            $className = $this->extractClassNameFromFile($file->getPathname(), $moduleName);

            if ($className && $this->isValidComponent($className, $type))
            {
                $components->push($className);
            }
        }

        return $components;
    }

    /**
     * Create directory iterator with configuration.
     */
    protected function createDirectoryIterator(string $path): RecursiveIteratorIterator
    {
        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $filterIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            function ($current, $key, $iterator)
            {
                // Skip excluded directories
                $excludedDirs = config('modulite.components.scanning.excluded_directories', [
                    'tests', 'migrations', 'seeders', 'factories', '.git', 'node_modules', 'vendor'
                ]);

                if ($current->isDir())
                {
                    $dirName = $current->getFilename();
                    return !in_array($dirName, $excludedDirs);
                }

                // Only include PHP files
                $extensions = config('modulite.components.scanning.extensions', ['php']);
                return $current->isFile() && in_array($current->getExtension(), $extensions);
            }
        );

        return new RecursiveIteratorIterator(
            $filterIterator,
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
    }

    /**
     * Extract class name from PHP file.
     */
    protected function extractClassNameFromFile(string $filePath, string $moduleName): ?string
    {
        try
        {
            $content = File::get($filePath);

            // Extract namespace
            $namespacePattern = '/namespace\s+([^;]+);/';
            if (!preg_match($namespacePattern, $content, $namespaceMatches))
            {
                return null;
            }

            $namespace = mb_trim($namespaceMatches[1]);

            // Extract class name
            $classPattern = '/class\s+(\w+)(?:\s+extends\s+[\w\\\\]+)?(?:\s+implements\s+[\w,\s\\\\]+)?\s*{/';
            if (!preg_match($classPattern, $content, $classMatches))
            {
                return null;
            }

            $className = mb_trim($classMatches[1]);

            return $namespace.'\\'.$className;

        } catch (Throwable $e)
        {
            return null;
        }
    }

    /**
     * Get scan locations from configuration.
     */
    protected function getScanLocations(?string $panelId = null): array
    {
        $locations = config('modulite.components.locations', [
            'modules/*/Filament/{panel}/Resources',
            'modules/*/Filament/{panel}/Pages',
            'modules/*/Filament/{panel}/Widgets',
            'foundation/*/Filament/{panel}/Resources',
            'foundation/*/Filament/{panel}/Pages',
            'foundation/*/Filament/{panel}/Widgets',
        ]);

        // Replace {panel} placeholder with actual panel ID
        if ($panelId)
        {
            $locations = array_map(fn ($location) => str_replace('{panel}', Str::studly($panelId), $location), $locations);
        } else
        {
            // For global discovery, we might want to scan all panels or use a default
            $locations = array_map(fn ($location) => str_replace('{panel}', '*', $location), $locations);
        }

        return $locations;
    }

    /**
     * Resolve component path from location pattern and type.
     */
    protected function resolveComponentPath(string $moduleName, string $locationPattern, string $type, ?string $panelId = null): string
    {
        $resolved = str_replace('*', $moduleName, $locationPattern);

        if ($panelId && str_contains($resolved, '{panel}'))
        {
            $resolved = str_replace('{panel}', Str::studly($panelId), $resolved);
        }

        // If location pattern doesn't include the component type, append it
        if (!str_ends_with(mb_strtolower($resolved), mb_strtolower($type)))
        {
            $resolved = mb_rtrim($resolved, '/').'/'.Str::studly($type);
        }

        return base_path($resolved);
    }

    /**
     * Check if component type is enabled in configuration.
     */
    protected function isComponentTypeEnabled(string $type): bool
    {
        return config("modulite.components.types.{$type}.enabled", true);
    }

    /**
     * Get enabled modules using the configured module resolver.
     */
    protected function getEnabledModules(): Collection
    {
        $cacheKey = 'enabled_modules';

        // Check cache first
        $cached = $this->cache->get($cacheKey);
        if (null !== $cached)
        {
            return collect($cached);
        }

        // Get modules from the resolver
        $modules = $this->moduleResolver->getEnabledModules();

        // Store the result
        $this->cache->put($cacheKey, $modules->toArray());

        return $modules;
    }




}
