<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Attributes;

use Attribute;

/**
 * FilamentPanel attribute for marking classes as Filament Panel Providers.
 *
 * This attribute is used to mark classes that should be automatically
 * discovered and registered as Filament Panel Providers by Modulite.
 *
 * The attribute can be applied to any class that extends Filament's
 * PanelProvider or implements panel configuration logic.
 *
 * Usage Examples:
 *
 * ```php
 * use PanicDevs\Modulite\Attributes\FilamentPanel;
 * use Filament\Panel;
 * use Filament\PanelProvider;
 *
 * #[FilamentPanel]
 * class AdminPanelProvider extends PanelProvider
 * {
 *     public function panel(Panel $panel): Panel
 *     {
 *         return $panel
 *             ->default()
 *             ->id('admin')
 *             ->path('/admin');
 *     }
 * }
 * ```
 *
 * Advanced Usage with Configuration:
 *
 * ```php
 * #[FilamentPanel(priority: 10, environment: 'production')]
 * class ProductionPanelProvider extends PanelProvider
 * {
 *     // Panel configuration...
 * }
 * ```
 *
 * Requirements:
 * - Class must be instantiable (not abstract or interface)
 * - Class should extend Filament's PanelProvider or provide panel configuration
 * - Class must be autoloadable through PSR-4 standards
 *
 * Discovery Process:
 * 1. Modulite scans configured directories for PHP files
 * 2. Extracts class names using token parsing
 * 3. Uses reflection to check for this attribute
 * 4. Registers matching classes as panel providers
 *
 * Performance Considerations:
 * - Attribute detection uses reflection, which is cached for performance
 * - Classes are only loaded when the attribute is confirmed to exist
 * - File scanning is optimized with configurable depth limits and exclusions
 *
 * @package PanicDevs\Modulite\Attributes
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class FilamentPanel
{
    /**
     * Create a new FilamentPanel attribute instance.
     *
     * @param int $priority Priority for panel registration (higher = earlier registration)
     * @param string|null $environment Limit panel to specific environment(s)
     * @param array<string> $conditions Additional conditions for panel loading
     * @param bool $autoRegister Whether to automatically register this panel (default: true)
     */
    public function __construct(
        public int     $priority = 0,
        public ?string $environment = null,
        public array   $conditions = [],
        public bool    $autoRegister = true,
    ) {
    }

    /**
     * Check if this panel should be registered in the current environment.
     *
     * @param string $currentEnvironment Current application environment
     * @return bool True if panel should be registered
     */
    public function shouldRegister(string $currentEnvironment): bool
    {
        if (!$this->autoRegister) {
            return false;
        }

        if (null !== $this->environment && $this->environment !== $currentEnvironment) {
            return false;
        }

        // Additional condition checks could be implemented here
        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a registration condition.
     *
     * This method can be extended to support complex registration logic
     * based on application state, configuration, or other factors.
     *
     * @param string $condition Condition to evaluate
     * @return bool True if condition is met
     */
    protected function evaluateCondition(string $condition): bool
    {
        // For now, this is a placeholder for future condition evaluation
        // Could support things like:
        // - config('app.feature_flags.admin_panel')
        // - class_exists('SomeRequiredClass')
        // - function_exists('some_required_function')

        return true;
    }

    /**
     * Get attribute configuration as array.
     *
     * @return array<string, mixed> Attribute configuration
     */
    public function toArray(): array
    {
        return [
            'priority'      => $this->priority,
            'environment'   => $this->environment,
            'conditions'    => $this->conditions,
            'auto_register' => $this->autoRegister,
        ];
    }
}
