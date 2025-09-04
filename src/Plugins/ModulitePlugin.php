<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Log;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use Throwable;

/**
 * ModulitePlugin - Automatically discovers and registers Filament components.
 *
 * This plugin provides automatic discovery and registration of:
 * - Resources from modules/{ModuleName}/Filament/{PanelName}/Resources
 * - Pages from modules/{ModuleName}/Filament/{PanelName}/Pages
 * - Widgets from modules/{ModuleName}/Filament/{PanelName}/Widgets
 *
 * Features:
 * - Directory-structure based discovery (no attributes needed)
 * - Multi-layer caching for optimal performance
 * - Production vs development optimizations
 * - Graceful error handling and optional logging
 * - Smart validation and duplicate prevention
 *
 * Usage:
 * ```php
 * // Simple usage - all configuration through config/modulite.php
 * $panel->plugin(ModulitePlugin::make());
 * ```
 *
 * Directory Structure:
 * - modules/{Module}/Filament/{Panel}/Resources/
 * - modules/{Module}/Filament/{Panel}/Pages/
 * - modules/{Module}/Filament/{Panel}/Widgets/
 *
 * @package PanicDevs\Modulite\Plugins
 */
class ModulitePlugin implements Plugin
{
    /**
     * Plugin configuration options.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Component scanner service.
     */
    protected ?ComponentScannerInterface $componentScanner = null;

    /**
     * Cache manager service.
     */
    protected ?CacheManagerInterface $cacheManager = null;

    /**
     * Static cache for repeated requests to avoid service container lookups.
     * @var array<string, mixed>
     */
    protected static array $staticCache = [];

    /**
     * Flag to track if discovery has been performed for this panel.
     * @var array<string, bool>
     */
    protected static array $discoveredPanels = [];

    /**
     * Clear all static caches (useful for testing or cache invalidation).
     */
    public static function clearStaticCaches(): void
    {
        static::$staticCache      = [];
        static::$discoveredPanels = [];
    }

    /**
     * Get the plugin identifier.
     */
    public function getId(): string
    {
        return 'modulite';
    }

    /**
     * Create a new plugin instance.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Register the plugin with a panel.
     *
     * This is where the magic happens - components are discovered and registered.
     * Optimized for performance with early returns and static caching.
     */
    public function register(Panel $panel): void
    {
        // Fast path: check if discovery should be performed
        if (!$this->shouldPerformDiscovery())
        {
            return;
        }

        try
        {
            $panelId = $this->getPanelId($panel);

            // Fast path: avoid duplicate discovery for the same panel
            if (isset(static::$discoveredPanels[$panelId]))
            {
                return;
            }

            // Mark panel as discovered to prevent duplicate work
            static::$discoveredPanels[$panelId] = true;

            // Skip attribute-based configuration - use pure discovery
            // $this->applyPanelConfigurationOptimized($panel);

            // Discover components with enhanced caching
            $components = $this->discoverComponentsOptimized($panelId);

            // Register components with minimal overhead
            $this->registerComponentsOptimized($panel, $components);

            // Only log in development mode
            if (app()->hasDebugModeEnabled())
            {
                $this->logRegistrationSuccess($panelId, $components);
            }

        } catch (Throwable $e)
        {
            $this->handleRegistrationError($panel, $e);
        }
    }

    /**
     * Boot the plugin (called after registration).
     */
    public function boot(Panel $panel): void
    {
        // Additional boot logic if needed
        $this->logPluginBooted($panel);
    }

    // Configuration methods removed - use config file instead for better performance

    /**
     * Optimized component discovery with multi-layer caching.
     */
    protected function discoverComponentsOptimized(string $panelId): array
    {
        // Layer 1: Static cache for current request
        $staticKey = "components_{$panelId}";
        if (isset(static::$staticCache[$staticKey]))
        {
            return static::$staticCache[$staticKey];
        }

        // Layer 2: Persistent cache
        $cacheKey = "components.{$panelId}";

        if ($this->isCachingEnabled())
        {
            $components = $this->getCacheManager()->remember($cacheKey, fn () => $this->performComponentDiscovery($panelId));
        } else
        {
            $components = $this->performComponentDiscovery($panelId);
        }

        // Store in static cache for this request
        static::$staticCache[$staticKey] = $components;

        return $components;
    }

