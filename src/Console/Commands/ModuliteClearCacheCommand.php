<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Services\SimpleFileStorage;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use PanicDevs\Modulite\Contracts\ComponentDiscoveryInterface;
use Throwable;

/**
 * Command to clear all Modulite caches.
 */
class ModuliteClearCacheCommand extends Command
{
    protected $signature = 'modulite:clear-cache';

    protected $description = 'Clear all Modulite storage (panels, components, and discovery data)';

    public function handle(
        SimpleFileStorage $storage,
        PanelScannerInterface $panelScanner,
        ComponentDiscoveryInterface $componentDiscovery
    ): int {
        $this->info('Clearing Modulite storage...');

        try {
            // Clear main storage
            $storage->clear();
            $this->line('✓ Main storage cleared');

            // Clear panel scanner cache
            $panelScanner->refreshCache();
            $this->line('✓ Panel discovery cache cleared');

            // Clear component discovery cache
            $componentDiscovery->refreshCache();
            $this->line('✓ Component discovery cache cleared');

            $this->info('All Modulite storage cleared successfully!');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Failed to clear storage: {$e->getMessage()}");

            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
