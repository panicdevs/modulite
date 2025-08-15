# 📚 API Reference

Complete API reference for Modulite's interfaces, services, and attributes.

## 📋 Table of Contents

- [Attributes](#attributes)
- [Interfaces](#interfaces)
- [Services](#services)
- [Console Commands](#console-commands)
- [Exceptions](#exceptions)
- [Configuration](#configuration)
- [Helper Functions](#helper-functions)

## Attributes

### FilamentPanel

The `FilamentPanel` attribute marks classes for automatic discovery and registration as Filament Panel Providers.

```php
#[Attribute(Attribute::TARGET_CLASS)]
readonly class FilamentPanel
```

#### Constructor Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `priority` | `int` | `0` | Registration priority (higher = earlier registration) |
| `environment` | `?string` | `null` | Limit panel to specific environment |
| `conditions` | `array` | `[]` | Additional conditions for panel loading |
| `autoRegister` | `bool` | `true` | Whether to automatically register this panel |

#### Methods

##### `shouldRegister(string $currentEnvironment): bool`

Determines if the panel should be registered in the current environment.

**Parameters:**
- `$currentEnvironment` - Current application environment

**Returns:** `bool` - True if panel should be registered

**Example:**
```php
$attribute = new FilamentPanel(environment: 'production');
$shouldRegister = $attribute->shouldRegister(app()->environment()); // true in production
```

##### `toArray(): array`

Returns attribute configuration as an array.

**Returns:** `array<string, mixed>` - Attribute configuration

**Example:**
```php
$attribute = new FilamentPanel(priority: 10, environment: 'local');
$config = $attribute->toArray();
// ['priority' => 10, 'environment' => 'local', 'conditions' => [], 'auto_register' => true]
```

#### Usage Examples

**Basic Usage:**
```php
#[FilamentPanel]
class AdminPanelProvider extends PanelProvider
{
    // Panel implementation
}
```

**Advanced Usage:**
```php
#[FilamentPanel(
    priority: 100,
    environment: 'production',
    conditions: ['feature.admin_panel'],
    autoRegister: true
)]
class ProductionAdminPanelProvider extends PanelProvider
{
    // Panel implementation
}
```

## Interfaces

### CacheManagerInterface

Interface for cache management operations.

```php
interface CacheManagerInterface
```

#### Methods

##### `get(string $key, mixed $default = null): mixed`

Retrieve an item from the cache.

**Parameters:**
- `$key` - Cache key
- `$default` - Default value if key not found

**Returns:** `mixed` - Cached value or default

##### `put(string $key, mixed $value, ?int $ttl = null): bool`

Store an item in the cache.

**Parameters:**
- `$key` - Cache key
- `$value` - Value to store
- `$ttl` - Time to live in seconds (null for default)

**Returns:** `bool` - Success status

##### `remember(string $key, callable $callback): array`

Get an item from cache or store the result of callback.

**Parameters:**
- `$key` - Cache key
- `$callback` - Callback to execute if cache miss

**Returns:** `array` - Cached or computed value

##### `forget(string $key): void`

Remove an item from the cache.

**Parameters:**
- `$key` - Cache key to remove

##### `flush(): void`

Clear all cached items.

##### `has(string $key): bool`

Check if cache has an item.

**Parameters:**
- `$key` - Cache key to check

**Returns:** `bool` - True if key exists

##### `getStats(): array`

Get cache statistics.

**Returns:** `array<string, mixed>` - Cache statistics

### PanelScannerInterface

Interface for panel discovery operations.

```php
interface PanelScannerInterface
```

#### Methods

##### `discoverPanels(): array`

Discover all Filament Panel classes.

**Returns:** `array<string>` - Array of fully qualified class names

**Throws:** `ScanException` - When scanning fails

##### `getScanStats(): array`

Get scanning statistics.

**Returns:** `array<string, mixed>` - Statistics from last scan

### ComponentScannerInterface

Interface for component discovery operations.

```php
interface ComponentScannerInterface
```

#### Methods

##### `discoverComponents(string $panelName): array`

Discover all components for a specific panel.

**Parameters:**
- `$panelName` - Panel identifier

**Returns:** `array` - Components grouped by type

##### `discoverComponentType(string $panelName, string $componentType): array`

Discover components of a specific type.

**Parameters:**
- `$panelName` - Panel identifier
- `$componentType` - Component type ('resources', 'pages', 'widgets')

**Returns:** `array<string>` - Array of component class names

##### `getScanStats(): array`

Get component discovery statistics.

**Returns:** `array<string, mixed>` - Statistics from last scan

##### `isComponentType(string $className, string $componentType): bool`

Check if a class is a valid component of specified type.

**Parameters:**
- `$className` - Fully qualified class name
- `$componentType` - Component type to check

**Returns:** `bool` - True if class is valid component

## Services

### UnifiedCacheManager

Main cache implementation using file-based storage.

```php
class UnifiedCacheManager implements CacheManagerInterface
```

#### Constructor

```php
public function __construct(array $config = [])
```

**Parameters:**
- `$config` - Cache configuration array

#### Methods

##### `getCacheFile(): string`

Get the cache file path.

**Returns:** `string` - Absolute path to cache file

##### `isEnabled(): bool`

Check if caching is enabled.

**Returns:** `bool` - Cache enabled status

##### `isCacheEnabled(): bool`

Alias for `isEnabled()`. Interface-compliant method.

**Returns:** `bool` - Cache enabled status

#### Configuration

```php
$config = [
    'file' => base_path('bootstrap/cache/modulite.php'),
    'enabled' => true,
    'ttl' => 3600,
];

$cache = new UnifiedCacheManager($config);
```

### PanelScannerService

Service for discovering Filament Panel Providers.

```php
class PanelScannerService implements PanelScannerInterface
```

#### Constructor

```php
public function __construct(array $config, string $basePath, mixed $moduleManager = null)
```

**Parameters:**
- `$config` - Scanner configuration
- `$basePath` - Application base path
- `$moduleManager` - Optional module manager instance

#### Methods

##### `discoverPanels(): array`

Discovers all panel providers with `#[FilamentPanel]` attribute.

**Returns:** `array<string>` - Panel provider class names

**Example:**
```php
$scanner = app(PanelScannerInterface::class);
$panels = $scanner->discoverPanels();
// ['Modules\Admin\Providers\Filament\Panels\AdminPanelProvider', ...]
```

##### `getScanStats(): array`

Get detailed statistics from the last scan operation.

**Returns:** `array<string, mixed>` - Scan statistics

**Example Response:**
```php
[
    'files_scanned' => 25,
    'classes_found' => 8,
    'panels_discovered' => 3,
    'scan_time' => 0.156,
    'errors' => 0,
]
```

### ComponentDiscoveryService

Service for discovering Filament components.

```php
class ComponentDiscoveryService implements ComponentScannerInterface
```

#### Constructor

```php
public function __construct(CacheManagerInterface $cache = null)
```

**Parameters:**
- `$cache` - Optional cache manager instance

#### Methods

##### `discoverComponentsForPanel(string $panelId): array`

Discover all components for a specific panel.

**Parameters:**
- `$panelId` - Panel identifier

**Returns:** `array` - Components grouped by type

##### `discoverResources(?string $panelId = null): Collection`

Discover Resource components.

**Parameters:**
- `$panelId` - Optional panel identifier

**Returns:** `Collection<string>` - Resource class names

##### `discoverPages(?string $panelId = null): Collection`

Discover Page components.

**Parameters:**
- `$panelId` - Optional panel identifier

**Returns:** `Collection<string>` - Page class names

##### `discoverWidgets(?string $panelId = null): Collection`

Discover Widget components.

**Parameters:**
- `$panelId` - Optional panel identifier

**Returns:** `Collection<string>` - Widget class names

##### `isValidComponent(string $className, string $type): bool`

Validate if a class is a proper component of specified type.

**Parameters:**
- `$className` - Fully qualified class name
- `$type` - Component type ('resources', 'pages', 'widgets')

**Returns:** `bool` - Validation result

##### `refreshCache(): void`

Clear component discovery cache.

#### Usage Examples

```php
use PanicDevs\Modulite\Services\ComponentDiscoveryService;

$discovery = new ComponentDiscoveryService();

// Discover all components for admin panel
$components = $discovery->discoverComponents('admin');
/*
[
    'resources' => ['App\Filament\Resources\UserResource', ...],
    'pages' => ['App\Filament\Pages\Dashboard', ...],
    'widgets' => ['App\Filament\Widgets\StatsWidget', ...]
]
*/

// Discover only resources
$resources = $discovery->discoverResources('admin');

// Check if class is valid resource
$isValid = $discovery->isValidComponent(UserResource::class, 'resources');
```

## Console Commands

### modulite:status

Display Modulite discovery status and statistics.

```bash
php artisan modulite:status [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--clear-cache` | Clear all Modulite cache before showing status |
| `--scan` | Force rescan of panels and components |
| `--vvv` | Show detailed information |

#### Example Output

```
Modulite Status Report
===================

Configuration:
┌─────────────────┬─────────┐
│ Setting         │ Value   │
├─────────────────┼─────────┤
│ Cache Enabled   │ ✓       │
│ Lazy Discovery  │ ✓       │
│ Logging Enabled │ ✗       │
└─────────────────┴─────────┘

Panel Discovery:
Discovered 3 panels:
  • AdminPanelProvider
  • UserPanelProvider
  • SupportPanelProvider
```

### modulite:clear-cache

Clear all Modulite cache files.

```bash
php artisan modulite:clear-cache
```

### modulite:benchmark

Benchmark Modulite performance.

```bash
php artisan modulite:benchmark [options]
```

#### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--iterations` | `100` | Number of benchmark iterations |
| `--warm-cache` | `false` | Warm up cache before testing |
| `--show-details` | `false` | Show detailed performance breakdown |

#### Example Output

```
🚀 Modulite Performance Benchmark
=====================================

📈 Benchmark Results:
┌─────────────────────┬─────────┐
│ Metric              │ Value   │
├─────────────────────┼─────────┤
│ Average (ms)        │ 1.234   │
│ 95th Percentile (ms)│ 2.456   │
│ Cache Hit Rate      │ 98.5%   │
└─────────────────────┴─────────┘
```

### modulite:discover-panels

Manually discover panels (debugging).

```bash
php artisan modulite:discover-panels [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--verbose` | Show detailed discovery information |

### modulite:discover-components

Discover components for a specific panel.

```bash
php artisan modulite:discover-components {panel} [options]
```

#### Arguments

| Argument | Description |
|----------|-------------|
| `panel` | Panel identifier to discover components for |

#### Options

| Option | Description |
|--------|-------------|
| `--type` | Filter by component type (resources, pages, widgets) |

## Exceptions

### ScanException

Thrown when scanning operations fail.

```php
class ScanException extends Exception
```

#### Usage

```php
use PanicDevs\Modulite\Exceptions\ScanException;

try {
    $panels = $scanner->discoverPanels();
} catch (ScanException $e) {
    Log::error('Panel discovery failed: ' . $e->getMessage());
}
```

### CacheException

Thrown when cache operations fail.

```php
class CacheException extends Exception
```

#### Usage

```php
use PanicDevs\Modulite\Exceptions\CacheException;

try {
    $cache->put('key', $value);
} catch (CacheException $e) {
    Log::error('Cache operation failed: ' . $e->getMessage());
}
```

## Configuration

### Configuration Structure

The configuration array structure for Modulite:

```php
return [
    'panels' => [
        'locations' => array,           // Scan locations for panels
        'patterns' => [
            'files' => array,           // File naming patterns
            'classes' => array,         // Class naming patterns
        ],
        'validation' => [
            'strict_inheritance' => bool,
            'must_extend' => string,
            'must_be_instantiable' => bool,
            'check_panel_method' => bool,
            'allow_custom_base_classes' => bool,
        ],
        'registration' => [
            'auto_register' => bool,
            'sort_by' => string,        // 'priority', 'name', 'none'
            'respect_environment' => bool,
            'validate_before_register' => bool,
        ],
        'scanning' => [
            'max_depth' => int,
            'follow_symlinks' => bool,
            'extensions' => array,
            'excluded_directories' => array,
        ],
    ],
    'components' => [
        'locations' => array,
        'types' => [
            'resources' => [
                'enabled' => bool,
                'strict_inheritance' => bool,
                'must_extend' => string,
                'naming_pattern' => string,
                'allow_custom_base_classes' => bool,
            ],
            // Similar for 'pages' and 'widgets'
        ],
        'registration' => [
            'auto_register' => bool,
            'sort_by' => string,
            'validate_before_register' => bool,
            'group_by_module' => bool,
        ],
        'scanning' => [
            'max_depth' => int,
            'follow_symlinks' => bool,
            'extensions' => array,
            'excluded_directories' => array,
        ],
    ],
    'cache' => [
        'enabled' => bool,
        'file' => string,
        'ttl' => int,
        'auto_invalidate' => bool,
        'memory_cache' => [
            'enabled' => bool,
            'max_items' => int,
        ],
    ],
    'performance' => [
        'lazy_discovery' => bool,
        'memory_optimization' => [
            'batch_size' => int,
            'clear_stat_cache' => bool,
            'gc_after_scan' => bool,
        ],
        'concurrent' => [
            'enabled' => bool,
            'max_workers' => int,
        ],
    ],
    'modules' => [
        'namespace' => string,
        'scan_only_enabled' => bool,
        'respect_module_priority' => bool,
        'status_cache_ttl' => int,
    ],
    'logging' => [
        'enabled' => bool,
        'channel' => string,
        'level' => string,
        'log_discovery_time' => bool,
        'log_cache_hits' => bool,
        'log_scan_stats' => bool,
    ],
    'error_handling' => [
        'fail_silently' => bool,
        'log_errors' => bool,
        'max_errors_per_scan' => int,
        'throw_on_invalid_class' => bool,
        'throw_on_missing_requirements' => bool,
    ],
];
```

### Environment Variables

Complete list of environment variables:

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `MODULITE_CACHE_ENABLED` | `bool` | `!debug` | Enable/disable caching |
| `MODULITE_CACHE_TTL` | `int` | `0` | Cache time-to-live in seconds |
| `MODULITE_LAZY_DISCOVERY` | `bool` | `true` | Enable lazy panel discovery |
| `MODULITE_AUTO_REGISTER_PANELS` | `bool` | `true` | Auto-register discovered panels |
| `MODULITE_AUTO_REGISTER_COMPONENTS` | `bool` | `true` | Auto-register discovered components |
| `MODULITE_STRICT_INHERITANCE` | `bool` | `false` | Require strict class inheritance |
| `MODULITE_ALLOW_CUSTOM_BASE_CLASSES` | `bool` | `true` | Allow custom base classes |
| `MODULITE_STRICT_COMPONENT_INHERITANCE` | `bool` | `false` | Strict component inheritance |
| `MODULITE_ALLOW_CUSTOM_RESOURCE_CLASSES` | `bool` | `true` | Allow custom resource classes |
| `MODULITE_ALLOW_CUSTOM_PAGE_CLASSES` | `bool` | `true` | Allow custom page classes |
| `MODULITE_ALLOW_CUSTOM_WIDGET_CLASSES` | `bool` | `true` | Allow custom widget classes |
| `MODULITE_FAIL_SILENTLY` | `bool` | `!debug` | Handle errors silently |
| `MODULITE_LOGGING_ENABLED` | `bool` | `debug` | Enable logging |
| `MODULITE_LOG_CHANNEL` | `string` | `stack` | Log channel to use |
| `MODULITE_LOG_LEVEL` | `string` | `info` | Log level |

## Helper Functions

### Service Resolution

Access Modulite services through Laravel's service container:

```php
// Get cache manager
$cache = app(\PanicDevs\Modulite\Contracts\CacheManagerInterface::class);

// Get panel scanner
$scanner = app(\PanicDevs\Modulite\Contracts\PanelScannerInterface::class);

// Get component scanner
$componentScanner = app(\PanicDevs\Modulite\Contracts\ComponentScannerInterface::class);
```

### Direct Service Instantiation

```php
use PanicDevs\Modulite\Services\UnifiedCacheManager;
use PanicDevs\Modulite\Services\PanelScannerService;
use PanicDevs\Modulite\Services\ComponentDiscoveryService;

// Create cache manager
$cache = new UnifiedCacheManager(config('modulite.cache'));

// Create panel scanner
$scanner = new PanelScannerService(
    config('modulite'),
    base_path(),
    app('modules') // nwidart module manager
);

// Create component discovery service
$discovery = new ComponentDiscoveryService($cache);
```

### Configuration Helpers

```php
// Get specific configuration
$cacheEnabled = config('modulite.cache.enabled');
$scanLocations = config('modulite.panels.locations');

// Check if feature is enabled
$lazyDiscovery = config('modulite.performance.lazy_discovery', true);

// Get environment-specific settings
$failSilently = config('modulite.error_handling.fail_silently', !app()->hasDebugModeEnabled());
```

### Cache Key Patterns

Modulite uses specific cache key patterns:

```php
// Panel cache keys
"panels:{hash}"                    // Main panel discovery cache
"panels:benchmark_{iteration}"     // Benchmark cache keys

// Component cache keys
"panel_components:{panel}"         // Panel-specific components
"discovered_resources:{panel}"     // Panel resources
"discovered_pages:{panel}"         // Panel pages
"discovered_widgets:{panel}"       // Panel widgets
"enabled_modules"                  // Enabled modules list
```

### Performance Monitoring

```php
// Measure discovery performance
$start = microtime(true);
$panels = $scanner->discoverPanels();
$duration = microtime(true) - $start;

echo "Discovery took: " . round($duration * 1000, 2) . "ms\n";

// Get cache statistics
$stats = $cache->getStats();
echo "Cache hit ratio: " . ($stats['hits'] / $stats['requests'] * 100) . "%\n";

// Get scan statistics
$scanStats = $scanner->getScanStats();
echo "Files scanned: " . $scanStats['files_scanned'] . "\n";
echo "Panels found: " . $scanStats['panels_discovered'] . "\n";
```

---

This API reference provides complete documentation for all public interfaces, classes, and methods in Modulite. For usage examples and best practices, see the [Examples Guide](EXAMPLES.md).
