<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use Throwable;

/**
 * Console command for discovering Filament components across modules.
 *
 * This command provides:
 * - Manual component discovery for testing and debugging
 * - Component listing by panel and type
 * - Performance metrics and statistics
 * - Cache warming capabilities
 *
 * @package PanicDevs\Modulite\Console\Commands
 */
class DiscoverComponentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modulite:discover-components 
                           {panel : Panel name to discover components for}
                           {--type= : Specific component type (resources, pages, widgets)}
                           {--show-stats : Show discovery statistics}
                           {--format=table : Output format (table, json, list)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover Filament components in modules for a specific panel';

    /**
     * Execute the console command.
     */
    public function handle(ComponentScannerInterface $componentScanner): int
    {
        $panelName = $this->argument('panel');
        $componentType = $this->option('type');
        $showStats = $this->option('show-stats');
        $format = $this->option('format');

        $this->info("Discovering components for panel: <comment>{$panelName}</comment>");

        try {
            if ($componentType) {
                $components = [$componentType => $componentScanner->discoverComponentType($panelName, $componentType)];
                $this->info("Scanning for component type: <comment>{$componentType}</comment>");
            } else {
                $components = $componentScanner->discoverComponents($panelName);
                $this->info("Scanning for all component types");
            }

            $this->displayComponents($components, $format);

            if ($showStats) {
                $this->displayStatistics($componentScanner->getScanStats());
            }

            $totalComponents = array_sum(array_map('count', $components));
            $this->info("\n<fg=green>✓</> Discovery completed successfully. Found {$totalComponents} components.");

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Component discovery failed: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error("File: {$e->getFile()}");
                $this->error("Line: {$e->getLine()}");
                $this->error("Trace: {$e->getTraceAsString()}");
            }

            return self::FAILURE;
        }
    }

    /**
     * Display discovered components in the specified format.
     */
    protected function displayComponents(array $components, string $format): void
    {
        if (empty($components)) {
            $this->warn('No components discovered.');
            return;
        }

        switch ($format) {
            case 'json':
                $this->displayComponentsAsJson($components);
                break;
            case 'list':
                $this->displayComponentsAsList($components);
                break;
            case 'table':
            default:
                $this->displayComponentsAsTable($components);
                break;
        }
    }

    /**
     * Display components as a table.
     */
    protected function displayComponentsAsTable(array $components): void
    {
        $tableData = [];

        foreach ($components as $type => $classList) {
            foreach ($classList as $className) {
                $tableData[] = [
                    'Type'       => ucfirst($type),
                    'Class Name' => $className,
                    'Module'     => $this->extractModuleName($className),
                ];
            }
        }

        if (!empty($tableData)) {
            $this->table(['Type', 'Class Name', 'Module'], $tableData);
        }
    }

    /**
     * Display components as a simple list.
     */
    protected function displayComponentsAsList(array $components): void
    {
        foreach ($components as $type => $classList) {
            if (empty($classList)) {
                continue;
            }

            $this->line("\n<fg=yellow>".ucfirst($type).":</>");
            foreach ($classList as $className) {
                $this->line("  • {$className}");
            }
        }
    }

    /**
     * Display components as JSON.
     */
    protected function displayComponentsAsJson(array $components): void
    {
        $this->line(json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Display scan statistics.
     */
    protected function displayStatistics(array $stats): void
    {
        $this->line("\n<fg=yellow>Discovery Statistics:</>");

        $statsTable = [
            ['Files Scanned', $stats['files_scanned'] ?? 0],
            ['Classes Found', $stats['classes_found'] ?? 0],
            ['Components Discovered', $stats['components_discovered'] ?? 0],
            ['Scan Time', round(($stats['scan_time'] ?? 0) * 1000, 2).' ms'],
            ['Errors', $stats['errors'] ?? 0],
        ];

        $this->table(['Metric', 'Value'], $statsTable);
    }

    /**
     * Extract module name from a class name.
     */
    protected function extractModuleName(string $className): string
    {
        // Try to extract module name from namespace
        // E.g., "Modules\User\Filament\Admin\Resources\UserResource" -> "User"
        if (preg_match('/(?:Modules|modules)\\\\([^\\\\]+)/', $className, $matches)) {
            return $matches[1];
        }

        // Try foundation modules
        if (preg_match('/(?:Foundation|foundation)\\\\([^\\\\]+)/', $className, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }
}
