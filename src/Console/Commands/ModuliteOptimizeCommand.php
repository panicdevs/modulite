<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use PanicDevs\Modulite\Contracts\ModuleResolverInterface;
use Throwable;

/**
 * ModuliteOptimizeCommand
 *
 * Optimizes Modulite by warming all caches (panels and components).
 * This command is automatically called by Laravel's `optimize` command.
 *
 * @package PanicDevs\Modulite\Console\Commands
 * @author  PanicDevs
 * @since   1.0.0
 */
class ModuliteOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modulite:cache
                           {--force : Force cache regeneration even if cache exists}
                           {--enable-cache : Temporarily enable caching even in debug mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache Filament panels and components for better performance';

    /**
     * Execute the console command.
     */
    public function handle(
        CacheManagerInterface $cacheManager,
        PanelScannerInterface $panelScanner,
        ComponentScannerInterface $componentScanner,
        ModuleResolverInterface $moduleResolver
    ): int {
        $this->info('Optimizing Modulite caches...');

        try
        {
            // Temporarily enable cache if requested
            if ($this->option('enable-cache'))
            {
                $this->info('Temporarily enabling cache for this operation...');
                // Enable cache in the manager for this command only
                $this->enableCacheForCommand($cacheManager);
            }

            // Clear existing caches if force flag is used
            if ($this->option('force'))
            {
                $this->info('Clearing existing caches...');
                $cacheManager->flush();
            }

            // Warm panel discovery cache
            $this->line('• Warming panel discovery cache...');
            $panels     = $panelScanner->discoverPanels();
            $panelCount = count($panels);

            // Store panels in cache using the same key as the service provider
            $panelCacheKey = $this->generatePanelCacheKey($moduleResolver);
            $cacheManager->put($panelCacheKey, $panels);

            $this->line("  <fg=green>✓</> Found {$panelCount} panel providers");

            // Warm component discovery cache for all discovered panels
            $this->line('• Warming component discovery cache...');
            $totalComponents = 0;

            foreach ($panels as $panelClass)
            {
                // Extract panel ID from class name (e.g., UserPanelProvider -> user)
                $panelId = $this->extractPanelIdFromClass($panelClass);

                // Force cache warming by calling discoverComponents which stores in cache
                $components     = $componentScanner->discoverComponents($panelId);
                $componentCount = array_sum(array_map('count', $components));
                $totalComponents += $componentCount;

                $this->line("    - {$panelId}: {$componentCount} components");
            }

            $this->line("  <fg=green>✓</> Found {$totalComponents} components");

            // Display cache file locations
            $this->displayCacheInfo();

            $this->info('Modulite caches optimized successfully!');
            $this->line("- <fg=green>{$panelCount}</> panel providers cached");
            $this->line("- <fg=green>{$totalComponents}</> components cached");

            return self::SUCCESS;

        } catch (Throwable $e)
        {
            $this->error("Failed to optimize Modulite caches: {$e->getMessage()}");

            if ($this->getOutput()->isVerbose())
            {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Extract panel ID from panel provider class name.
     *
     * Examples:
     * - AdminPanelProvider -> admin
     * - UserPanelProvider -> user
     * - ManagerPanelProvider -> manager
     */
    protected function extractPanelIdFromClass(string $className): string
    {
        // Get just the class name without namespace
        $parts     = explode('\\', $className);
        $shortName = end($parts);

        // Remove 'Provider' suffix and 'Panel'
        $panelId = str_replace(['PanelProvider', 'Panel', 'Provider'], '', $shortName);

        // Convert to lowercase
        return mb_strtolower($panelId);
    }

    /**
     * Temporarily enable cache for this command operation.
     */
    protected function enableCacheForCommand(CacheManagerInterface $cacheManager): void
    {
        // Check if cache manager supports runtime enable (UnifiedCacheManager does)
        if (method_exists($cacheManager, 'enableTemporarily'))
        {
            $cacheManager->enableTemporarily();
        } else
        {
            $this->warn('Cache manager does not support temporary enabling. Use MODULITE_CACHE_ENABLED=true instead.');
        }
    }

    /**
     * Generate cache key for panels (same logic as ModuliteServiceProvider).
     */
    protected function generatePanelCacheKey(ModuleResolverInterface $moduleResolver): string
    {
        // Use the module resolver to get enabled modules
        $enabledModules = $moduleResolver->getEnabledModules();

        // Include module names - use simple array for consistent hashing
        $moduleData = $enabledModules->sort()->values()->toArray();

        // Include the module resolver approach in cache key
        $approach = config('modulite.modules.approach', 'nwidart');

        // Include configuration in cache key
        $configHash = md5(serialize([
            'panels'     => config('modulite.panels', []),
            'components' => config('modulite.components', []),
            'modules'    => config('modulite.modules', [])
        ]));

        // Include environment
        $environment = config('app.env', 'production');

        $keyData = [
            'modules'     => $moduleData,
            'approach'    => $approach,
            'config'      => $configHash,
            'environment' => $environment,
        ];

        return 'panels:'.md5(serialize($keyData));
    }

    /**
     * Display cache file information.
     */
    protected function displayCacheInfo(): void
    {
        $cacheFile = config('modulite.cache.file', base_path('bootstrap/cache/modulite.php'));

        if (file_exists($cacheFile))
        {
            $this->line("- Cache file: <fg=blue>{$cacheFile}</>");
            $size = filesize($cacheFile);
            $this->line("- Cache size: <fg=blue>".number_format($size)." bytes</>");
        } else
        {
            $this->line("- Cache file: <fg=yellow>Not created yet</>");
        }
    }
}
