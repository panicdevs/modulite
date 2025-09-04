<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services\ModuleResolvers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PanicDevs\Modulite\Contracts\ModuleResolverInterface;
use Throwable;

/**
 * Module resolver for nwidart/laravel-modules package.
 *
 * This resolver integrates with the nwidart/laravel-modules package
 * to discover enabled modules for Modulite component discovery.
 *
 * @package PanicDevs\Modulite\Services\ModuleResolvers
 */
class NwidartModuleResolver implements ModuleResolverInterface
{
    /**
     * Get collection of enabled module names.
     *
     * @return Collection<int, string> Collection of module names
     */
    public function getEnabledModules(): Collection
    {
        if (!$this->isAvailable())
        {
            return $this->getFallbackModules();
        }

        try
        {
            $enabledModules = \Nwidart\Modules\Facades\Module::allEnabled();
            $modules        = collect();

            foreach ($enabledModules as $module)
            {
                $modules->push($module->getName());
            }

            return $modules;
        } catch (Throwable $e)
        {
            // Log error if needed
            return $this->getFallbackModules();
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
            return $this->getFallbackModules()->contains($moduleName);
        }

        try
        {
            return \Nwidart\Modules\Facades\Module::find($moduleName)?->isEnabled() ?? false;
        } catch (Throwable $e)
        {
            return $this->getFallbackModules()->contains($moduleName);
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
            return $this->getFallbackModules();
        }

        try
        {
            $allModules = \Nwidart\Modules\Facades\Module::all();
            $modules    = collect();

            foreach ($allModules as $module)
            {
                $modules->push($module->getName());
            }

            return $modules;
        } catch (Throwable $e)
        {
            return $this->getFallbackModules();
        }
    }

    /**
     * Get the module system name/type.
     *
     * @return string Module system identifier
     */
    public function getSystemType(): string
    {
        return 'nwidart';
    }

    /**
     * Check if the module system is available and properly configured.
     *
     * @return bool True if system is available, false otherwise
     */
    public function isAvailable(): bool
    {
        return class_exists(\Nwidart\Modules\Facades\Module::class);
    }

    /**
     * Get fallback modules from modules_statuses.json.
     *
     * @return Collection<int, string> Collection of module names
     */
    protected function getFallbackModules(): Collection
    {
        $modules    = collect();
        $statusFile = base_path('modules_statuses.json');

        if (File::exists($statusFile))
        {
            try
            {
                $statuses = json_decode(File::get($statusFile), true);

                if (is_array($statuses))
                {
                    foreach ($statuses as $moduleName => $enabled)
                    {
                        if ($enabled)
                        {
                            $modules->push($moduleName);
                        }
                    }
                }
            } catch (Throwable $e)
            {
                // Silently fail - return empty collection
            }
        }

        return $modules;
    }
}
