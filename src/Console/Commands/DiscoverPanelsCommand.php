<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use Throwable;

/**
 * Artisan command to discover and display Filament panels.
 *
 * This command manually triggers panel discovery and displays
 * detailed information about found panels and scan statistics.
 *
 * Usage:
 * php artisan modulite:discover
 * php artisan modulite:discover --verbose
 * php artisan modulite:discover --stats
 *
 * @package PanicDevs\Modulite\Console\Commands
 */
class DiscoverPanelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'modulite:discover 
                           {--stats : Show detailed scan statistics}
                           {--no-cache : Skip cache and force fresh scan}';

    /**
     * The console command description.
     */
    protected $description = 'Discover and display Filament panel providers';

    /**
     * Execute the console command.
     */
    public function handle(PanelScannerInterface $scanner): int
    {
        try {
            $this->info('Discovering Filament panel providers...');
            $this->newLine();

            if ($this->option('no-cache')) {
                $this->warn('Skipping cache - performing fresh scan...');
            }

            $startTime = microtime(true);

            // Discover panels
            $panels = $scanner->discoverPanels();

            $endTime = microtime(true);
            $scanTime = round($endTime - $startTime, 3);

            // Display results
            $this->displayResults($panels, $scanTime);

            // Display statistics if requested
            if ($this->option('stats') || $this->getOutput()->isVerbose()) {
                $this->displayStatistics($scanner->getScanStats());
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->components->error('Panel discovery failed: '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Display discovery results.
     *
     * @param array<string> $panels
     */
    protected function displayResults(array $panels, float $scanTime): void
    {
        $count = count($panels);

        if (0 === $count) {
            $this->components->warn('No panel providers found!');
            $this->newLine();
            $this->line('Tips:');
            $this->line('• Make sure your panel providers are marked with #[FilamentPanel]');
            $this->line('• Check your scan locations in config/modulite.php');
            $this->line('• Verify that modules are enabled');
            return;
        }

        $this->components->info("Found {$count} panel provider(s) in {$scanTime}s");
        $this->newLine();

        // Group panels by module/namespace
        $grouped = $this->groupPanelsByModule($panels);

        foreach ($grouped as $module => $modulePanels) {
            $this->components->twoColumnDetail($module, count($modulePanels).' panel(s)');

            foreach ($modulePanels as $panel) {
                $className = class_basename($panel);
                $this->line("  • {$className}");
            }

            $this->newLine();
        }
    }

    /**
     * Display scan statistics.
     *
     * @param array<string, mixed> $stats
     */
    protected function displayStatistics(array $stats): void
    {
        $this->components->info('Scan Statistics');
        $this->newLine();

        $this->components->twoColumnDetail('Files Scanned', (string) ($stats['files_scanned'] ?? 0));
        $this->components->twoColumnDetail('Classes Found', (string) ($stats['classes_found'] ?? 0));
        $this->components->twoColumnDetail('Panels Discovered', (string) ($stats['panels_discovered'] ?? 0));
        $this->components->twoColumnDetail('Scan Time', round($stats['scan_time'] ?? 0, 3).'s');
        $this->components->twoColumnDetail('Errors', (string) ($stats['errors'] ?? 0));

        if (($stats['errors'] ?? 0) > 0) {
            $this->newLine();
            $this->components->warn('Some errors occurred during scanning. Enable verbose logging for details.');
        }

        $this->newLine();
        $this->displayConfiguration();
    }

    /**
     * Display current configuration.
     */
    protected function displayConfiguration(): void
    {
        $this->components->info('Configuration');
        $this->newLine();

        $config = config('modulite', []);

        // Scan locations
        $locations = $config['scan']['locations'] ?? [];
        $this->components->twoColumnDetail('Scan Locations', count($locations).' configured');
        foreach ($locations as $location) {
            $this->line("  • {$location}");
        }

        $this->newLine();

        // Cache settings
        $cacheEnabled = $config['cache']['enabled'] ?? false;
        $this->components->twoColumnDetail('Cache Enabled', $cacheEnabled ? 'Yes' : 'No');

        if ($cacheEnabled) {
            $this->components->twoColumnDetail('Cache Driver', $config['cache']['driver'] ?? 'file');
            $this->components->twoColumnDetail('Cache TTL', ($config['cache']['ttl'] ?? 3600).'s');
        }

        // Performance settings
        $this->components->twoColumnDetail('Max Depth', (string) ($config['scan']['max_depth'] ?? 10));
        $this->components->twoColumnDetail('Lazy Discovery', ($config['performance']['lazy_discovery'] ?? true) ? 'Yes' : 'No');
    }

    /**
     * Group panels by their module/namespace.
     *
     * @param array<string> $panels
     * @return array<string, array<string>>
     */
    protected function groupPanelsByModule(array $panels): array
    {
        $grouped = [];

        foreach ($panels as $panel) {
            $parts = explode('\\', $panel);

            // Try to determine module name
            $module = 'Unknown';

            if (count($parts) >= 2) {
                if ('Modules' === $parts[0] && isset($parts[1])) {
                    $module = "Module: {$parts[1]}";
                } elseif ('App' === $parts[0]) {
                    $module = 'Application';
                } elseif ('Foundation' === $parts[0] && isset($parts[1])) {
                    $module = "Foundation: {$parts[1]}";
                } else {
                    $module = $parts[0];
                }
            }

            $grouped[$module][] = $panel;
        }

        // Sort by module name
        ksort($grouped);

        return $grouped;
    }
}
