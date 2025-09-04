<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
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
                           {--force : Force cache regeneration even if cache exists}';

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
        ComponentScannerInterface $componentScanner
    ): int {
        $this->info('Optimizing Modulite caches...');

        try
        {
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
            $this->line("  <fg=green>✓</> Found {$panelCount} panel providers");

            // Warm component discovery cache for all discovered panels
            $this->line('• Warming component discovery cache...');
            $totalComponents = 0;

            foreach ($panels as $panelClass)
            {
                // Extract panel ID from class name (e.g., UserPanelProvider -> user)
                $panelId    = $this->extractPanelIdFromClass($panelClass);
                $components = $componentScanner->discoverComponents($panelId);
                $totalComponents += array_sum(array_map('count', $components));
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
