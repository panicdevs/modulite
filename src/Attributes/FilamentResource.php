<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Attributes;

use Attribute;

/**
 * FilamentResource attribute for marking classes as Filament Resources.
 *
 * This attribute allows automatic discovery of Filament Resources regardless
 * of their inheritance hierarchy. Use this when extending custom base classes
 * instead of the standard Filament\Resources\Resource.
 *
 * Usage:
 * ```php
 * #[FilamentResource]
 * class UserResource extends MyCustomResource
 * {
 *     // Resource implementation
 * }
 *
 * // With priority for sorting
 * #[FilamentResource(priority: 100)]
 * class AdminUserResource extends MyCustomResource
 * {
 *     // Higher priority resource
 * }
 *
 * // Disable auto-discovery for specific resource
 * #[FilamentResource(enabled: false)]
 * class InternalResource extends MyCustomResource
 * {
 *     // This won't be auto-discovered
 * }
 * ```
 *
 * @package PanicDevs\Modulite\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class FilamentResource
{
    /**
     * Create a new FilamentResource attribute.
     *
     * @param int $priority Priority for sorting (higher = first). Default: 0
     * @param bool $enabled Whether this resource should be auto-discovered. Default: true
     * @param array<string> $panels Specific panels this resource should be registered with. Empty = all panels
     * @param array<string, mixed> $options Additional options for the resource
     */
    public function __construct(
        public int $priority = 0,
        public bool $enabled = true,
        public array $panels = [],
        public array $options = []
    ) {
    }

    /**
     * Check if this resource is enabled for auto-discovery.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if this resource should be registered with a specific panel.
     *
     * @param string $panelId Panel identifier
     * @return bool True if resource should be registered with panel
     */
    public function isEnabledForPanel(string $panelId): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // If no specific panels defined, enable for all panels
        if (empty($this->panels)) {
            return true;
        }

        return in_array($panelId, $this->panels, true);
    }

    /**
     * Get the priority for sorting.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get additional options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
