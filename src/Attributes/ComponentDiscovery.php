<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Attributes;

use Attribute;

/**
 * ComponentDiscovery attribute for configuring component discovery on Panel Providers.
 *
 * This attribute allows panel providers to override the default component discovery
 * configuration for their specific panel. When applied to a panel provider class,
 * it will customize how components are discovered and registered for that panel.
 *
 * Usage:
 * ```php
 * #[ComponentDiscovery(
 *     locations: ['path-to-your-locations'],
 *     enabledTypes: ['resources' => true, 'pages' => false, 'widgets' => true],
 *     cacheEnabled: false,
 *     sortBy: 'priority'
 * )]
 * class AdminPanelProvider extends PanelProvider
 * {
 *     // Panel implementation
 * }
 *
 * // Minimal configuration
 * #[ComponentDiscovery(
 *     enabledTypes: ['resources' => true, 'pages' => false, 'widgets' => false]
 * )]
 * class ApiPanelProvider extends PanelProvider
 * {
 *     // Only resources, no pages or widgets
 * }
 * ```
 *
 * @package PanicDevs\Modulite\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ComponentDiscovery
{
    /**
     * Create a new ComponentDiscovery attribute.
     *
     * @param array<string>|null $locations Custom scan locations for this panel. Null = use default
     * @param array<string, bool>|null $enabledTypes Component types to enable/disable. Null = use default
     * @param array<string>|null $excludedDirectories Directories to exclude from scanning. Null = use default
     * @param bool|null $cacheEnabled Enable/disable caching for this panel. Null = use default
     * @param string|null $sortBy How to sort components ('name', 'priority', 'none'). Null = use default
     * @param bool|null $validateComponents Enable/disable component validation. Null = use default
     * @param int|null $maxDepth Maximum scan depth. Null = use default
     * @param bool|null $followSymlinks Whether to follow symlinks. Null = use default
     * @param array<string, mixed> $options Additional custom options
     */
    public function __construct(
        public ?array $locations = null,
        public ?array $enabledTypes = null,
        public ?array $excludedDirectories = null,
        public ?bool $cacheEnabled = null,
        public ?string $sortBy = null,
        public ?bool $validateComponents = null,
        public ?int $maxDepth = null,
        public ?bool $followSymlinks = null,
        public array $options = []
    ) {
    }

    /**
     * Get scan locations for this panel.
     *
     * @param string $panelId Panel identifier for placeholder replacement
     * @param array<string> $defaultLocations Default locations from config
     * @return array<string> Resolved scan locations
     */
    public function getLocations(string $panelId, array $defaultLocations = []): array
    {
        $locations = $this->locations ?? $defaultLocations;

        // Replace {PanelName} placeholder with actual panel ID
        return array_map(
            fn(string $location) => str_replace('{PanelName}', $panelId, $location),
            $locations
        );
    }

    /**
     * Get enabled component types.
     *
     * @param array<string, bool> $defaultTypes Default types from config
     * @return array<string, bool> Resolved enabled types
     */
    public function getEnabledTypes(array $defaultTypes = []): array
    {
        if (null === $this->enabledTypes) {
            return $defaultTypes;
        }

        // Merge with defaults, allowing overrides
        return array_merge($defaultTypes, $this->enabledTypes);
    }

    /**
     * Get excluded directories.
     *
     * @param array<string> $defaultExcluded Default excluded directories from config
     * @return array<string> Resolved excluded directories
     */
    public function getExcludedDirectories(array $defaultExcluded = []): array
    {
        return $this->excludedDirectories ?? $defaultExcluded;
    }

    /**
     * Check if caching is enabled.
     *
     * @param bool $defaultEnabled Default cache setting from config
     * @return bool Whether caching is enabled
     */
    public function isCacheEnabled(bool $defaultEnabled = true): bool
    {
        return $this->cacheEnabled ?? $defaultEnabled;
    }

    /**
     * Get sort method.
     *
     * @param string $defaultSort Default sort method from config
     * @return string Sort method ('name', 'priority', 'none')
     */
    public function getSortBy(string $defaultSort = 'name'): string
    {
        return $this->sortBy ?? $defaultSort;
    }

    /**
     * Check if component validation is enabled.
     *
     * @param bool $defaultValidation Default validation setting from config
     * @return bool Whether validation is enabled
     */
    public function isValidationEnabled(bool $defaultValidation = false): bool
    {
        return $this->validateComponents ?? $defaultValidation;
    }

    /**
     * Get maximum scan depth.
     *
     * @param int $defaultDepth Default max depth from config
     * @return int Maximum scan depth
     */
    public function getMaxDepth(int $defaultDepth = 10): int
    {
        return $this->maxDepth ?? $defaultDepth;
    }

    /**
     * Check if symlinks should be followed.
     *
     * @param bool $defaultFollow Default symlink following setting from config
     * @return bool Whether to follow symlinks
     */
    public function shouldFollowSymlinks(bool $defaultFollow = false): bool
    {
        return $this->followSymlinks ?? $defaultFollow;
    }

    /**
     * Get additional options.
     *
     * @return array<string, mixed> Additional options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Convert attribute to configuration array.
     *
     * @param string $panelId Panel identifier
     * @param array<string, mixed> $defaultConfig Default configuration
     * @return array<string, mixed> Resolved configuration
     */
    public function toConfig(string $panelId, array $defaultConfig = []): array
    {
        $config = $defaultConfig;

        // Override scan locations
        if (null !== $this->locations) {
            $config['component_scan']['locations'] = $this->getLocations($panelId, []);
        }

        // Override enabled types
        if (null !== $this->enabledTypes) {
            foreach ($this->enabledTypes as $type => $enabled) {
                $config['component_scan']['types'][$type]['enabled'] = $enabled;
            }
        }

        // Override other settings
        if (null !== $this->excludedDirectories) {
            $config['component_scan']['excluded_directories'] = $this->excludedDirectories;
        }

        if (null !== $this->cacheEnabled) {
            $config['cache']['enabled'] = $this->cacheEnabled;
        }

        if (null !== $this->sortBy) {
            $config['component_scan']['registration']['sort_by'] = $this->sortBy;
        }

        if (null !== $this->validateComponents) {
            $config['component_scan']['registration']['validate_components'] = $this->validateComponents;
        }

        if (null !== $this->maxDepth) {
            $config['component_scan']['max_depth'] = $this->maxDepth;
        }

        if (null !== $this->followSymlinks) {
            $config['component_scan']['follow_symlinks'] = $this->followSymlinks;
        }

        // Merge additional options
        $config = array_merge_recursive($config, $this->options);

        return $config;
    }
}
