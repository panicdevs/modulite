<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;

/**
 * Command to display Modulite status and statistics.
 */
class ModuliteStatusCommand extends Command
{
    protected $signature = 'modulite:status
                          {--clear-cache : Clear all Modulite cache}
                          {--scan : Force rescan of panels and components}
                          {--vvv : Show detailed information}';

    protected $description = 'Display Modulite discovery status and statistics';

    public function handle(
        \PanicDevs\Modulite\Contracts\CacheManagerInterface $cache,
        PanelScannerInterface $panelScanner,
        \PanicDevs\Modulite\Contracts\ComponentScannerInterface $componentScanner
    ): int {
        $this->info('Modulite Status Report');
        $this->line('===================');

        // Handle options
        if ($this->option('clear-cache'))
        {
            $this->clearCache($cache);
        }

        if ($this->option('scan'))
        {
            $this->forceScan($panelScanner, $componentScanner);
        }

        // Display status
        $this->displayConfiguration();
        $this->displayModuleStatus($panelScanner);
        $this->displayPanelStatus($panelScanner);
        $this->displayComponentStatus($componentScanner);
        $this->displayCacheStatus($cache);

        if ($this->option('vvv'))
        {
            $this->displayDetailedStats($panelScanner, $componentScanner, $cache);
        }

        return self::SUCCESS;
    }

    protected function clearCache(
        \PanicDevs\Modulite\Contracts\CacheManagerInterface $cache
    ): void {
        $this->info('Clearing Modulite cache...');

        $cache->flush();

        $this->info('✓ Cache cleared successfully');
        $this->newLine();
    }

    protected function forceScan(
        PanelScannerInterface $panelScanner,
        \PanicDevs\Modulite\Contracts\ComponentScannerInterface $componentScanner
    ): void {
        $this->info('Force scanning panels and components...');

        $startTime = microtime(true);

        $panels    = $panelScanner->discoverPanels();
        $resources = $componentScanner->discoverComponentType('manager', 'resources');
        $pages     = $componentScanner->discoverComponentType('manager', 'pages');
        $widgets   = $componentScanner->discoverComponentType('manager', 'widgets');

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->info("✓ Scan completed in {$duration}ms");
        $this->info("  Found ".count($panels)." panels, ".count($resources)." resources, ".count($pages)." pages, ".count($widgets)." widgets");
        $this->newLine();
    }

    protected function displayConfiguration(): void
    {
        $this->info('Configuration:');

        $config = config('modulite', []);

        $this->table(['Setting', 'Value'], [
            ['Cache Enabled', $config['cache']['enabled'] ?? false ? '✓' : '✗'],
            ['Cache File', $config['cache']['file'] ?? 'unknown'],
            ['Lazy Discovery', $config['performance']['lazy_discovery'] ?? false ? '✓' : '✗'],
            ['Logging Enabled', $config['logging']['enabled'] ?? false ? '✓' : '✗'],
            ['Fail Silently', $config['error_handling']['fail_silently'] ?? false ? '✓' : '✗'],
        ]);

        $this->newLine();
    }

    protected function displayModuleStatus(PanelScannerInterface $panelScanner): void
    {
        $this->info('Module Status:');

        // Get enabled modules using nwidart modules if available
        $enabledModules = collect();

        if (class_exists(\Nwidart\Modules\Facades\Module::class))
        {
            $enabledModulesArray = \Nwidart\Modules\Facades\Module::allEnabled();
            foreach ($enabledModulesArray as $module)
            {
                $enabledModules->push($module->getName());
            }
        }

        if ($enabledModules->isEmpty())
        {
            $this->warn('No enabled modules found');
        } else
        {
            $this->info("Found {$enabledModules->count()} enabled modules:");
            foreach ($enabledModules as $module)
            {
                $this->line("  • {$module}");
            }
        }

        $this->newLine();
    }

