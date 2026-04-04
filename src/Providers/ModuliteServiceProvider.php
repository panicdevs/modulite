<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Providers;

use Filament\FilamentServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use PanicDevs\Modulite\Actions\CreateModuleResolver;
use PanicDevs\Modulite\Console\Commands\ModuliteClearCacheCommand;
use PanicDevs\Modulite\Console\Commands\ModuliteStatusCommand;
use PanicDevs\Modulite\Console\Commands\ModuliteOptimizeCommand;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use PanicDevs\Modulite\Contracts\ComponentScannerInterface;
use PanicDevs\Modulite\Contracts\ModuleResolverInterface;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;
use PanicDevs\Modulite\Services\ComponentDiscoveryService;
use PanicDevs\Modulite\Services\PanelScannerService;
use PanicDevs\Modulite\Services\UnifiedCacheManager;
use Throwable;

/**
 * ModuliteServiceProvider - Main service provider for Modulite package.
 *
 * This service provider is responsible for:
 * - Publishing configuration files
 * - Registering core services (CacheManager, PanelScannerService)
 * - Discovering and registering Filament Panel Providers
 * - Handling configuration validation
 * - Setting up event listeners for cache invalidation
 *
 * The provider follows Laravel's best practices:
 * - Lazy loading of panel discovery
 * - Service container binding with interfaces
 * - Configuration merging and publishing
 * - Event-driven cache invalidation
 *
 * @package PanicDevs\Modulite\Providers
 */
class ModuliteServiceProvider extends ServiceProvider
{
    /**
     * Filament framework namespace for lazy discovery.
     */
    protected const FILAMENT_NAMESPACE = 'filament';

    /**
     * nwidart/laravel-modules namespace for module management.
     */
    protected const NWIDART_MODULES_NAMESPACE = 'modules';

    /**
     * Configuration file path.
     */
    protected const CONFIG_PATH = __DIR__.'/../../config/modulite.php';

    /**
     * All the singletons that should be registered.
     *
     * @var array<string, string>
     */
    public array $singletons = [
        // Services are manually registered in registerCoreServices() with proper dependency injection
    ];

    /**
     * Register services into the service container.
     *
     * This method:
     * - Merges package configuration with app configuration
     * - Registers core services as singletons
     * - Sets up lazy panel discovery
     * - Configures cache invalidation listeners
     */
    public function register(): void
    {
        $this->registerConfiguration();

        $this->registerCoreServices();

        $this->app
            ->make(ModuleResolverInterface::class)
            ->shouldRegisterPanelsBeforeFilament() ?
                $this->app->beforeResolving('filament', fn () => $this->registerPanelDiscovery()):
                $this->registerPanelDiscovery();

        $this->registerCacheInvalidationListeners();
    }

    /**
     * Bootstrap services after all services are registered.
     *
     * This method:
     * - Publishes configuration files
     * - Validates configuration
     * - Sets up development mode helpers
     */
    public function boot(): void
    {
        $this->publishConfiguration();
        $this->validateConfiguration();
        $this->setupDevelopmentHelpers();
        $this->registerCommands();
    }

