# Modulite

**Automatic Filament Panel Provider discovery for modular Laravel applications with intelligent caching and performance optimization.**

Modulite automatically discovers and registers Filament panels and components across your modular application structure, eliminating the need for manual registration while providing production-ready performance optimizations.

## Features

- 🔍 **Automatic Discovery**: Finds Filament panels, resources, pages, and widgets across modules
- ⚡ **Production Optimized**: File-based caching system similar to Laravel's bootstrap cache
- 🏗️ **Modular Support**: Works with both `nwidart/laravel-modules` and `panicdevs/modules`
- 🎯 **Flexible Patterns**: Configurable naming patterns and discovery locations
- 📊 **Performance Insights**: Built-in commands for monitoring and optimization
- 🛡️ **Robust Error Handling**: Graceful failure modes for production environments

## Installation

Install via Composer:

```bash
composer require panicdevs/modulite
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=modulite-config
```

## Quick Start

1. **Install the package** (configuration publishes automatically)
2. **Register the plugin** in your Filament panel providers
3. **Structure your modules** following the expected patterns
4. **Run optimization** for production: `php artisan modulite:cache`

### Panel Registration

Add the `ModulitePlugin` to each panel where you want component discovery:

```php
// In your Panel Provider (e.g., AdminPanelProvider.php)
use PanicDevs\Modulite\Plugins\ModulitePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('/admin')
        ->plugins([
            ModulitePlugin::make(), // Add this to discover components
            // ... other plugins
        ])
        // ... other panel configuration
}
```

That's it! Modulite will automatically discover and register components for this panel.

## How It Works

### Discovery Process

Modulite works on two levels:

1. **Panel Providers**: Automatically discovered and registered by the service provider
2. **Components**: Discovered by the plugin for specific panels
   - **Resources**: Filament resource classes for CRUD operations
   - **Pages**: Custom Filament pages
   - **Widgets**: Dashboard widgets and components

### Module Structure

For a module named `User`, Modulite expects this structure:

```
modules/
├── User/
│   ├── Providers/
│   │   └── Filament/
│   │       └── Panels/
│   │           └── UserPanelProvider.php    # Panel definition
│   └── Filament/
│       ├── Admin/                           # For 'admin' panel
│       │   ├── Resources/
│       │   │   └── UserResource.php
│       │   ├── Pages/
│       │   │   └── UserDashboard.php
│       │   └── Widgets/
│       │       └── UserStatsWidget.php
│       └── Manager/                         # For 'manager' panel
│           └── Resources/
│               └── ProfileResource.php
```

### Registration Flow

1. **Bootstrap**: Service provider registers core services during Laravel boot
2. **Panel Discovery**: Panel providers are automatically discovered and registered
3. **Plugin Registration**: `ModulitePlugin` is registered with specific panels for component discovery
4. **Component Discovery**: When panel loads, plugin discovers components (resources, pages, widgets)
5. **Cache Check**: Fast path checks cache file first (per panel)
6. **Scan & Cache**: On cache miss, scans filesystem and caches results
7. **Component Registration**: Discovered components auto-register to the specific panel

## Configuration

### Cache Settings

Configure caching behavior for optimal performance:

```php
'cache' => [
    'enabled' => env('MODULITE_CACHE_ENABLED', !app()->hasDebugModeEnabled()),
    'file' => base_path('bootstrap/cache/modulite.php'),
    'ttl' => env('MODULITE_CACHE_TTL', app()->hasDebugModeEnabled() ? 300 : 0),
    'auto_invalidate' => app()->hasDebugModeEnabled(),
],
```

**Key Settings:**
- `enabled`: Master cache toggle (auto: off in development, on in production)
- `ttl`: Cache lifetime in seconds (0 = never expires, recommended for production)
- `auto_invalidate`: Automatically clear cache when files change (development only)

### Discovery Locations

Define where to scan for components:

```php
'panels' => [
    'locations' => [
        'modules/*/Providers/Filament/Panels',
        'foundation/*/Providers/Filament/Panels',
    ],
],

'components' => [
    'locations' => [
        'modules/*/Filament/{panel}/Resources',
        'modules/*/Filament/{panel}/Pages',
        'modules/*/Filament/{panel}/Widgets',
    ],
],
```

**Placeholders:**
- `*`: Module wildcard (e.g., `User`, `Blog`)
- `{panel}`: Panel ID placeholder (e.g., `Admin`, `Manager`)

### Validation Rules

Control how strict discovery validation should be:

```php
'panels' => [
    'validation' => [
        'strict_inheritance' => env('MODULITE_STRICT_INHERITANCE', false),
        'must_extend' => 'Filament\PanelProvider',
        'must_be_instantiable' => true,
        'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_BASE_CLASSES', true),
    ],
],
```