    protected function displayPanelStatus(PanelScannerInterface $panelScanner): void
    {
        $this->info('Panel Discovery:');

        $panels = $panelScanner->discoverPanels();

        if (empty($panels))
        {
            $this->warn('No panels discovered');
        } else
        {
            $this->info("Discovered ".count($panels)." panels:");
            foreach ($panels as $panel)
            {
                $this->line("  • {$panel}");
            }
        }

        $this->newLine();
    }

    protected function displayComponentStatus(\PanicDevs\Modulite\Contracts\ComponentScannerInterface $componentScanner): void
    {
        $this->info('Component Discovery:');

        $resources = $componentScanner->discoverComponentType('manager', 'resources');
        $pages     = $componentScanner->discoverComponentType('manager', 'pages');
        $widgets   = $componentScanner->discoverComponentType('manager', 'widgets');

        $this->table(['Type', 'Count'], [
            ['Resources', count($resources)],
            ['Pages', count($pages)],
            ['Widgets', count($widgets)],
            ['Total', count($resources) + count($pages) + count($widgets)],
        ]);

        $this->newLine();
    }

    protected function displayCacheStatus(\PanicDevs\Modulite\Contracts\CacheManagerInterface $cache): void
    {
        $this->info('Cache Status:');

        $stats = $cache->getStats();

        $this->table(['Metric', 'Value'], [
            ['Enabled', $stats['enabled'] ? '✓' : '✗'],
            ['File Exists', $stats['file_exists'] ? '✓' : '✗'],
            ['File Size', $stats['file_size'] ? number_format($stats['file_size']).' bytes' : 'N/A'],
            ['Total Items', $stats['total_items'] ?? 0],
            ['Valid Items', $stats['valid_items'] ?? 0],
            ['Expired Items', $stats['expired_items'] ?? 0],
            ['Cache Created', $stats['cache_created'] ? date('Y-m-d H:i:s', $stats['cache_created']) : 'N/A'],
        ]);

        $this->newLine();
    }

    protected function displayDetailedStats(
        PanelScannerInterface $panelScanner,
        \PanicDevs\Modulite\Contracts\ComponentScannerInterface $componentScanner,
        \PanicDevs\Modulite\Contracts\CacheManagerInterface $cache
    ): void {
        $this->info('Detailed Statistics:');

        $panelStats     = $panelScanner->getScanStats();
        $componentStats = $componentScanner->getScanStats();

        $this->info('Panel Scanner:');
        $this->table(['Metric', 'Value'], [
            ['Files Scanned', $panelStats['files_scanned'] ?? 0],
            ['Classes Found', $panelStats['classes_found'] ?? 0],
            ['Panels Discovered', $panelStats['panels_discovered'] ?? 0],
            ['Scan Time (ms)', round(($panelStats['scan_time'] ?? 0) * 1000, 2)],
            ['Errors', $panelStats['errors'] ?? 0],
        ]);

        $this->info('Component Scanner:');
        $this->table(['Metric', 'Value'], [
            ['Scanned Modules', $componentStats['scanned_modules'] ?? 0],
            ['Scanned Files', $componentStats['scanned_files'] ?? 0],
            ['Found Resources', $componentStats['found_resources'] ?? 0],
            ['Found Pages', $componentStats['found_pages'] ?? 0],
            ['Found Widgets', $componentStats['found_widgets'] ?? 0],
            ['Scan Time (ms)', round(($componentStats['scan_time'] ?? 0) * 1000, 2)],
        ]);

        $this->info('Cache Details:');
        $cacheStats = $cache->getStats();
        $this->table(['Metric', 'Value'], [
            ['Cache File', $cache->getCacheFile()],
            ['File Size (bytes)', $cacheStats['file_size'] ?? 0],
            ['Total Items', $cacheStats['total_items'] ?? 0],
            ['Valid Items', $cacheStats['valid_items'] ?? 0],
            ['Expired Items', $cacheStats['expired_items'] ?? 0],
        ]);
    }
}
