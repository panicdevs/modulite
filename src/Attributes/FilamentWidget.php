<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Attributes;

use Attribute;

/**
 * FilamentWidget attribute for marking classes as Filament Widgets.
 *
 * This attribute allows automatic discovery of Filament Widgets regardless
 * of their inheritance hierarchy. Use this when extending custom base classes
 * instead of the standard Filament\Widgets\Widget.
 *
 * Usage:
 * ```php
 * #[FilamentWidget]
 * class UserStatsWidget extends MyCustomWidget
 * {
 *     // Widget implementation
 * }
 *
 * // With priority for sorting
 * #[FilamentWidget(priority: 100)]
 * class ImportantWidget extends MyCustomWidget
 * {
 *     // Higher priority widget
 * }
 *
 * // Panel-specific widget
 * #[FilamentWidget(panels: ['admin'])]
 * class AdminOnlyWidget extends MyCustomWidget
 * {
 *     // Only for admin panel
 * }
 * ```
 *
 * @package PanicDevs\Modulite\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class FilamentWidget
{
    /**
     * Create a new FilamentWidget attribute.
     *
     * @param int $priority Priority for sorting (higher = first). Default: 0
     * @param bool $enabled Whether this widget should be auto-discovered. Default: true
     * @param array<string> $panels Specific panels this widget should be registered with. Empty = all panels
     * @param array<string, mixed> $options Additional options for the widget
     */
    public function __construct(
        public int $priority = 0,
        public bool $enabled = true,
        public array $panels = [],
        public array $options = []
    ) {
    }

    /**
     * Check if this widget is enabled for auto-discovery.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if this widget should be registered with a specific panel.
     *
     * @param string $panelId Panel identifier
     * @return bool True if widget should be registered with panel
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
