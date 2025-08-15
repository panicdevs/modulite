# 🚀 Installation & Setup Guide

Complete guide to installing and configuring Modulite in your Laravel application.

## 📋 Table of Contents

- [Requirements](#requirements)
- [Quick Installation](#quick-installation)
- [Step-by-Step Installation](#step-by-step-installation)
- [Configuration Setup](#configuration-setup)
- [Directory Structure](#directory-structure)
- [First Panel Creation](#first-panel-creation)
- [Environment Configuration](#environment-configuration)
- [Framework Integration](#framework-integration)
- [Verification & Testing](#verification--testing)
- [Troubleshooting Installation](#troubleshooting-installation)

## Requirements

### System Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 10.0 or higher
- **Filament**: 4.0 or higher

### Recommended Packages

- **nwidart/laravel-modules**: 11.0+ (for modular applications)
- **Laravel Sanctum**: For API authentication (if using API panels)
- **Laravel Telescope**: For development debugging (optional)

### PHP Extensions

Required PHP extensions:
- `mbstring`
- `openssl`
- `PDO`
- `Tokenizer`
- `XML`
- `Ctype`
- `JSON`
- `BCMath`

### Server Requirements

**Development:**
- Memory: 512MB minimum
- Disk: 100MB free space
- PHP execution time: 60 seconds

**Production:**
- Memory: 1GB recommended
- Disk: 500MB free space
- OPcache enabled
- Fast storage (SSD recommended)

## Quick Installation

For experienced developers who want to get started quickly:

```bash
# 1. Install Modulite
composer require panicdevs/modulite

# 2. Publish configuration (optional)
php artisan vendor:publish --tag=modulite-config

# 3. Create your first panel
php artisan make:filament-panel admin

# 4. Add the attribute to your panel provider
# Add #[FilamentPanel] to your AdminPanelProvider class

# 5. Verify installation
php artisan modulite:status
```

## Step-by-Step Installation

### Step 1: Install via Composer

Add Modulite to your Laravel project:

```bash
composer require panicdevs/modulite
```

**For development/testing environments:**
```bash
composer require panicdevs/modulite --dev
```

### Step 2: Verify Package Registration

Modulite uses Laravel's auto-discovery feature. Verify it's registered:

```bash
php artisan package:discover
```

You should see Modulite in the discovered packages list.

### Step 3: Publish Configuration (Optional)

Publish the configuration file to customize Modulite's behavior:

```bash
php artisan vendor:publish --tag=modulite-config
```

This creates `config/modulite.php` with default settings.

**Available publish tags:**
- `modulite-config` - Configuration file only
- `modulite` - All publishable assets

### Step 4: Install Filament (if not already installed)

If you don't have Filament installed:

```bash
composer require filament/filament
php artisan filament:install --panels
```

### Step 5: Install nwidart/laravel-modules (Recommended)

For modular applications:

```bash
composer require nwidart/laravel-modules
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

Configure modules in `config/modules.php`:

```php
'paths' => [
    'modules' => base_path('modules'),
    'assets' => public_path('modules'),
    'migration' => base_path('database/migrations'),
    'generator' => [
        'config' => ['path' => 'Config', 'generate' => true],
        'command' => ['path' => 'Console', 'generate' => true],
        'migration' => ['path' => 'Database/Migrations', 'generate' => true],
        'seeder' => ['path' => 'Database/Seeders', 'generate' => true],
        'factory' => ['path' => 'Database/Factories', 'generate' => true],
        'model' => ['path' => 'Entities', 'generate' => true],
        'controller' => ['path' => 'Http/Controllers', 'generate' => true],
        'filter' => ['path' => 'Http/Middleware', 'generate' => true],
        'request' => ['path' => 'Http/Requests', 'generate' => true],
        'provider' => ['path' => 'Providers', 'generate' => true],
        'assets' => ['path' => 'Resources/assets', 'generate' => true],
        'lang' => ['path' => 'Resources/lang', 'generate' => true],
        'views' => ['path' => 'Resources/views', 'generate' => true],
        'test' => ['path' => 'Tests', 'generate' => true],
        'repository' => ['path' => 'Repositories', 'generate' => false],
        'event' => ['path' => 'Events', 'generate' => false],
        'listener' => ['path' => 'Listeners', 'generate' => false],
        'policies' => ['path' => 'Policies', 'generate' => false],
        'rules' => ['path' => 'Rules', 'generate' => false],
        'jobs' => ['path' => 'Jobs', 'generate' => false],
        'emails' => ['path' => 'Emails', 'generate' => false],
        'notifications' => ['path' => 'Notifications', 'generate' => false],
        'resource' => ['path' => 'Transformers', 'generate' => false],
    ],
],
```

## Configuration Setup

### Basic Configuration

Edit `config/modulite.php` to match your application structure:

```php
<?php

return [
    'panels' => [
        'locations' => [
            'modules/*/Providers/Filament/Panels',  // For nwidart modules
            'app/Filament/Panels',                  // For app-level panels
        ],
        'patterns' => [
            'files' => ['*PanelProvider.php'],
            'classes' => ['*PanelProvider'],
        ],
    ],
    
    'cache' => [
        'enabled' => env('MODULITE_CACHE_ENABLED', !app()->hasDebugModeEnabled()),
        'ttl' => env('MODULITE_CACHE_TTL', 3600),
    ],
    
    'performance' => [
        'lazy_discovery' => env('MODULITE_LAZY_DISCOVERY', true),
    ],
];
```

### Environment Configuration

Add environment variables to your `.env` file:

```env
# Modulite Configuration
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=3600
MODULITE_LAZY_DISCOVERY=true
MODULITE_LOGGING_ENABLED=false
```

### Advanced Configuration

For complex applications, you might need custom configurations:

```php
// config/modulite.php
return [
    'panels' => [
        'locations' => [
            'modules/*/Providers/Filament/Panels',
            'app/Domains/*/Infrastructure/Filament/Panels',  // DDD structure
            'packages/*/src/Filament/Panels',                // Custom packages
        ],
        'validation' => [
            'strict_inheritance' => false,
            'allow_custom_base_classes' => true,
        ],
        'scanning' => [
            'max_depth' => 5,
            'excluded_directories' => [
                'tests', 'vendor', 'node_modules', '.git',
                'storage', 'bootstrap/cache', 'public',
            ],
        ],
    ],
    
    'components' => [
        'locations' => [
            'modules/*/Filament/{panel}/Resources',
            'modules/*/Filament/{panel}/Pages',
            'modules/*/Filament/{panel}/Widgets',
        ],
        'types' => [
            'resources' => ['enabled' => true],
            'pages' => ['enabled' => true],
            'widgets' => ['enabled' => true],
        ],
    ],
    
    'performance' => [
        'memory_optimization' => [
            'batch_size' => 100,
            'gc_after_scan' => true,
        ],
    ],
];
```

## Directory Structure

### Standard nwidart Structure

Create the following directory structure for your modules:

```
modules/
├── Admin/
│   ├── Providers/
│   │   └── Filament/
│   │       └── Panels/
│   │           └── AdminPanelProvider.php
│   └── Filament/
│       └── Admin/
│           ├── Resources/
│           ├── Pages/
│           └── Widgets/
├── User/
│   ├── Providers/
│   │   └── Filament/
│   │       └── Panels/
│   │           └── UserPanelProvider.php
│   └── Filament/
│       └── User/
│           ├── Resources/
│           │   └── UserResource.php
│           ├── Pages/
│           └── Widgets/
└── Blog/
    ├── Providers/
    │   └── Filament/
    │       └── Panels/
    │           └── BlogPanelProvider.php
    └── Filament/
        └── Blog/
            ├── Resources/
            │   └── PostResource.php
            ├── Pages/
            └── Widgets/
```

### Alternative Structures

**Domain-Driven Design:**
```
app/
└── Domains/
    ├── User/
    │   └── Infrastructure/
    │       └── Filament/
    │           └── Panels/
    │               └── UserPanelProvider.php
    └── Blog/
        └── Infrastructure/
            └── Filament/
                └── Panels/
                    └── BlogPanelProvider.php
```

**Foundation Modules:**
```
foundation/
├── Core/
│   └── Providers/
│       └── Filament/
│           └── Panels/
│               └── CorePanelProvider.php
└── Auth/
    └── Providers/
        └── Filament/
            └── Panels/
                └── AuthPanelProvider.php
```

### Create Required Directories

```bash
# For nwidart modules structure
mkdir -p modules/{Admin,User,Blog}/Providers/Filament/Panels
mkdir -p modules/{Admin,User,Blog}/Filament/{Admin,User,Blog}/{Resources,Pages,Widgets}

# For app-level panels
mkdir -p app/Filament/Panels

# Ensure proper permissions
chmod -R 755 modules/
chmod -R 755 app/Filament/
```

## First Panel Creation

### Step 1: Create a Module (if using nwidart)

```bash
php artisan module:make Admin
```

### Step 2: Create Panel Provider

```bash
# Using Filament command
php artisan make:filament-panel admin

# Or create manually
mkdir -p modules/Admin/Providers/Filament/Panels
```

### Step 3: Implement Panel Provider

Create `modules/Admin/Providers/Filament/Panels/AdminPanelProvider.php`:

```php
<?php

namespace Modules\Admin\Providers\Filament\Panels;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
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
            ->discoverResources(
                in: module_path('Admin', 'Filament/Admin/Resources'),
                for: 'Modules\\Admin\\Filament\\Admin\\Resources'
            )
            ->discoverPages(
                in: module_path('Admin', 'Filament/Admin/Pages'),
                for: 'Modules\\Admin\\Filament\\Admin\\Pages'
            )
            ->pages([
                'dashboard' => \Modules\Admin\Filament\Admin\Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                'web',
                'auth',
            ])
            ->authMiddleware([
                'auth',
            ]);
    }
}
```

### Step 4: Create Dashboard Page

Create `modules/Admin/Filament/Admin/Pages/Dashboard.php`:

```php
<?php

namespace Modules\Admin\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Admin Dashboard';
}
```

### Step 5: Register Module (if using nwidart)

Enable the module:

```bash
php artisan module:enable Admin
```

Or manually in `modules_statuses.json`:

```json
{
    "Admin": true
}
```

## Environment Configuration

### Development Environment

**.env.local:**
```env
APP_ENV=local
APP_DEBUG=true

# Modulite Development Settings
MODULITE_CACHE_ENABLED=false
MODULITE_CACHE_TTL=300
MODULITE_LAZY_DISCOVERY=true
MODULITE_LOGGING_ENABLED=true
MODULITE_LOG_LEVEL=debug
MODULITE_FAIL_SILENTLY=false
```

### Staging Environment

**.env.staging:**
```env
APP_ENV=staging
APP_DEBUG=false

# Modulite Staging Settings
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=1800
MODULITE_LAZY_DISCOVERY=true
MODULITE_LOGGING_ENABLED=true
MODULITE_LOG_LEVEL=info
MODULITE_FAIL_SILENTLY=true
```

### Production Environment

**.env.production:**
```env
APP_ENV=production
APP_DEBUG=false

# Modulite Production Settings
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0
MODULITE_LAZY_DISCOVERY=true
MODULITE_LOGGING_ENABLED=false
MODULITE_FAIL_SILENTLY=true
```

## Framework Integration

### Laravel Octane

For Laravel Octane compatibility:

```php
// config/octane.php
'warm' => [
    'modulite.cache',
],

'flush' => [
    'modulite.cache',
],
```

### Laravel Horizon

If using Laravel Horizon, ensure queue workers are restarted after Modulite updates:

```php
// app/Providers/HorizonServiceProvider.php
use PanicDevs\Modulite\Events\ModuliteUpdated;

protected function gate()
{
    Gate::define('viewHorizon', function ($user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
    
    // Restart workers when Modulite cache changes
    Event::listen(ModuliteUpdated::class, function () {
        Artisan::call('horizon:terminate');
    });
}
```

### Laravel Telescope

Configure Telescope to monitor Modulite operations:

```php
// config/telescope.php
'watchers' => [
    TelescopeWatchers\CacheWatcher::class => [
        'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
        'hidden' => [
            'modulite:*',  // Hide Modulite cache operations if too verbose
        ],
    ],
],
```



## Verification & Testing

### Step 1: Check Modulite Status

```bash
php artisan modulite:status
```

Expected output:
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
Discovered 1 panels:
  • AdminPanelProvider
```

### Step 2: Test Panel Access

Visit your panel in the browser:

```
http://your-app.test/admin
```

You should see the Filament login page.

### Step 3: Run Benchmark

Test performance:

```bash
php artisan modulite:benchmark --iterations=50
```

### Step 4: Verify Cache Operations

```bash
# Clear cache
php artisan modulite:clear-cache

# Check cache file
ls -la bootstrap/cache/modulite.php

# Force discovery
php artisan modulite:discover-panels --verbose
```

### Step 5: Test in Different Environments

```bash
# Test with cache disabled
APP_ENV=local MODULITE_CACHE_ENABLED=false php artisan modulite:status

# Test with cache enabled
APP_ENV=production MODULITE_CACHE_ENABLED=true php artisan modulite:status
```

## Troubleshooting Installation

### Common Installation Issues

#### 1. Composer Install Fails

**Error:** "Could not find package panicdevs/modulite"

**Solution:**
```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Try installing again
composer require panicdevs/modulite
```

#### 2. File Permissions

**Error:** "Permission denied" when accessing cache

**Solution:**
```bash
# Fix permissions
sudo chown -R $USER:www-data bootstrap/cache
chmod -R 775 bootstrap/cache

# For production
sudo chown -R www-data:www-data bootstrap/cache
chmod -R 755 bootstrap/cache
```

#### 3. Namespace Issues

**Error:** "Class not found" for panel providers

**Solution:**
```bash
# Regenerate autoload
composer dump-autoload

# Check PSR-4 autoloading in composer.json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "modules/"
        }
    }
}
```

#### 4. Configuration Cache Issues

**Error:** Configuration not loading correctly

**Solution:**
```bash
# Clear configuration cache
php artisan config:clear

# Republish configuration
php artisan vendor:publish --tag=modulite-config --force

# Recache configuration
php artisan config:cache
```

### Validation Commands

Run these commands to validate your installation:

```bash
# 1. Check PHP requirements
php -v
php -m | grep -E "(mbstring|openssl|pdo|tokenizer|xml)"

# 2. Check Laravel version
php artisan --version

# 3. Check Filament installation
php artisan list | grep filament

# 4. Check nwidart modules (if using)
php artisan module:list

# 5. Verify Modulite registration
php artisan package:discover | grep modulite

# 6. Test Modulite functionality
php artisan modulite:status --vvv
```

### Performance Validation

```bash
# Test discovery performance
time php artisan modulite:discover-panels

# Benchmark with cache
php artisan modulite:benchmark --warm-cache --iterations=100

# Check memory usage
php -d memory_limit=256M artisan modulite:status
```

### Getting Help

If you encounter issues:

1. **Check the logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Enable debug mode:**
   ```env
   APP_DEBUG=true
   MODULITE_LOGGING_ENABLED=true
   MODULITE_LOG_LEVEL=debug
   ```

3. **Run diagnostics:**
   ```bash
   php artisan modulite:status --vvv
   php artisan about
   ```

4. **Create a GitHub issue** with:
   - Laravel version
   - PHP version
   - Modulite configuration
   - Error messages
   - Steps to reproduce

---

Congratulations! You've successfully installed and configured Modulite. Next, explore the [Configuration Guide](CONFIGURATION.md) for advanced setup options or check the [Examples](EXAMPLES.md) for real-world usage patterns.
