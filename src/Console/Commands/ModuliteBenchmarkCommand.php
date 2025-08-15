<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Console\Commands;

use Illuminate\Console\Command;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;

/**
 * Command to benchmark Modulite performance in different scenarios.
 */
class ModuliteBenchmarkCommand extends Command
{
    protected $signature = 'modulite:benchmark 
                          {--iterations=100 : Number of iterations for benchmarking}
                          {--warm-cache : Warm up cache before testing}
                          {--show-details : Show detailed breakdown}';

    protected $description = 'Benchmark Modulite performance for production optimization';

    public function handle(
        CacheManagerInterface $cache,
        PanelScannerInterface $panelScanner,
        ComponentScannerInterface $componentScanner
    ): int {
        $iterations = (int) $this->option('iterations');

        $this->info("🚀 Modulite Performance Benchmark");
        $this->line("=====================================");
        $this->newLine();

        // Show current environment and config
        $this->displayEnvironmentInfo($cache);

        if ($this->option('warm-cache')) {
            $this->warmUpCache($cache, $panelScanner, $componentScanner);
        }

        // Benchmark different scenarios
        $results = [
            'cache_read'          => $this->benchmarkCacheRead($cache, $iterations),
            'panel_discovery'     => $this->benchmarkPanelDiscovery($panelScanner, $iterations, $cache),
            'component_discovery' => $this->benchmarkComponentDiscovery($componentScanner, $iterations),
        ];

        $this->displayResults($results);
        $this->displayRecommendations($results);

        return self::SUCCESS;
    }

    protected function displayEnvironmentInfo(CacheManagerInterface $cache): void
    {
        $this->info("Environment Information:");
        $this->table(['Setting', 'Value'], [
            ['Environment', app()->environment()],
            ['Debug Mode', app()->hasDebugModeEnabled() ? '✓ Enabled' : '✗ Disabled'],
            ['Cache Enabled', $cache->isCacheEnabled() ? '✓ Enabled' : '✗ Disabled'],
            ['Cache File', $cache->getCacheFile()],
            ['Cache TTL', config('modulite.cache.ttl', 'default')],
            ['Lazy Discovery', config('modulite.performance.lazy_discovery', false) ? '✓ Enabled' : '✗ Disabled'],
            ['PHP Version', PHP_VERSION],
            ['Memory Limit', ini_get('memory_limit')],
        ]);
        $this->newLine();
    }

    protected function warmUpCache(
        CacheManagerInterface $cache,
        PanelScannerInterface $panelScanner,
        ComponentScannerInterface $componentScanner
    ): void {
        $this->info("🔥 Warming up cache...");

        // Simulate the actual discovery process
        $cacheKey = 'panels:'.md5('benchmark');
        $cache->put($cacheKey, $panelScanner->discoverPanels());

        $componentScanner->discoverComponents('manager');

        $this->info("✓ Cache warmed up");
        $this->newLine();
    }

    protected function benchmarkCacheRead(CacheManagerInterface $cache, int $iterations): array
    {
        $this->info("📊 Benchmarking cache read performance...");

        // Ensure we have some cache data
        $testKey = 'benchmark_test_key';
        $testData = ['test' => 'data', 'panels' => ['TestPanel1', 'TestPanel2']];
        $cache->put($testKey, $testData);

        $times = [];
        $progress = $this->output->createProgressBar($iterations);

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $result = $cache->get($testKey);
            $end = hrtime(true);

            $times[] = ($end - $start) / 1_000_000; // Convert to milliseconds
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();

        // Clean up
        $cache->forget($testKey);

        return $this->calculateStats($times, 'Cache Read');
    }

    protected function benchmarkPanelDiscovery(
        PanelScannerInterface $panelScanner,
        int $iterations,
        CacheManagerInterface $cache
    ): array {
        $this->info("🔍 Benchmarking panel discovery with cache...");

        $times = [];
        $progress = $this->output->createProgressBar($iterations);

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);