**When to Use:**
- `strict_inheritance => true`: Enforces exact class inheritance
- `allow_custom_base_classes => false`: Only allows direct Filament class inheritance
- Use strict settings for large teams to enforce conventions

### Module Integration

Choose your module management approach:

```php
'modules' => [
    'approach' => env('MODULITE_APPROACH', 'panicdevs'), // or 'nwidart'
    'scan_only_enabled' => true,
    'respect_module_priority' => true,
],
```

### Performance Optimization

Configure for production performance:

```php
'performance' => [
    'lazy_discovery' => env('MODULITE_LAZY_DISCOVERY', true),
    'memory_optimization' => [
        'batch_size' => 100,
        'clear_stat_cache' => true,
        'gc_after_scan' => true,
    ],
],
```

## Environment Variables

Set these in your `.env` for easy configuration:

```bash
# Cache Control
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0

# Performance
MODULITE_LAZY_DISCOVERY=true
MODULITE_STATIC_CACHING=true

# Validation
MODULITE_STRICT_INHERITANCE=false
MODULITE_ALLOW_CUSTOM_BASE_CLASSES=true

# Debugging
MODULITE_LOGGING_ENABLED=false
```

## Production Setup

### Optimization Commands

```bash
# Cache all discoveries for production
php artisan modulite:cache

# Clear caches when needed
php artisan modulite:cache --force

# Check status and performance
php artisan modulite:status

# Detailed diagnostics
php artisan modulite:status --vvv
```

### Deployment Workflow

1. **Build Assets**: Run your normal build process
2. **Cache Application**: `php artisan optimize`
3. **Cache Modulite**: `php artisan modulite:cache`
4. **Deploy**: Your cached discoveries are ready

### Cache Management

The cache system works like Laravel's bootstrap cache:

```bash
# Cache file location
bootstrap/cache/modulite.php

# Clear with Laravel caches
php artisan optimize:clear

# Or clear specifically
php artisan modulite:cache --force
```

## Troubleshooting

### Check Discovery Status

```bash
php artisan modulite:status
```

This shows:
- Configuration summary
- Module status
- Discovered panels and components
- Cache statistics

### Common Issues

**No panels discovered:**
- Check module structure matches expected patterns
- Verify panel classes extend `PanelProvider`
- Check if modules are enabled

**Performance issues:**
- Enable caching: `MODULITE_CACHE_ENABLED=true`
- Set TTL to 0 for production: `MODULITE_CACHE_TTL=0`
- Run `php artisan modulite:cache`

**Components not showing:**
- Verify directory structure matches panel patterns
- Check component naming (must end with `Resource`, `Page`, `Widget`)
- Ensure components extend proper Filament base classes

### Debug Mode

Enable detailed logging in development:

```bash
MODULITE_LOGGING_ENABLED=true
MODULITE_LOG_LEVEL=debug
```

### Cache Issues

Clear all caches if you encounter stale data:

```bash
php artisan modulite:cache --force
php artisan optimize:clear
```

## Performance

### Benchmarks

- **Cold start** (no cache): ~1-20ms depending on module count
- **Warm cache**: ~1-2ms (file include time)
- **Laravel response overhead**: <1ms when optimized

### Production Optimizations

Modulite automatically optimizes for production:

- Static caching eliminates repeated file reads
- Lazy discovery defers scanning until needed
- Single cache file minimizes I/O operations
- TTL of 0 prevents unnecessary expiration checks

## Advanced Usage

### Custom Base Classes

You can use custom base classes for components:

```php
'components' => [
    'types' => [
        'resources' => [
            'allow_custom_base_classes' => true,
            'strict_inheritance' => false,
        ],
    ],
],
```

### Manual Component Registration

For edge cases, disable auto-discovery and register manually:

```php
'components' => [
    'registration' => [
        'auto_register' => false,
    ],
],
```

### Multiple Panel Support

Modulite automatically handles multiple panels per module. Each panel needs the plugin registered for component discovery:

```php
// AdminPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->plugins([
            ModulitePlugin::make(),
        ]);
}

// ManagerPanelProvider.php  
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('manager')
        ->plugins([
            ModulitePlugin::make(),
        ]);
}
```

Components are discovered based on directory structure:

```
modules/User/Filament/
├── Admin/Resources/UserResource.php     # Registers to 'admin' panel
├── Manager/Resources/ProfileResource.php # Registers to 'manager' panel
└── Public/Pages/LoginPage.php          # Registers to 'public' panel
```

## Requirements

- PHP 8.2+
- Filament 4.0+

## Support

- 📖 [Documentation](https://github.com/panicdevs/modulite#readme)
- 🐛 [Issues](https://github.com/panicdevs/modulite/issues)

---

**Made with ❤️ by [PanicDevs](https://github.com/panicdevs)**