    /**
     * Legacy method for backward compatibility.
     *
     * @deprecated Use discoverComponentsOptimized instead
     */
    protected function discoverComponents(string $panelId): array
    {
        return $this->discoverComponentsOptimized($panelId);
    }

    /**
     * Perform the actual component discovery.
     */
    protected function performComponentDiscovery(string $panelId): array
    {
        $scanner = $this->getComponentScanner();
        return $scanner->discoverComponentsForPanel($panelId);
    }

    /**
     * Optimized component registration with minimal overhead.
     */
    protected function registerComponentsOptimized(Panel $panel, array $components): void
    {
        // Fast path: early return if no components
        if (empty($components))
        {
            return;
        }

        // Cache enabled types to avoid repeated config lookups
        static $enabledTypesCache = null;
        if (null === $enabledTypesCache)
        {
            $enabledTypesCache = $this->getEnabledComponentTypes();
        }

        // Process each component type efficiently
        foreach ($components as $type => $classList)
        {
            // Fast path: skip disabled types
            if (empty($classList) || !($enabledTypesCache[$type] ?? true))
            {
                continue;
            }

            // Skip expensive operations if not needed
            $finalComponents = $this->processComponentsForRegistration($classList, $type);

            if (!empty($finalComponents))
            {
                $this->registerComponentType($panel, $type, $finalComponents);
            }
        }
    }

    /**
     * Legacy method for backward compatibility.
     *
     * @deprecated Use registerComponentsOptimized instead
     */
    protected function registerComponents(Panel $panel, array $components): void
    {
        $this->registerComponentsOptimized($panel, $components);
    }

    /**
     * Process components for registration with optional sorting and validation.
     */
    protected function processComponentsForRegistration(array $classList, string $type): array
    {
        // Skip processing if validation and sorting are disabled
        $needsValidation = $this->isValidationEnabled();
        $needsSorting    = ($this->options['sort_by'] ?? 'none') !== 'none';

        if (!$needsValidation && !$needsSorting)
        {
            return $classList;
        }

        $components = $classList;

        // Apply sorting only if needed
        if ($needsSorting)
        {
            $components = $this->sortComponents($components, $type);
        }

        // Apply validation only if enabled
        if ($needsValidation)
        {
            $components = $this->validateComponentsIfEnabled($components, $type);
        }

        return $components;
    }

    /**
     * Register a specific component type with the panel.
     */
    protected function registerComponentType(Panel $panel, string $type, array $components): void
    {
        if (empty($components))
        {
            return;
        }

        switch ($type)
        {
            case 'resources':
                $panel->resources($components);
                break;
            case 'pages':
                $panel->pages($components);
                break;
            case 'widgets':
                $panel->widgets($components);
                break;
            default:
                $this->logUnknownComponentType($type);
        }
    }

    /**
     * Sort components based on configuration.
     */
    protected function sortComponents(array $components, string $type): array
    {
        $sortBy = $this->options['sort_by'] ?? config('modulite.components.registration.sort_by', 'name');

        switch ($sortBy)
        {
            case 'name':
                sort($components);
                break;
            case 'priority':
                // Sort by priority if components have priority methods
                usort($components, [$this, 'compareComponentPriority']);
                break;
            case 'none':
            default:
                // No sorting
                break;
        }

        return $components;
    }

    /**
     * Compare component priority for sorting.
     */
    protected function compareComponentPriority(string $a, string $b): int
    {
        $priorityA = $this->getComponentPriority($a);
        $priorityB = $this->getComponentPriority($b);

        return $priorityB <=> $priorityA; // Higher priority first
    }

    /**
     * Get component priority (if method exists).
     */
    protected function getComponentPriority(string $className): int
    {
        try
        {
            if (method_exists($className, 'getPriority'))
            {
                return $className::getPriority();
            }
        } catch (Throwable)
        {
            // Ignore errors getting priority
        }

        return 0; // Default priority
    }

    /**
     * Validate components if enabled.
     */
    protected function validateComponentsIfEnabled(array $components, string $type): array
    {
        if (!$this->isValidationEnabled())
        {
            return $components;
        }

        $scanner         = $this->getComponentScanner();
        $validComponents = [];

        foreach ($components as $component)
        {
            if ($scanner->isComponentType($component, $type))
            {
                $validComponents[] = $component;
            } else
            {
                $this->logInvalidComponent($component, $type);
            }
        }

        return $validComponents;
    }

