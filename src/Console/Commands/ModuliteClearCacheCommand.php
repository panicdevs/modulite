<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use Throwable;

/**
 * Command to clear all Modulite caches.
 */
class ModuliteClearCacheCommand extends Command
{
    protected $signature = 'modulite:clear 
                          {--force : Force clear without confirmation}';

    protected $description = 'Clear all Modulite caches';

    public function handle(CacheManagerInterface $cacheManager): int
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all Modulite caches?'))
        {
            $this->info('Cache clear cancelled.');
            return self::SUCCESS;
        }

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
