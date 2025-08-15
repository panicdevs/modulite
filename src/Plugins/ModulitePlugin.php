<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Log;
use PanicDevs\Modulite\Attributes\ComponentDiscovery;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use ReflectionClass;
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
 * - Configurable scan locations with pattern support
 * - Multi-layer caching for performance
 * - Development vs production optimizations
 * - Comprehensive error handling and logging
 * - Component validation and sorting
 *
 * Usage:
 * ```php
 * $panel->plugin(ModulitePlugin::make());
 *
 * // Or with custom configuration
 * $panel->plugin(
 *     ModulitePlugin::make()
 *         ->enableCaching(false)
 *         ->sortComponentsBy('priority')
 * );
 * ```
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
     */
    public function register(Panel $panel): void
    {
        if (!$this->shouldPerformDiscovery()) {
            return;
        }

        try {
            $panelId = $this->getPanelId($panel);

            // Apply panel-specific configuration from attributes
            $this->applyPanelConfiguration($panel);

            $components = $this->discoverComponents($panelId);

            $this->registerComponents($panel, $components);
            $this->logRegistrationSuccess($panelId, $components);

        } catch (Throwable $e) {
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

    /**
     * Enable or disable caching for component discovery.
     *
     * @param bool $enabled Whether to enable caching
     * @return static
     */
    public function enableCaching(bool $enabled = true): static
    {
        $this->options['cache_enabled'] = $enabled;
        return $this;
    }

    /**
     * Set how components should be sorted before registration.
     *
     * @param string $sortBy Sorting method: 'name', 'priority', or 'none'
     * @return static
     */
    public function sortComponentsBy(string $sortBy): static
    {
        $this->options['sort_by'] = $sortBy;
        return $this;
    }

    /**
     * Enable or disable component validation.
     *
     * @param bool $enabled Whether to validate components
     * @return static
     */
    public function validateComponents(bool $enabled = true): static
    {
        $this->options['validate_components'] = $enabled;
        return $this;
    }

    /**
     * Set custom scan locations for this plugin instance.
     *
     * @param array<string> $locations Custom scan locations
     * @return static
     */
    public function scanLocations(array $locations): static
    {
        $this->options['scan_locations'] = $locations;
        return $this;
    }

    /**
     * Enable or disable specific component types.
     *
     * @param array<string, bool> $types Component types to enable/disable
     * @return static
     */
    public function componentTypes(array $types): static
    {
        $this->options['component_types'] = $types;
        return $this;
    }

    /**
     * Set excluded directories for scanning.
     *
     * @param array<string> $excludedDirs Directories to exclude
     * @return static
     */
    public function excludeDirectories(array $excludedDirs): static
    {
        $this->options['excluded_directories'] = $excludedDirs;
        return $this;
    }

    /**
     * Discover components for a panel.
     *
     * Uses caching when enabled for performance optimization.
     * Also checks for panel-specific configuration from attributes.
     */
    protected function discoverComponents(string $panelId): array
    {
        $cacheKey = "components.{$panelId}";

        if ($this->isCachingEnabled()) {
            return $this->getCacheManager()->remember($cacheKey, fn () => $this->performComponentDiscovery($panelId));
        }

        return $this->performComponentDiscovery($panelId);
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
     * Register discovered components with the panel.
     */
    protected function registerComponents(Panel $panel, array $components): void
    {
        $enabledTypes = $this->getEnabledComponentTypes();

        foreach ($components as $type => $classList) {
            if (!isset($enabledTypes[$type]) || !$enabledTypes[$type]) {
                continue;
            }

            $sortedComponents = $this->sortComponents($classList, $type);
            $validatedComponents = $this->validateComponentsIfEnabled($sortedComponents, $type);

            $this->registerComponentType($panel, $type, $validatedComponents);
        }
    }

    /**
     * Register a specific component type with the panel.
     */
    protected function registerComponentType(Panel $panel, string $type, array $components): void
    {
        if (empty($components)) {
            return;
        }

        switch ($type) {
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

        switch ($sortBy) {
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
        try {
            if (method_exists($className, 'getPriority')) {
                return $className::getPriority();
            }
        } catch (Throwable) {
            // Ignore errors getting priority
        }

        return 0; // Default priority
    }

    /**
     * Validate components if enabled.
     */
    protected function validateComponentsIfEnabled(array $components, string $type): array
    {
        if (!$this->isValidationEnabled()) {
            return $components;
        }

        $scanner = $this->getComponentScanner();
        $validComponents = [];

        foreach ($components as $component) {
            if ($scanner->isComponentType($component, $type)) {
                $validComponents[] = $component;
            } else {
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
     * Check if discovery should be performed.
     */
    protected function shouldPerformDiscovery(): bool
    {
        return config('modulite.components.registration.auto_register', true);
    }

    /**
     * Check if caching is enabled.
     */
    protected function isCachingEnabled(): bool
    {
        return $this->options['cache_enabled']
            ?? config('modulite.cache.enabled', true);
    }

    /**
     * Check if validation is enabled.
     */
    protected function isValidationEnabled(): bool
    {
        return $this->options['validate_components']
            ?? config('modulite.components.registration.validate_before_register', false);
    }

    /**
     * Get enabled component types.
     */
    protected function getEnabledComponentTypes(): array
    {
        $configured = config('modulite.components.types', []);
        $override = $this->options['component_types'] ?? [];

        $enabled = [];
        foreach ($configured as $type => $settings) {
            $enabled[$type] = $override[$type] ?? ($settings['enabled'] ?? true);
        }

        return $enabled;
    }

    /**
     * Get component scanner service.
     */
    protected function getComponentScanner(): ComponentScannerInterface
    {
        if (!$this->componentScanner) {
            $this->componentScanner = app(ComponentScannerInterface::class);
        }

        return $this->componentScanner;
    }

    /**
     * Get cache manager service.
     */
    protected function getCacheManager(): CacheManagerInterface
    {
        if (!$this->cacheManager) {
            $this->cacheManager = app(CacheManagerInterface::class);
        }

        return $this->cacheManager;
    }

    /**
     * Apply panel-specific configuration from ComponentDiscovery attribute.
     *
     * Looks for the ComponentDiscovery attribute on panel provider classes
     * and applies the configuration to override defaults.
     */
    protected function applyPanelConfiguration(Panel $panel): void
    {
        try {
            // Try to find the panel provider class
            $panelProvider = $this->findPanelProvider($panel);

            if (!$panelProvider) {
                return; // No provider found, use defaults
            }

            $reflection = new ReflectionClass($panelProvider);
            $attributes = $reflection->getAttributes(ComponentDiscovery::class);

            if (empty($attributes)) {
                return; // No ComponentDiscovery attribute, use defaults
            }

            $discoveryAttribute = $attributes[0]->newInstance();
            $panelId = $this->getPanelId($panel);

            // Apply attribute configuration to plugin options
            $this->applyAttributeConfiguration($discoveryAttribute, $panelId);

        } catch (Throwable $e) {
            // Log error but don't fail - continue with defaults
            $this->logConfigurationError($panel, $e);
        }
    }

    /**
     * Find the panel provider class for a panel.
     *
     * This is a best-effort attempt to find the provider class.
     */
    protected function findPanelProvider(Panel $panel): ?string
    {
        // Try common panel provider naming patterns
        $panelId = $this->getPanelId($panel);
        $patterns = [
            ucfirst($panelId).'PanelProvider',
            ucfirst($panelId).'Panel',
            'App\\Providers\\Filament\\'.ucfirst($panelId).'PanelProvider',
            'App\\Providers\\'.ucfirst($panelId).'PanelProvider',
        ];

        foreach ($patterns as $pattern) {
            if (class_exists($pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Apply configuration from ComponentDiscovery attribute.
     */
    protected function applyAttributeConfiguration(ComponentDiscovery $attribute, string $panelId): void
    {
        // Apply scan locations
        if (null !== $attribute->locations) {
            $this->options['scan_locations'] = $attribute->getLocations($panelId);
        }

        // Apply enabled types
        if (null !== $attribute->enabledTypes) {
            $this->options['component_types'] = $attribute->getEnabledTypes();
        }

        // Apply excluded directories
        if (null !== $attribute->excludedDirectories) {
            $this->options['excluded_directories'] = $attribute->getExcludedDirectories();
        }

        // Apply cache setting
        if (null !== $attribute->cacheEnabled) {
            $this->options['cache_enabled'] = $attribute->isCacheEnabled();
        }

        // Apply sort setting
        if (null !== $attribute->sortBy) {
            $this->options['sort_by'] = $attribute->getSortBy();
        }

        // Apply validation setting
        if (null !== $attribute->validateComponents) {
            $this->options['validate_components'] = $attribute->isValidationEnabled();
        }

        // Apply additional options
        $additionalOptions = $attribute->getOptions();
        $this->options = array_merge($this->options, $additionalOptions);
    }

    /**
     * Handle registration errors.
     */
    protected function handleRegistrationError(Panel $panel, Throwable $e): void
    {
        $panelId = $this->getPanelId($panel);

        if (config('modulite.error_handling.fail_silently', false)) {
            $this->logRegistrationError($panelId, $e);
            return;
        }

        throw $e;
    }

    // Logging methods...
    protected function logRegistrationSuccess(string $panelId, array $components): void
    {
        if (!config('modulite.logging.enabled', false)) {
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

    protected function logRegistrationError(string $panelId, Throwable $e): void
    {
        if (!config('modulite.logging.enabled', false)) {
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

    protected function logPluginBooted(Panel $panel): void
    {
        if (!config('modulite.logging.enabled', false) || !config('modulite.development.verbose_logging', false)) {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->debug("ModulitePlugin: Plugin booted for panel: {$panel->getId()}");
    }

    protected function logUnknownComponentType(string $type): void
    {
        if (!config('modulite.logging.enabled', false)) {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->warning("ModulitePlugin: Unknown component type: {$type}");
    }

    protected function logInvalidComponent(string $component, string $type): void
    {
        if (!config('modulite.logging.enabled', false) || !config('modulite.development.verbose_logging', false)) {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->debug("ModulitePlugin: Invalid component for type {$type}: {$component}");
    }

    protected function logConfigurationError(Panel $panel, Throwable $e): void
    {
        if (!config('modulite.logging.enabled', false)) {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->warning("ModulitePlugin: Failed to apply panel configuration for panel: {$panel->getId()}", [
                'panel_id' => $panel->getId(),
                'error'    => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine()
            ]);
    }
}
