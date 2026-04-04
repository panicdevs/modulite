<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services\ModuleResolvers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use PanicDevs\Modules\Services\ModuleService;
use PanicDevs\Modulite\Contracts\ModuleResolverInterface;
use Throwable;

/**
 * Module resolver for panicdevs/modules package.
 *
 * This resolver integrates with the panicdevs/modules package
 * to discover enabled modules for Modulite component discovery.
 *
 * @package PanicDevs\Modulite\Services\ModuleResolvers
 */
class PanicDevsModuleResolver implements ModuleResolverInterface
{
    /**
     * Application instance.
     */
    protected Application $app;

    /**
     * Module service instance.
     */
    protected ?ModuleService $moduleService = null;

    /**
     * Create a new PanicDevsModuleResolver instance.
     *
     * @param Application $app Application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get collection of enabled module names.
     *
     * @return Collection<int, string> Collection of module names
     */
    public function getEnabledModules(): Collection
    {
        if (!$this->isAvailable())
        {
            return collect();
        }

        try
        {
            $modules = $this->getModuleService()->getEnabledByPriority();

            return collect($modules)->pluck('name');
        } catch (Throwable $e)
        {
            // Log error if needed
            return collect();
        }
    }

    /**
     * Check if a specific module is enabled.
     *
     * @param string $moduleName Module name to check
     * @return bool True if module is enabled, false otherwise
     */
    public function isModuleEnabled(string $moduleName): bool
    {
        if (!$this->isAvailable())
        {
            return false;
        }

        try
        {
            return $this->getModuleService()->isEnabled($moduleName);
        } catch (Throwable $e)
        {
            return false;
        }
    }

    /**
     * Get all available modules (enabled and disabled).
     *
     * @return Collection<int, string> Collection of all module names
     */
    public function getAllModules(): Collection
    {
        if (!$this->isAvailable())
        {
            return collect();
        }

        try
        {
            $modules = $this->getModuleService()->getAll();

            return collect($modules)->pluck('name');
        } catch (Throwable $e)
        {
            return collect();
        }
    }

    /**
     * Get the module system name/type.
     *
     * @return string Module system identifier
     */
    public function getSystemType(): string
    {
        return 'panicdevs';
    }

    /**
     * Check if the module system is available and properly configured.
     *
     * @return bool True if system is available, false otherwise
     */
    public function isAvailable(): bool
    {
        return class_exists(ModuleService::class) &&
               $this->app->bound(ModuleService::class);
    }

    /**
     * Get the module service instance.
     *
     * @return ModuleService
     */
    protected function getModuleService(): ModuleService
    {
        if (null === $this->moduleService)
        {
            $this->moduleService = $this->app->make(ModuleService::class);
        }

        return $this->moduleService;
    }

    /**
     * Determine whether panels should be registered
     * before Filament initialization.
     *
     * @return bool True if panels must be registered before Filament
     */
    public function shouldRegisterPanelsBeforeFilament(): bool
    {
        return false;
    }
}
