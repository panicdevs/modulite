<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use Throwable;

/**
 * Artisan command to clear Modulite caches.
 *
 * This command provides a convenient way to clear all Modulite caches
 * during development or deployment processes.
 *
 * Usage:
 * php artisan modulite:clear-cache
 * php artisan modulite:clear-cache --force
 *
 * @package PanicDevs\Modulite\Console\Commands
 */
class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'modulite:clear-cache 
                           {--force : Force cache clearing without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clear all Modulite panel discovery caches';

    /**
     * Execute the console command.
     */
    public function handle(CacheManagerInterface $cacheManager): int
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all Modulite caches?')) {
            $this->info('Cache clearing cancelled.');
            return self::SUCCESS;
        }

        try {
            $this->info('Clearing Modulite caches...');

            $cacheManager->flush();

            $this->components->info('Modulite caches cleared successfully!');

            // Show cache status
            $this->showCacheStatus($cacheManager);

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->components->error('Failed to clear Modulite caches: '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Show cache status information.
     */
    protected function showCacheStatus(CacheManagerInterface $cacheManager): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('Cache Status', $cacheManager->isCacheEnabled() ? 'Enabled' : 'Disabled');

        $config = config('modulite.cache', []);
        $this->components->twoColumnDetail('Cache Driver', $config['driver'] ?? 'file');
        $this->components->twoColumnDetail('Cache TTL', ($config['ttl'] ?? 3600).' seconds');
        $this->components->twoColumnDetail('File Cache', ($config['file_cache']['enabled'] ?? true) ? 'Enabled' : 'Disabled');

        if ($config['file_cache']['enabled'] ?? true) {
            $filePath = $config['file_cache']['path'] ?? base_path('bootstrap/cache/modulite_panels.php');
            $exists = file_exists($filePath);
            $this->components->twoColumnDetail('File Cache Path', $filePath);
            $this->components->twoColumnDetail('File Cache Status', $exists ? 'Exists' : 'Not Found');
        }
    }
}