            // Simulate the production service provider flow
            $cacheKey = 'panels:benchmark_'.$i;
            $panels = $cache->get($cacheKey);

            if (null === $panels) {
                $panels = $panelScanner->discoverPanels();
                $cache->put($cacheKey, $panels);
            }

            $end = hrtime(true);

            $times[] = ($end - $start) / 1_000_000; // Convert to milliseconds
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();

        return $this->calculateStats($times, 'Panel Discovery');
    }

    protected function benchmarkComponentDiscovery(
        ComponentScannerInterface $componentScanner,
        int $iterations
    ): array {
        $this->info("🧩 Benchmarking component discovery...");

        $times = [];
        $progress = $this->output->createProgressBar($iterations);

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $components = $componentScanner->discoverComponents('manager');
            $end = hrtime(true);

            $times[] = ($end - $start) / 1_000_000; // Convert to milliseconds
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();

        return $this->calculateStats($times, 'Component Discovery');
    }

    protected function calculateStats(array $times, string $operation): array
    {
        $count = count($times);
        $total = array_sum($times);
        $average = $total / $count;
        $min = min($times);
        $max = max($times);

        sort($times);
        $median = 0 === $count % 2
            ? ($times[$count / 2 - 1] + $times[$count / 2]) / 2
            : $times[intval($count / 2)];

        $p95Index = intval($count * 0.95);
        $p95 = $times[$p95Index];

        return [
            'operation'  => $operation,
            'iterations' => $count,
            'total_ms'   => round($total, 3),
            'average_ms' => round($average, 3),
            'median_ms'  => round($median, 3),
            'min_ms'     => round($min, 3),
            'max_ms'     => round($max, 3),
            'p95_ms'     => round($p95, 3),
        ];
    }

    protected function displayResults(array $results): void
    {
        $this->info("📈 Benchmark Results:");

        foreach ($results as $result) {
            $this->table(['Metric', 'Value'], [
                ['Operation', $result['operation']],
                ['Iterations', number_format($result['iterations'])],
                ['Average (ms)', $result['average_ms']],
                ['Median (ms)', $result['median_ms']],
                ['Min (ms)', $result['min_ms']],
                ['Max (ms)', $result['max_ms']],
                ['95th Percentile (ms)', $result['p95_ms']],
                ['Total Time (ms)', $result['total_ms']],
            ]);
            $this->newLine();
        }
    }

    protected function displayRecommendations(array $results): void
    {
        $this->info("💡 Performance Recommendations:");

        $cacheRead = $results['cache_read'];
        $panelDiscovery = $results['panel_discovery'];

        if ($cacheRead['average_ms'] > 0.5) {
            $this->warn("⚠️  Cache reading is slower than expected ({$cacheRead['average_ms']}ms)");
            $this->line("   → Consider setting MODULITE_CACHE_TTL=0 for production");
            $this->line("   → Ensure bootstrap/cache directory has proper permissions");
        } else {
            $this->info("✅ Cache performance is excellent ({$cacheRead['average_ms']}ms average)");
        }

        if ($panelDiscovery['average_ms'] > 2.0) {
            $this->warn("⚠️  Panel discovery is slow ({$panelDiscovery['average_ms']}ms)");
            $this->line("   → Cache might not be working properly");
            $this->line("   → Check if MODULITE_CACHE_ENABLED=true");
        } else {
            $this->info("✅ Panel discovery performance is good ({$panelDiscovery['average_ms']}ms average)");
        }

        $this->newLine();
        $this->info("🎯 Production Optimization Tips:");
        $this->line("   • Set MODULITE_CACHE_TTL=0 (never expire)");
        $this->line("   • Set APP_DEBUG=false");
        $this->line("   • Use 'php artisan optimize' for Laravel optimizations");
        $this->line("   • Enable OPcache in PHP for better performance");
        $this->line("   • Use 'php artisan modulite:benchmark --warm-cache' to test");
    }
}
