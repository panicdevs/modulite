<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Contracts;

/**
 * Interface for Modulite panel discovery services.
 *
 * This interface defines the contract for scanning and discovering
 * Filament Panel Provider classes through directory structure and naming conventions.
 *
 * @package PanicDevs\Modulite\Contracts
 */
interface PanelScannerInterface
{
    /**
     * Discover all Filament Panel Provider classes in configured scan locations.
     *
     * This method should:
     * - Scan all configured locations for PHP files
     * - Parse files to extract class names
     * - Use inheritance checking to identify panel providers
     * - Return fully qualified class names of discovered panels
     * - Handle errors gracefully based on configuration
     *
     * @return array<string> Array of fully qualified panel provider class names
     */
    public function discoverPanels(): array;

    /**
     * Get statistics from the last scan operation.
     *
     * Returns metrics useful for debugging and performance monitoring:
     * - files_scanned: Number of PHP files examined
     * - classes_found: Total classes discovered in scanned files
     * - panels_discovered: Classes identified as panel providers
     * - scan_time: Time taken for the scan in seconds
     * - errors: Number of errors encountered during scan
     *
     * @return array<string, mixed> Scan statistics and metrics
     */
    public function getScanStats(): array;
}
