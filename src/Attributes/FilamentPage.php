<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Attributes;

use Attribute;

/**
 * FilamentPage attribute for marking classes as Filament Pages.
 *
 * This attribute allows automatic discovery of Filament Pages regardless
 * of their inheritance hierarchy. Use this when extending custom base classes
 * instead of the standard Filament\Pages\Page.
 *
 * Usage:
 * ```php
 * #[FilamentPage]
 * class Dashboard extends MyCustomPage
 * {
 *     // Page implementation
 * }
 *
 * // With priority for sorting
 * #[FilamentPage(priority: 100)]
 * class AdminDashboard extends MyCustomPage
 * {
 *     // Higher priority page
 * }
 *
 * // Panel-specific page
 * #[FilamentPage(panels: ['admin', 'manager'])]
 * class AdminOnlyPage extends MyCustomPage
 * {
 *     // Only for admin and manager panels
 * }
 * ```
 *
 * @package PanicDevs\Modulite\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class FilamentPage
{
    /**
     * Create a new FilamentPage attribute.
     *
     * @param int $priority Priority for sorting (higher = first). Default: 0
     * @param bool $enabled Whether this page should be auto-discovered. Default: true
     * @param array<string> $panels Specific panels this page should be registered with. Empty = all panels
     * @param array<string, mixed> $options Additional options for the page
     */
    public function __construct(
        public int $priority = 0,
        public bool $enabled = true,
        public array $panels = [],
        public array $options = []
    ) {
    }

    /**
     * Check if this page is enabled for auto-discovery.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if this page should be registered with a specific panel.
     *
     * @param string $panelId Panel identifier
     * @return bool True if page should be registered with panel
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
