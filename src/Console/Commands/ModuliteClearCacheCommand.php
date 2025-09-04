<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use Throwable;

/**
 * Command to clear all Modulite caches.
 * This command is automatically called by Laravel's `optimize:clear` command.
 */
class ModuliteClearCacheCommand extends Command
{
    protected $signature = 'modulite:clear 
                          {--force : Force clear without confirmation}';

    protected $description = 'Clear all Modulite caches';

    public function handle(CacheManagerInterface $cacheManager): int
    {
        $this->info('Clearing Modulite caches...');

        try
        {
            // Clear all caches
            $cacheManager->flush();
            $this->line('✓ All caches cleared');

            $this->info('Modulite cache cleared successfully!');

            return self::SUCCESS;

        } catch (Throwable $e)
        {
            $this->error("Failed to clear cache: {$e->getMessage()}");

            if ($this->getOutput()->isVerbose())
            {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