    /**
     * Get the panel ID from panel instance.
     */
    protected function getPanelId(Panel $panel): string
    {
        return $panel->getId();
    }

    /**
     * Check if discovery should be performed with caching.
     */
    protected function shouldPerformDiscovery(): bool
    {
        static $shouldPerform = null;

        if (null === $shouldPerform)
        {
            $shouldPerform = config('modulite.components.registration.auto_register', true);
        }

        return $shouldPerform;
    }

    /**
     * Check if caching is enabled with static caching.
     */
    protected function isCachingEnabled(): bool
    {
        static $cacheEnabled = null;

        if (null === $cacheEnabled)
        {
            $cacheEnabled = $this->options['cache_enabled']
                ?? config('modulite.cache.enabled', true);
        }

        return $cacheEnabled;
    }

    /**
     * Check if validation is enabled with static caching.
     */
    protected function isValidationEnabled(): bool
    {
        static $validationEnabled = null;

        if (null === $validationEnabled)
        {
            $validationEnabled = $this->options['validate_components']
                ?? config('modulite.components.registration.validate_before_register', false);
        }

        return $validationEnabled;
    }

    /**
     * Get enabled component types.
     */
    protected function getEnabledComponentTypes(): array
    {
        $configured = config('modulite.components.types', []);
        $override   = $this->options['component_types'] ?? [];

        $enabled = [];
        foreach ($configured as $type => $settings)
        {
            $enabled[$type] = $override[$type] ?? ($settings['enabled'] ?? true);
        }

        return $enabled;
    }

    /**
     * Get component scanner service with static caching.
     */
    protected function getComponentScanner(): ComponentScannerInterface
    {
        // Use static cache to avoid repeated service container lookups
        static $instance = null;

        if (null === $instance)
        {
            $instance = app(ComponentScannerInterface::class);
        }

        return $instance;
    }

    /**
     * Get cache manager service with static caching.
     */
    protected function getCacheManager(): CacheManagerInterface
    {
        // Use static cache to avoid repeated service container lookups
        static $instance = null;

        if (null === $instance)
        {
            $instance = app(CacheManagerInterface::class);
        }

        return $instance;
    }



    // Note: Attribute-based configuration has been removed for performance.
    // All configuration is now handled through the config file and directory structure.



    /**
     * Handle registration errors.
     */
    protected function handleRegistrationError(Panel $panel, Throwable $e): void
    {
        $panelId = $this->getPanelId($panel);

        if (config('modulite.error_handling.fail_silently', false))
        {
            $this->logRegistrationError($panelId, $e);
            return;
        }

        throw $e;
    }

    /**
     * Log successful component registration.
     */
    protected function logRegistrationSuccess(string $panelId, array $components): void
    {
        if (!config('modulite.logging.enabled', false))
        {
            return;
        }

        $totalComponents = array_sum(array_map('count', $components));

        Log::channel(config('modulite.logging.channel', 'default'))
            ->info("ModulitePlugin: Successfully registered components for panel: {$panelId}", [
                'panel_id'           => $panelId,
                'total_components'   => $totalComponents,
                'components_by_type' => array_map('count', $components)
            ]);
    }

    /**
     * Log component registration errors.
     */
    protected function logRegistrationError(string $panelId, Throwable $e): void
    {
        if (!config('modulite.logging.enabled', false))
        {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->error("ModulitePlugin: Component registration failed for panel: {$panelId}", [
                'panel_id' => $panelId,
                'error'    => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine()
            ]);
    }

    /**
     * Log plugin boot completion.
     */
    protected function logPluginBooted(Panel $panel): void
    {
        if (!config('modulite.logging.enabled', false) || !app()->hasDebugModeEnabled())
        {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->debug("ModulitePlugin: Plugin booted for panel: {$panel->getId()}");
    }

    /**
     * Log unknown component type warnings.
     */
    protected function logUnknownComponentType(string $type): void
    {
        if (!config('modulite.logging.enabled', false))
        {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->warning("ModulitePlugin: Unknown component type: {$type}");
    }

    /**
     * Log invalid component discoveries.
     */
    protected function logInvalidComponent(string $component, string $type): void
    {
        if (!config('modulite.logging.enabled', false) || !app()->hasDebugModeEnabled())
        {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->debug("ModulitePlugin: Invalid component for type {$type}: {$component}");
    }


}
