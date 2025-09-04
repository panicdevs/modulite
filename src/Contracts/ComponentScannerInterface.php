<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Contracts;

/**
 * Interface for Modulite component discovery services.
 *
 * This interface defines the contract for scanning and discovering
 * Filament components (Resources, Pages, Widgets) across modules.
 *
 * @package PanicDevs\Modulite\Contracts
 */
interface ComponentScannerInterface
{
    /**
     * Discover all Filament components in configured scan locations.
     *
     * This method should:
     * - Scan all configured locations for PHP files
     * - Parse files to extract class names
     * - Use reflection to check for Filament component types
     * - Return organized array of discovered components by type
     * - Handle errors gracefully based on configuration
     *
     * @param string $panelName Panel name to scan components for
     * @return array<string, array<string>> Array of components organized by type
     * [
     *     'resources' => ['App\Modules\User\Filament\Admin\Resources\UserResource'],
     *     'pages' => ['App\Modules\User\Filament\Admin\Pages\Dashboard'],
     *     'widgets' => ['App\Modules\User\Filament\Admin\Widgets\StatsWidget']
     * ]
     *
     *
     */
    public function discoverComponents(string $panelName): array;

    /**
     * Discover components of a specific type.
     *
     * @param string $panelName Panel name to scan for
     * @param string $componentType Component type ('resources', 'pages', 'widgets')
     * @return array<string> Array of fully qualified class names
     *
     *
     */
    public function discoverComponentType(string $panelName, string $componentType): array;

    /**
     * Get statistics from the last scan operation.
     *
     * Returns metrics useful for debugging and performance monitoring:
     * - files_scanned: Number of PHP files examined
     * - classes_found: Total classes discovered in scanned files
     * - components_discovered: Classes identified as Filament components
     * - scan_time: Time taken for the scan in seconds
     * - errors: Number of errors encountered during scan
     *
     * @return array<string, mixed> Scan statistics and metrics
     */
    public function getScanStats(): array;

    /**
     * Check if a class is a valid Filament component of the specified type.
     *
     * @param string $className Fully qualified class name
     * @param string $componentType Component type to check for
     * @return bool True if class is valid component type
     */
    public function isComponentType(string $className, string $componentType): bool;
}