    /**
     * Register and merge package configuration.
     */
    protected function registerConfiguration(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'modulite');
    }

    /**
     * Register core services with proper dependency injection.
     */
    protected function registerCoreServices(): void
    {
        // Register UnifiedCacheManager with configuration
        $this->app->singleton(CacheManagerInterface::class, function (Application $app)
        {
            $config = $app['config']->get('modulite.cache');
            return new UnifiedCacheManager($config);
        });

        // Register ModuleResolver based on configuration
        $this->app->singleton(
            ModuleResolverInterface::class,
            fn(Application $app) => (new CreateModuleResolver())->handle($app)
        );

        // Register PanelScannerService with dependencies
        $this->app->singleton(PanelScannerInterface::class, function (Application $app)
        {
            $config        = $app['config']->get('modulite');
            $basePath      = $app->basePath();
            $moduleManager = $this->getModuleManager($app);

            return new PanelScannerService($config, $basePath, $moduleManager);
        });

        // Register ComponentDiscoveryService with dependencies
        $this->app->singleton(ComponentScannerInterface::class, function (Application $app)
        {
            $cacheManager   = $app->make(CacheManagerInterface::class);
            $moduleResolver = $app->make(ModuleResolverInterface::class);
            return new ComponentDiscoveryService($cacheManager, $moduleResolver);
        });
    }

    /**
     * Register panel discovery mechanism.
     *
     * In production, panels are discovered immediately to ensure routes are cached.
     * In development, discovery is deferred until Filament is resolved for performance.
     */
    protected function registerPanelDiscovery(): void
    {
        if (!$this->shouldPerformDiscovery())
        {
            return;
        }

        $this->discoverAndRegisterPanels();
    }

    /**
     * Discover and register all Filament Panel Providers.
     *
     * This method uses the new service-based architecture:
     * - CacheManager for multi-layer caching
     * - PanelScannerService for file discovery
     * - Proper error handling and logging
     * @throws Throwable
     */
    protected function discoverAndRegisterPanels(): void
    {
        try
        {
            /** @var CacheManagerInterface $cacheManager */
            $cacheManager = $this->app->make(CacheManagerInterface::class);

            // Generate cache key based on enabled modules
            $cacheKey = $this->generateCacheKey();

            // Fast path: Check cache first without loading scanner
            $panelClasses = $cacheManager->get($cacheKey);
            if (null === $panelClasses)
            {
                // Cache miss: Load scanner and perform discovery
                /** @var PanelScannerInterface $scanner */
                $scanner      = $this->app->make(PanelScannerInterface::class);
                $panelClasses = $scanner->discoverPanels();
                $cacheManager->put($cacheKey, $panelClasses);

                // Log only when actually scanning (development)
                $this->logDiscoverySuccess($panelClasses, $scanner->getScanStats());
            }

            // Register discovered panel providers
            $this->registerPanelProviders($panelClasses);

        } catch (Throwable $e)
        {
            $this->handleDiscoveryError($e);
        }
    }

    /**
     * Register individual panel provider classes.
     *
     * @param array<string> $panelClasses Array of panel provider class names
     */
    protected function registerPanelProviders(array $panelClasses): void
    {

        foreach ($panelClasses as $providerClass)
        {
            try
            {
                $this->app->register($providerClass);
            } catch (Throwable $e)
            {
                $this->handleProviderRegistrationError($providerClass, $e);
            }
        }
    }

    /**
     * Generate cache key based on current module state.
     *
     * The cache key includes:
     * - Enabled module list and their configuration
     * - Module resolver approach
     * - Configuration hash
     * - Application environment
     */
    protected function generateCacheKey(): string
    {
        // Use the module resolver to get enabled modules
        $moduleResolver = $this->app->make(ModuleResolverInterface::class);
        $enabledModules = $moduleResolver->getEnabledModules();

        // Include module names - use simple array for consistent hashing
        $moduleData = $enabledModules->sort()->values()->toArray();

        // Include the module resolver approach in cache key
        $approach = config('modulite.modules.approach');

        // Include configuration in cache key
        $configHash = md5(serialize([
            'panels'     => config('modulite.panels', []),
            'components' => config('modulite.components', []),
            'modules'    => config('modulite.modules', [])
        ]));

        // Include environment
        $environment = $this->app->environment();

        $keyData = [
            'modules'     => $moduleData,
            'approach'    => $approach,
            'config'      => $configHash,
            'environment' => $environment,
        ];

        return 'panels:'.md5(serialize($keyData));
    }

    /**
     * Get module manager instance safely.
     */
    protected function getModuleManager(Application $app): mixed
    {
        try
        {
            return $app->bound(self::NWIDART_MODULES_NAMESPACE)
                ? $app[self::NWIDART_MODULES_NAMESPACE]
                : null;
        } catch (Throwable)
        {
            return null;
        }
    }

    /**
     * Register cache invalidation event listeners.
     *
     * This ensures cache is cleared when modules are:
     * - Enabled/disabled
     * - Installed/uninstalled
     * - Updated
     */
    protected function registerCacheInvalidationListeners(): void
    {
        $triggers = config('modulite.cache.invalidation.triggers', []);

        foreach ($triggers as $event)
        {
            $this->app['events']->listen($event, function (): void
            {
                $this->invalidateCache();
            });
        }

        // Also listen to config cache clear
        $this->app['events']->listen('config:cache:cleared', function (): void
        {
            $this->invalidateCache();
        });
    }

    /**
     * Invalidate all Modulite caches.
     */
    protected function invalidateCache(): void
    {
        try
        {
            /** @var CacheManagerInterface $cacheManager */
            $cacheManager = $this->app->make(CacheManagerInterface::class);
            $cacheManager->flush();
        } catch (Throwable $e)
        {
            // Log but don't throw - cache invalidation shouldn't break the app
            if (config('modulite.logging.enabled', false))
            {
                Log::channel(config('modulite.logging.channel', 'default'))
                    ->warning('Failed to invalidate Modulite cache', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Publish configuration file to application.
     */
    protected function publishConfiguration(): void
    {
        if ($this->app->runningInConsole())
        {
            $this->publishes([
                self::CONFIG_PATH => config_path('modulite.php'),
            ], ['modulite', 'modulite-config']);
        }
    }

    /**
     * Validate configuration and warn about issues.
     */
    protected function validateConfiguration(): void
    {
        if (!config('app.debug', false))
        {
            return;
        }

        $config = config('modulite', []);

        // Validate panel scan locations exist
        $panelLocations = $config['panels']['locations'] ?? [];
        foreach ($panelLocations as $location)
        {
            if (!str_contains($location, '*'))
            {
                $fullPath = $this->app->basePath($location);
                if (!is_dir($fullPath))
                {
                    Log::channel(config('modulite.logging.channel', 'default'))
                        ->warning("Modulite panel scan location does not exist: {$fullPath}");
                }
            }
        }

        // Validate component scan locations exist
        $componentLocations = $config['components']['locations'] ?? [];
        foreach ($componentLocations as $location)
        {
            if (!str_contains($location, '*'))
            {
                $fullPath = $this->app->basePath($location);
                if (!is_dir($fullPath))
                {
                    Log::channel(config('modulite.logging.channel', 'default'))
                        ->warning("Modulite component scan location does not exist: {$fullPath}");
                }
            }
        }

        // Cache configuration validation removed as we use file-based cache only
    }

    /**
     * Setup development mode helpers.
     */
    protected function setupDevelopmentHelpers(): void
    {
        // Development helpers are now registered in registerCommands()
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        // Only register commands in console environment
        if ($this->app->runningInConsole())
        {
            $this->commands([
                ModuliteClearCacheCommand::class,
                ModuliteStatusCommand::class,
                ModuliteOptimizeCommand::class,
            ]);

            // Register optimization commands with Laravel's optimize system
            $this->optimizes('modulite:cache', clear: 'modulite:clear');
        }
    }

    /**
     * Check if panel discovery should be performed.
     */
    protected function shouldPerformDiscovery(): bool
    {
        // Check if Filament is available
        if (!class_exists(FilamentServiceProvider::class))
        {
            return false;
        }

        // In production, always perform discovery to ensure routes are cached properly
        if ('production' === config('app.env'))
        {
            return true;
        }

        // Don't discover in console unless explicitly needed (development only)
        if ($this->app->runningInConsole())
        {
            return true;
        }

        // Check configuration
        return config('modulite.performance.lazy_discovery', true);
    }

    /**
     * Log successful panel discovery.
     *
     * @param array<string> $panels
     * @param array<string, mixed> $stats
     */
    protected function logDiscoverySuccess(array $panels, array $stats): void
    {
        if (!config('modulite.logging.log_discovery_time', false))
        {
            return;
        }

        Log::channel(config('modulite.logging.channel', 'default'))
            ->info('Modulite panel discovery completed successfully', [
                'panel_count' => count($panels),
                'scan_stats'  => $stats,
            ]);
    }

    /**
     * Handle panel discovery errors.
     * @throws Throwable
     */
    protected function handleDiscoveryError(Throwable $e): void
    {
        $failSilently = config('modulite.error_handling.fail_silently', false);

        if (config('modulite.logging.enabled', false))
        {
            Log::channel(config('modulite.logging.channel', 'default'))
                ->error('Modulite panel discovery failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
        }

        if (!$failSilently)
        {
            throw $e;
        }
    }

    /**
     * Handle individual provider registration errors.
     */
    protected function handleProviderRegistrationError(string $providerClass, Throwable $e): void
    {
        if (config('modulite.logging.enabled', false))
        {
            Log::channel(config('modulite.logging.channel', 'default'))
                ->warning('Failed to register panel provider', [
                    'provider' => $providerClass,
                    'error'    => $e->getMessage(),
                ]);
        }

        // Continue with other providers even if one fails
    }
}
