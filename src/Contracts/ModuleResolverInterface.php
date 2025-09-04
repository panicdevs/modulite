<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for resolving enabled modules from different module management systems.
 *
 * This interface abstracts the differences between various Laravel module
 * management packages (nwidart/laravel-modules, panicdevs/modules, etc.)
 * allowing Modulite to work with any module system.
 *
 * @package PanicDevs\Modulite\Contracts
 */
interface ModuleResolverInterface
{
    /**
     * Get collection of enabled module names.
     *
     * @return Collection<int, string> Collection of module names
     */
    public function getEnabledModules(): Collection;

    /**
     * Check if a specific module is enabled.
     *
     * @param string $moduleName Module name to check
     * @return bool True if module is enabled, false otherwise
     */
    public function isModuleEnabled(string $moduleName): bool;

    /**
     * Get all available modules (enabled and disabled).
     *
     * @return Collection<int, string> Collection of all module names
     */
    public function getAllModules(): Collection;

    /**
     * Get the module system name/type.
     *
     * @return string Module system identifier (e.g., 'nwidart', 'panicdevs')
     */
    public function getSystemType(): string;

    /**
     * Check if the module system is available and properly configured.
     *
     * @return bool True if system is available, false otherwise
     */
    public function isAvailable(): bool;
}
