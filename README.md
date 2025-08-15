# 🚀 Modulite

[![Latest Version on Packagist](https://img.shields.io/packagist/v/panicdevs/modulite.svg?style=flat-square)](https://packagist.org/packages/panicdevs/modulite)
[![Total Downloads](https://img.shields.io/packagist/dt/panicdevs/modulite.svg?style=flat-square)](https://packagist.org/packages/panicdevs/modulite)
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/panicdevs?style=flat-square&logo=github)](https://github.com/sponsors/panicdevs)

**Automatic Filament Panel Provider discovery for modular Laravel applications with multi-layer caching and performance optimization.**

Modulite revolutionizes how you organize and manage Filament panels in modular Laravel applications. It automatically discovers and registers your Filament Panel Providers across modules, eliminating boilerplate configuration while maintaining peak performance through intelligent caching.

## ✨ Features

- 🔍 **Auto-Discovery**: Automatically find and register Filament Panel Providers across modules
- ⚡ **Performance Optimized**: Multi-layer caching with production-grade optimizations
- 🏗️ **Modular Architecture**: Perfect for nwidart/laravel-modules and modular applications
- 🎯 **Attribute-Based**: Use `#[FilamentPanel]` attributes for clean, explicit registration
- 🔧 **Flexible Configuration**: Comprehensive configuration options for every use case
- 📊 **Component Discovery**: Auto-discover Resources, Pages, and Widgets for existing panels
- 🛡️ **Production Ready**: Built for enterprise applications with robust error handling
- 🔄 **Smart Caching**: File-based cache system similar to Laravel's bootstrap cache
- 📈 **Performance Monitoring**: Built-in benchmarking and performance analysis tools
- 🌍 **Environment Aware**: Different configurations for development and production

## 🎯 Quick Start

### Installation

Install via Composer:

```bash
composer require panicdevs/modulite
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=modulite-config
```

### Basic Usage

1. **Mark your Panel Provider with the attribute:**

```php
<?php

namespace Modules\Admin\Providers\Filament\Panels;

use Filament\Panel;
use Filament\PanelProvider;
use PanicDevs\Modulite\Attributes\FilamentPanel;

#[FilamentPanel]
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->colors([
                'primary' => '#1f2937',
            ]);
    }
}
```

2. **That's it!** Modulite will automatically discover and register your panel.

### Advanced Usage

For more control over panel registration:

```php
#[FilamentPanel(
    priority: 10,
    environment: 'production',
    autoRegister: true
)]
class ManagerPanelProvider extends PanelProvider
{
    // Panel configuration...
}
```

## 📚 Table of Contents

- [Installation & Setup](#-installation--setup)
- [Configuration Guide](#-configuration-guide)
- [Usage Examples](#-usage-examples)
- [Component Discovery](#-component-discovery)
- [Performance Optimization](#-performance-optimization)
- [Commands & Tools](#-commands--tools)
- [Troubleshooting](#-troubleshooting)
- [API Reference](#-api-reference)
- [Contributing](#-contributing)

## 🛠️ Installation & Setup

### Requirements

- PHP 8.2 or higher
- Laravel 10.0 or higher
- Filament 4.0 or higher
- nwidart/laravel-modules (recommended)

### Step-by-Step Installation

1. **Install the package:**

```bash
composer require panicdevs/modulite
```

2. **Publish configuration (optional):**

```bash
php artisan vendor:publish --tag=modulite-config
```

3. **Create your first panel:**

```bash
php artisan make:filament-panel admin
```

4. **Add the FilamentPanel attribute:**

```php
use PanicDevs\Modulite\Attributes\FilamentPanel;

#[FilamentPanel]
class AdminPanelProvider extends PanelProvider
{
    // Your panel configuration
}
```

5. **Verify everything works:**

```bash
php artisan modulite:status
```

### Laravel Sail & Docker

Modulite works seamlessly with Laravel Sail and Docker environments. No special configuration required.

## ⚙️ Configuration Guide

### Configuration File Overview

The configuration file `config/modulite.php` contains comprehensive options:

```php
return [
    'panels' => [
        'locations' => [
            'modules/*/Providers/Filament/Panels',
            'foundation/*/Providers/Filament/Panels',
        ],
        'patterns' => [
            'files'   => ['*PanelProvider.php', '*Panel.php'],
            'classes' => ['*PanelProvider', '*Panel'],
        ],
        // ... more options
    ],
    'cache' => [
        'enabled' => env('MODULITE_CACHE_ENABLED', !app()->hasDebugModeEnabled()),
        'file' => base_path('bootstrap/cache/modulite.php'),
        'ttl' => env('MODULITE_CACHE_TTL', app()->hasDebugModeEnabled() ? 300 : 0),
    ],
    // ... more configurations
];
```

### Key Configuration Sections

#### Panel Discovery

Configure where and how panels are discovered:

```php
'panels' => [
    'locations' => [
        'modules/*/Providers/Filament/Panels',  // Your module structure
        'app/Filament/Panels',                   // App-level panels
    ],
    'patterns' => [
        'files'   => ['*PanelProvider.php'],    // File naming patterns
        'classes' => ['*PanelProvider'],        // Class naming patterns
    ],
    'validation' => [
        'strict_inheritance' => false,           // Require exact Filament classes
        'must_extend' => 'Filament\PanelProvider',
    ],
]
```

#### Cache Configuration

Optimize performance with intelligent caching:

```php
'cache' => [
    'enabled' => env('MODULITE_CACHE_ENABLED', !app()->hasDebugModeEnabled()),
    'file' => base_path('bootstrap/cache/modulite.php'),
    'ttl' => env('MODULITE_CACHE_TTL', 0), // 0 = never expires (production)
    'auto_invalidate' => app()->hasDebugModeEnabled(),
]
```

#### Performance Settings

Fine-tune performance for your use case:

```php
'performance' => [
    'lazy_discovery' => true,              // Defer scanning until needed
    'memory_optimization' => [
        'batch_size' => 100,
        'clear_stat_cache' => true,
        'gc_after_scan' => true,
    ],
]
```

### Environment Variables

Control Modulite behavior via environment variables:

```env
# Cache Configuration
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0

# Performance
MODULITE_LAZY_DISCOVERY=true

# Error Handling
MODULITE_FAIL_SILENTLY=false

# Logging
MODULITE_LOGGING_ENABLED=false
MODULITE_LOG_CHANNEL=stack
MODULITE_LOG_LEVEL=info
```

### Production Configuration

For optimal production performance:

```env
# .env.production
APP_DEBUG=false
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0
MODULITE_LAZY_DISCOVERY=true
MODULITE_FAIL_SILENTLY=true
MODULITE_LOGGING_ENABLED=false
```

Then run:

```bash
php artisan optimize
php artisan config:cache
```

## 🎨 Usage Examples

### Basic Panel Creation

Create a simple admin panel:

```php
<?php

namespace Modules\Admin\Providers\Filament\Panels;

use Filament\Panel;
use Filament\PanelProvider;
use PanicDevs\Modulite\Attributes\FilamentPanel;

#[FilamentPanel]
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login()
            ->colors([
                'primary' => '#1f2937',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ]);
    }
}
```

### Multi-Environment Panels

Create panels that only register in specific environments:

```php
#[FilamentPanel(environment: 'local')]
class DevelopmentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dev')
            ->path('/dev')
            ->colors(['primary' => '#10b981']);
    }
}

#[FilamentPanel(environment: 'production', priority: 100)]
class ProductionPanelProvider extends PanelProvider
{
    // Production-optimized panel configuration
}
```

### Conditional Panel Registration

Use custom conditions for panel registration:

```php
#[FilamentPanel(
    conditions: ['feature.admin_panel_enabled'],
    priority: 50
)]
class ConditionalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Only registers if condition is met
        return $panel->id('conditional');
    }
}
```

### Priority-Based Loading

Control panel registration order:

```php
#[FilamentPanel(priority: 100)]  // Loads first
class CorePanelProvider extends PanelProvider { }

#[FilamentPanel(priority: 50)]   // Loads second
class ManagerPanelProvider extends PanelProvider { }

#[FilamentPanel(priority: 10)]   // Loads third
class UserPanelProvider extends PanelProvider { }
```

## 🧩 Component Discovery

Modulite can automatically discover and register Filament components (Resources, Pages, Widgets) for existing panels.

### Configuration

Enable component discovery in your configuration:

```php
'components' => [
    'locations' => [
        'modules/*/Filament/{panel}/Resources',
        'modules/*/Filament/{panel}/Pages',
        'modules/*/Filament/{panel}/Widgets',
    ],
    'types' => [
        'resources' => [
            'enabled' => true,
            'naming_pattern' => '*Resource.php',
        ],
        'pages' => [
            'enabled' => true,
            'naming_pattern' => '*Page.php',
        ],
        'widgets' => [
            'enabled' => true,
            'naming_pattern' => '*Widget.php',
        ],
    ],
]
```

### Directory Structure

Organize your components following this structure:

```
modules/
├── User/
│   └── Filament/
│       ├── Admin/
│       │   ├── Resources/
│       │   │   └── UserResource.php
│       │   ├── Pages/
│       │   │   └── DashboardPage.php
│       │   └── Widgets/
│       │       └── UserStatsWidget.php
│       └── Manager/
│           ├── Resources/
│           └── Pages/
└── Blog/
    └── Filament/
        └── Admin/
            ├── Resources/
            │   └── PostResource.php
            └── Pages/
```

### Component Example

```php
<?php

namespace Modules\User\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Modules\User\Entities\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    // Resource implementation...
}
```

### Using Component Discovery

```php
// In your service provider or panel configuration
use PanicDevs\Modulite\Services\ComponentDiscoveryService;

$componentDiscovery = app(ComponentDiscoveryService::class);

// Discover all components for a specific panel
$components = $componentDiscovery->discoverComponents('admin');

// Discover specific component types
$resources = $componentDiscovery->discoverResources('admin');
$pages = $componentDiscovery->discoverPages('admin');
$widgets = $componentDiscovery->discoverWidgets('admin');
```

## ⚡ Performance Optimization

### Production Setup

For maximum performance in production:

1. **Configure caching:**

```env
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0  # Never expires
```

2. **Optimize Laravel:**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

3. **Enable OPcache** in your PHP configuration:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### Performance Monitoring

Use the built-in benchmark command:

```bash
# Basic benchmark
php artisan modulite:benchmark

# Detailed benchmark with warm cache
php artisan modulite:benchmark --warm-cache --show-details --iterations=1000
```

Sample output:

```
🚀 Modulite Performance Benchmark
=====================================

Environment Information:
+----------------+--------------------------------------------+
| Setting        | Value                                      |
+----------------+--------------------------------------------+
| Environment    | production                                 |
| Debug Mode     | ✗ Disabled                                 |
| Cache Enabled  | ✓ Enabled                                  |
| Cache File     | /var/www/html/bootstrap/cache/modulite.php |
| Cache TTL      | 0                                          |
| Lazy Discovery | ✓ Enabled                                  |
| PHP Version    | 8.4.11                                     |
+----------------+--------------------------------------------+

📈 Benchmark Results:
+----------------------+------------+
| Metric               | Value      |
+----------------------+------------+
| Operation            | Cache Read |
| Average (ms)         | 0          |
| Median (ms)          | 0          |
| Max (ms)             | 0.02       |
| 95th Percentile (ms) | 0          |
+----------------------+------------+

+----------------------+-----------------+
| Operation            | Panel Discovery |
| Average (ms)         | 0               |
| Median (ms)          | 0               |
| Max (ms)             | 0.011           |
+----------------------+-----------------+

💡 Performance Recommendations:
✅ Cache performance is excellent (0ms average)
✅ Panel discovery performance is good (0ms average)
```

### Memory Optimization

For large applications with many modules:

```php
'performance' => [
    'memory_optimization' => [
        'batch_size' => 50,        // Process files in smaller batches
        'clear_stat_cache' => true, // Clear file stat cache regularly
        'gc_after_scan' => true,    // Force garbage collection
    ],
    'concurrent' => [
        'enabled' => false,        // Enable for very large codebases
        'max_workers' => 4,
    ],
]
```

### Cache Strategies

**Development (auto-invalidation):**
```php
'cache' => [
    'ttl' => 300,              // 5 minutes
    'auto_invalidate' => true, // Clear on file changes
]
```

**Production (persistent cache):**
```php
'cache' => [
    'ttl' => 0,                // Never expires
    'auto_invalidate' => false, // Manual cache clearing only
]
```

## 🔧 Commands & Tools

### Status Command

Check Modulite's current state:

```bash
php artisan modulite:status
```

Options:
- `--clear-cache`: Clear all Modulite cache
- `--scan`: Force rescan of panels and components
- `--vvv`: Show detailed information

### Cache Management

Clear Modulite cache:

```bash
php artisan modulite:clear-cache
```

### Benchmark Command

Performance testing and optimization:

```bash
# Basic benchmark
php artisan modulite:benchmark

# Advanced benchmarking
php artisan modulite:benchmark \
    --iterations=1000 \
    --warm-cache \
    --show-details
```

### Panel Discovery

Manually discover panels (useful for debugging):

```bash
php artisan modulite:discover-panels
```

### Component Discovery

Discover components for a specific panel:

```bash
php artisan modulite:discover-components admin
```

## 🐛 Troubleshooting

### Common Issues

#### Cache Not Working

**Problem:** Panels not being cached or cache misses.

**Solutions:**
1. Check cache directory permissions:
   ```bash
   chmod 755 bootstrap/cache
   ```

2. Verify cache configuration:
   ```bash
   php artisan modulite:status
   ```

3. Clear and rebuild cache:
   ```bash
   php artisan modulite:clear-cache
   php artisan optimize
   ```

#### Panels Not Discovered

**Problem:** Panel providers not being found.

**Diagnostic steps:**
1. Check file location matches configuration:
   ```php
   // config/modulite.php
   'panels' => [
       'locations' => [
           'modules/*/Providers/Filament/Panels', // Check this path
       ],
   ]
   ```

2. Verify attribute is present:
   ```php
   #[FilamentPanel] // Must be present
   class YourPanelProvider extends PanelProvider
   ```

3. Check class naming pattern:
   ```php
   'patterns' => [
       'files' => ['*PanelProvider.php'], // Must match filename
   ]
   ```

4. Run discovery command:
   ```bash
   php artisan modulite:discover-panels --verbose
   ```

#### Performance Issues

**Problem:** Slow panel loading or discovery.

**Solutions:**
1. Enable caching in production:
   ```env
   MODULITE_CACHE_ENABLED=true
   MODULITE_CACHE_TTL=0
   ```

2. Use lazy discovery:
   ```env
   MODULITE_LAZY_DISCOVERY=true
   ```

3. Optimize scan locations (avoid deep directory structures):
   ```php
   'scanning' => [
       'max_depth' => 3,
       'excluded_directories' => ['tests', 'vendor', 'node_modules'],
   ]
   ```

4. Run benchmark to identify bottlenecks:
   ```bash
   php artisan modulite:benchmark --show-details
   ```

#### Memory Issues

**Problem:** High memory usage during discovery.

**Solutions:**
1. Enable memory optimization:
   ```php
   'performance' => [
       'memory_optimization' => [
           'batch_size' => 50,
           'gc_after_scan' => true,
       ],
   ]
   ```

2. Increase PHP memory limit temporarily:
   ```bash
   php -d memory_limit=512M artisan modulite:status
   ```

### Debugging Tools

#### Enable Debug Logging

```env
MODULITE_LOGGING_ENABLED=true
MODULITE_LOG_LEVEL=debug
```

#### Verbose Status Check

```bash
php artisan modulite:status --vvv
```

#### Cache Analysis

```bash
php artisan modulite:benchmark --show-details
```

### Environment-Specific Issues

#### Docker/Sail Issues

- Ensure proper file permissions in containers
- Check volume mounts for cache directories
- Verify PHP extensions are installed

#### Production Issues

- Always use `APP_DEBUG=false`
- Enable OPcache for better performance
- Use `php artisan optimize` after deployment

## 📖 API Reference

### FilamentPanel Attribute

```php
#[FilamentPanel(
    priority: int = 0,              // Registration priority
    environment: ?string = null,    // Target environment
    conditions: array = [],         // Custom conditions
    autoRegister: bool = true       // Auto-registration flag
)]
```

**Methods:**
- `shouldRegister(string $environment): bool` - Check if panel should register
- `toArray(): array` - Get attribute configuration

### Cache Manager Interface

```php
interface CacheManagerInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value, ?int $ttl = null): bool;
    public function remember(string $key, callable $callback): array;
    public function forget(string $key): void;
    public function flush(): void;
    public function has(string $key): bool;
    public function getStats(): array;
}
```

### Panel Scanner Interface

```php
interface PanelScannerInterface
{
    public function discoverPanels(): array;
    public function getScanStats(): array;
}
```

### Component Scanner Interface

```php
interface ComponentScannerInterface
{
    public function discoverComponents(string $panelName): array;
    public function discoverComponentType(string $panelName, string $componentType): array;
    public function getScanStats(): array;
    public function isComponentType(string $className, string $componentType): bool;
}
```

### Service Examples

#### Using the Panel Scanner

```php
use PanicDevs\Modulite\Contracts\PanelScannerInterface;

$scanner = app(PanelScannerInterface::class);
$panels = $scanner->discoverPanels();

foreach ($panels as $panelClass) {
    // Register panel
    app()->register($panelClass);
}
```

#### Using Component Discovery

```php
use PanicDevs\Modulite\Services\ComponentDiscoveryService;

$discovery = new ComponentDiscoveryService();

// Discover all components for admin panel
$components = $discovery->discoverComponents('admin');

// Get specific component types
$resources = $discovery->discoverResources('admin');
$pages = $discovery->discoverPages('admin');
$widgets = $discovery->discoverWidgets('admin');
```

#### Cache Management

```php
use PanicDevs\Modulite\Contracts\CacheManagerInterface;

$cache = app(CacheManagerInterface::class);

// Store data
$cache->put('panels:admin', $panelData, 3600);

// Retrieve data
$data = $cache->get('panels:admin', []);

// Remember pattern
$panels = $cache->remember('discovered_panels', function() {
    return $this->scanForPanels();
});

// Clear cache
$cache->flush();
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Check code style: `composer cs-check`

### Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test suite
./vendor/bin/phpunit tests/Unit/PanelDiscoveryTest.php
```

### Code Style

We use Laravel Pint for code formatting:

```bash
composer cs-fix
```

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## 🔒 Security

If you discover any security-related issues, please email security@panicdevs.agency instead of using the issue tracker.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 💖 Support

- ⭐ Star this repository
- 🐛 [Report bugs](https://github.com/panicdevs/modulite/issues)
- 💡 [Request features](https://github.com/panicdevs/modulite/issues)
- 💰 [Sponsor development](https://github.com/sponsors/panicdevs)

## 🙏 Credits

- **[Armin Hooshmand](https://github.com/NotifyHex)** - Creator & Maintainer
- **[PanicDevs](https://panicdevs.agency)** - Organization
- All [contributors](https://github.com/panicdevs/modulite/contributors)

Built with ❤️ by [PanicDevs](https://panicdevs.agency)

---

<div align="center">

**[Documentation](https://github.com/panicdevs/modulite#readme) • [Installation](https://github.com/panicdevs/modulite#installation--setup) • [Configuration](https://github.com/panicdevs/modulite#configuration-guide) • [Examples](https://github.com/panicdevs/modulite#usage-examples)**

</div>
