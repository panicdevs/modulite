# 📋 Configuration Guide

This comprehensive guide covers all configuration options available in Modulite, with practical examples and use cases for each setting.

## 📂 Table of Contents

- [Configuration File Overview](#configuration-file-overview)
- [Panel Discovery Configuration](#panel-discovery-configuration)
- [Component Discovery Configuration](#component-discovery-configuration)
- [Cache Configuration](#cache-configuration)
- [Performance Configuration](#performance-configuration)
- [Module Integration](#module-integration)
- [Logging & Debugging](#logging--debugging)
- [Error Handling](#error-handling)
- [Environment Variables](#environment-variables)
- [Production Optimizations](#production-optimizations)

## Configuration File Overview

The main configuration file is located at `config/modulite.php`. You can publish it using:

```bash
php artisan vendor:publish --tag=modulite-config
```

The configuration is organized into logical sections:

```php
return [
    'panels' => [/* Panel discovery settings */],
    'components' => [/* Component discovery settings */],
    'cache' => [/* Caching configuration */],
    'performance' => [/* Performance optimizations */],
    'modules' => [/* Module system integration */],
    'logging' => [/* Logging configuration */],
    'error_handling' => [/* Error handling behavior */],
];
```

## Panel Discovery Configuration

### Discovery Locations

Configure where Modulite looks for Panel Provider classes:

```php
'panels' => [
    'locations' => [
        'modules/*/Providers/Filament/Panels',    // nwidart/laravel-modules structure
        'foundation/*/Providers/Filament/Panels', // Custom foundation modules
        'app/Filament/Panels',                    // App-level panels
        'packages/*/src/Filament/Panels',         // Custom packages
    ],
]
```

**Supported Patterns:**
- `*` - Single directory wildcard
- `**` - Recursive directory wildcard (use with caution)

**Examples:**

```php
// For different module structures
'locations' => [
    // Standard nwidart structure
    'modules/*/Providers/Filament/Panels',
    
    // Nested module structure
    'src/Modules/*/Filament/Panels',
    
    // Domain-driven design structure
    'app/Domains/*/Infrastructure/Filament/Panels',
    
    // Package-based structure
    'packages/*/src/Providers/Filament',
],
```

### Naming Patterns

Define file and class naming conventions:

```php
'panels' => [
    'patterns' => [
        'files' => [
            '*PanelProvider.php',  // Standard naming
            '*Panel.php',          // Shorter naming
            'Panel*.php',          // Prefix naming
        ],
        'classes' => [
            '*PanelProvider',      // Must match file patterns
            '*Panel',
            'Panel*',
        ],
    ],
]
```

**Best Practices:**
- Keep file and class patterns consistent
- Use descriptive naming conventions
- Avoid overly broad patterns that might match unintended files

### Validation Rules

Control how discovered classes are validated:

```php
'panels' => [
    'validation' => [
        // Require exact inheritance from Filament classes
        'strict_inheritance' => env('MODULITE_STRICT_INHERITANCE', false),
        
        // Base class that panels must extend
        'must_extend' => 'Filament\PanelProvider',
        
        // Ensure classes can be instantiated
        'must_be_instantiable' => true,
        
        // Verify panel() method exists
        'check_panel_method' => true,
        
        // Allow custom base classes (duck typing)
        'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_BASE_CLASSES', true),
    ],
]
```

**Validation Levels:**

**Strict Mode (`strict_inheritance: true`):**
```php
// Only classes that directly extend Filament\PanelProvider
class AdminPanelProvider extends \Filament\PanelProvider { }
```

**Flexible Mode (`strict_inheritance: false`):**
```php
// Custom base classes are allowed
class AdminPanelProvider extends MyBasePanelProvider { }
class MyBasePanelProvider extends \Filament\PanelProvider { }
```

### Registration Options

Configure how discovered panels are registered:

```php
'panels' => [
    'registration' => [
        // Automatically register discovered panels
        'auto_register' => env('MODULITE_AUTO_REGISTER_PANELS', true),
        
        // Sort order: 'priority', 'name', 'none'
        'sort_by' => 'priority',
        
        // Check environment constraints from #[FilamentPanel] attributes
        'respect_environment' => true,
        
        // Validate classes before registration (debug mode only)
        'validate_before_register' => app()->hasDebugModeEnabled(),
    ],
]
```

**Sort Options:**
- `priority` - Sort by `#[FilamentPanel(priority: X)]` attribute
- `name` - Sort alphabetically by class name
- `none` - Registration order is not guaranteed

### Scanning Options

Optimize the file scanning process:

```php
'panels' => [
    'scanning' => [
        // Maximum directory depth to scan
        'max_depth' => 5,
        
        // Follow symbolic links
        'follow_symlinks' => false,
        
        // File extensions to scan
        'extensions' => ['php'],
        
        // Directories to exclude from scanning
        'excluded_directories' => [
            'tests',
            'migrations',
            'seeders',
            'factories',
            '.git',
            'node_modules',
            'vendor',
            'storage',
            'bootstrap/cache',
        ],
    ],
]
```

**Performance Impact:**
- Lower `max_depth` = faster scanning, might miss nested files
- Higher `max_depth` = thorough scanning, slower performance
- More `excluded_directories` = faster scanning

## Component Discovery Configuration

### Discovery Locations

Configure where to find Filament components:

```php
'components' => [
    'locations' => [
        // Panel-specific components
        'modules/*/Filament/{panel}/Resources',
        'modules/*/Filament/{panel}/Pages',
        'modules/*/Filament/{panel}/Widgets',
        
        // Foundation modules
        'foundation/*/Filament/{panel}/Resources',
        'foundation/*/Filament/{panel}/Pages',
        'foundation/*/Filament/{panel}/Widgets',
        
        // App-level components
        'app/Filament/{panel}/Resources',
        'app/Filament/{panel}/Pages',
        'app/Filament/{panel}/Widgets',
    ],
]
```

**Placeholder Variables:**
- `{panel}` - Replaced with actual panel ID (e.g., 'admin', 'manager')
- `{module}` - Replaced with module name

**Examples:**

```php
// Directory structure for multi-panel setup
modules/
├── User/
│   └── Filament/
│       ├── Admin/
│       │   ├── Resources/UserResource.php
│       │   ├── Pages/UsersPage.php
│       │   └── Widgets/UserStatsWidget.php
│       └── Manager/
│           ├── Resources/UserResource.php
│           └── Pages/UserManagementPage.php
└── Blog/
    └── Filament/
        └── Admin/
            ├── Resources/PostResource.php
            └── Widgets/PostsWidget.php
```

### Component Types

Configure individual component type discovery:

```php
'components' => [
    'types' => [
        'resources' => [
            'enabled' => true,
            'strict_inheritance' => env('MODULITE_STRICT_COMPONENT_INHERITANCE', false),
            'must_extend' => 'Filament\Resources\Resource',
            'naming_pattern' => '*Resource.php',
            'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_RESOURCE_CLASSES', true),
        ],
        'pages' => [
            'enabled' => true,
            'strict_inheritance' => env('MODULITE_STRICT_COMPONENT_INHERITANCE', false),
            'must_extend' => 'Filament\Pages\Page',
            'naming_pattern' => '*Page.php',
            'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_PAGE_CLASSES', true),
        ],
        'widgets' => [
            'enabled' => true,
            'strict_inheritance' => env('MODULITE_STRICT_COMPONENT_INHERITANCE', false),
            'must_extend' => 'Filament\Widgets\Widget',
            'naming_pattern' => '*Widget.php',
            'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_WIDGET_CLASSES', true),
        ],
    ],
]
```

**Custom Component Types:**

You can add custom component types:

```php
'types' => [
    'actions' => [
        'enabled' => true,
        'must_extend' => 'Filament\Actions\Action',
        'naming_pattern' => '*Action.php',
    ],
    'livewire' => [
        'enabled' => true,
        'must_extend' => 'Livewire\Component',
        'naming_pattern' => '*Component.php',
    ],
]
```

### Component Registration

Configure how components are registered to panels:

```php
'components' => [
    'registration' => [
        // Automatically register discovered components
        'auto_register' => env('MODULITE_AUTO_REGISTER_COMPONENTS', true),
        
        // Sort components: 'name', 'priority', 'none'
        'sort_by' => 'name',
        
        // Validate components before registration (debug mode)
        'validate_before_register' => app()->hasDebugModeEnabled(),
        
        // Group components by module in navigation
        'group_by_module' => true,
    ],
]
```

## Cache Configuration

### Cache Enable/Disable

Master switch for caching functionality:

```php
'cache' => [
    'enabled' => env('MODULITE_CACHE_ENABLED', !app()->hasDebugModeEnabled()),
]
```

**Environment-Based Defaults:**
- Development: `false` (disabled for quick iteration)
- Production: `true` (enabled for performance)

### Cache File Configuration

```php
'cache' => [
    'file' => base_path('bootstrap/cache/modulite.php'),
]
```

**Alternative Locations:**

```php
// Different cache locations for different environments
'file' => match(app()->environment()) {
    'testing' => storage_path('framework/cache/modulite.php'),
    'local' => base_path('bootstrap/cache/modulite.php'),
    default => base_path('bootstrap/cache/modulite.php'),
},
```

### Cache TTL (Time To Live)

```php
'cache' => [
    'ttl' => env('MODULITE_CACHE_TTL', app()->hasDebugModeEnabled() ? 300 : 0),
]
```

**TTL Settings:**
- `0` - Never expires (production)
- `300` - 5 minutes (development)
- `3600` - 1 hour (staging)
- `86400` - 24 hours (long-term caching)

### Auto-Invalidation

```php
'cache' => [
    'auto_invalidate' => app()->hasDebugModeEnabled(),
]
```

**When enabled:**
- File modification time changes trigger cache invalidation
- New files in scan directories clear cache
- Module enable/disable events clear cache

### Memory Cache

In-memory caching for current request:

```php
'cache' => [
    'memory_cache' => [
        'enabled' => true,
        'max_items' => 1000,
    ],
]
```

## Performance Configuration

### Lazy Discovery

Defer scanning until Filament is actually needed:

```php
'performance' => [
    'lazy_discovery' => env('MODULITE_LAZY_DISCOVERY', true),
]
```

**Benefits:**
- Faster application boot time
- Reduced memory usage on non-admin requests
- Better performance for API-only endpoints

### Memory Optimization

For large codebases with many modules:

```php
'performance' => [
    'memory_optimization' => [
        // Process files in batches to reduce memory usage
        'batch_size' => 100,
        
        // Clear file stat cache regularly
        'clear_stat_cache' => true,
        
        // Force garbage collection after scanning
        'gc_after_scan' => true,
    ],
]
```

**Tuning Guidelines:**
- Large projects (1000+ files): `batch_size: 50`
- Medium projects (100-1000 files): `batch_size: 100`
- Small projects (<100 files): `batch_size: 200`

### Concurrent Processing

**⚠️ Experimental Feature**

Enable concurrent file processing:

```php
'performance' => [
    'concurrent' => [
        'enabled' => false,
        'max_workers' => 4,
    ],
]
```

**Requirements:**
- PHP with `pcntl` extension
- Unix-like operating system
- Sufficient system resources

## Module Integration

### nwidart/laravel-modules Integration

```php
'modules' => [
    'namespace' => 'modules',
    'scan_only_enabled' => true,
    'respect_module_priority' => true,
    'status_cache_ttl' => 300,
]
```

**Configuration Options:**

```php
'modules' => [
    // Module directory namespace
    'namespace' => 'modules',
    
    // Only scan enabled modules
    'scan_only_enabled' => true,
    
    // Respect module loading priority
    'respect_module_priority' => true,
    
    // Cache module status for performance
    'status_cache_ttl' => 300, // 5 minutes
]
```

### Custom Module Systems

For custom module systems:

```php
'modules' => [
    'custom_module_resolver' => function() {
        // Return array of enabled module names
        return ['User', 'Blog', 'Shop'];
    },
]
```

## Logging & Debugging

### Logging Control

```php
'logging' => [
    'enabled' => env('MODULITE_LOGGING_ENABLED', app()->hasDebugModeEnabled()),
    'channel' => env('MODULITE_LOG_CHANNEL', 'stack'),
    'level' => env('MODULITE_LOG_LEVEL', 'info'),
]
```

**Log Levels:**
- `debug` - Verbose logging, file-by-file discovery details
- `info` - Standard logging, summary information
- `warning` - Important issues that don't break functionality
- `error` - Serious issues that may affect functionality

### Performance Logging

```php
'logging' => [
    'log_discovery_time' => app()->hasDebugModeEnabled(),
    'log_cache_hits' => app()->hasDebugModeEnabled(),
    'log_scan_stats' => app()->hasDebugModeEnabled(),
]
```

**Sample Log Output:**

```
[2024-01-15 10:30:45] local.INFO: Modulite panel discovery started
[2024-01-15 10:30:45] local.DEBUG: Scanning location: /app/modules/User/Providers/Filament/Panels
[2024-01-15 10:30:45] local.DEBUG: Found panels in /app/modules/User/Providers/Filament/Panels/AdminPanelProvider.php ["AdminPanelProvider"]
[2024-01-15 10:30:45] local.INFO: Modulite panel discovery completed {"panels_found":3,"files_scanned":25,"scan_time":0.156}
```

## Error Handling

### Error Behavior

```php
'error_handling' => [
    'fail_silently' => !app()->hasDebugModeEnabled(),
    'log_errors' => true,
    'max_errors_per_scan' => 10,
]
```

**Fail Silently Modes:**
- `true` (Production): Log errors but continue execution
- `false` (Development): Throw exceptions for debugging

### Validation Errors

```php
'error_handling' => [
    'throw_on_invalid_class' => app()->hasDebugModeEnabled(),
    'throw_on_missing_requirements' => app()->hasDebugModeEnabled(),
]
```

**Error Types:**
- Invalid class structure
- Missing required methods
- File parsing errors
- Reflection exceptions

## Environment Variables

### Complete Environment Variable Reference

```env
# Cache Configuration
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0

# Performance
MODULITE_LAZY_DISCOVERY=true

# Panel Discovery
MODULITE_AUTO_REGISTER_PANELS=true
MODULITE_STRICT_INHERITANCE=false
MODULITE_ALLOW_CUSTOM_BASE_CLASSES=true

# Component Discovery
MODULITE_AUTO_REGISTER_COMPONENTS=true
MODULITE_STRICT_COMPONENT_INHERITANCE=false
MODULITE_ALLOW_CUSTOM_RESOURCE_CLASSES=true
MODULITE_ALLOW_CUSTOM_PAGE_CLASSES=true
MODULITE_ALLOW_CUSTOM_WIDGET_CLASSES=true

# Error Handling
MODULITE_FAIL_SILENTLY=false

# Logging
MODULITE_LOGGING_ENABLED=false
MODULITE_LOG_CHANNEL=stack
MODULITE_LOG_LEVEL=info
```

### Environment-Specific Configurations

**.env.local (Development):**
```env
MODULITE_CACHE_ENABLED=false
MODULITE_CACHE_TTL=300
MODULITE_LOGGING_ENABLED=true
MODULITE_FAIL_SILENTLY=false
```

**.env.production (Production):**
```env
MODULITE_CACHE_ENABLED=true
MODULITE_CACHE_TTL=0
MODULITE_LOGGING_ENABLED=false
MODULITE_FAIL_SILENTLY=true
MODULITE_LAZY_DISCOVERY=true
```

**.env.testing (Testing):**
```env
MODULITE_CACHE_ENABLED=false
MODULITE_LOGGING_ENABLED=false
MODULITE_FAIL_SILENTLY=false
```

## Production Optimizations

### Optimal Production Configuration

```php
// config/modulite.php - Production optimizations
return [
    'cache' => [
        'enabled' => true,
        'ttl' => 0, // Never expires
        'auto_invalidate' => false,
    ],
    'performance' => [
        'lazy_discovery' => true,
        'memory_optimization' => [
            'batch_size' => 100,
            'clear_stat_cache' => true,
            'gc_after_scan' => true,
        ],
    ],
    'error_handling' => [
        'fail_silently' => true,
        'log_errors' => false, // Disable in production for performance
    ],
    'logging' => [
        'enabled' => false, // Disable for maximum performance
    ],
];
```

### Deployment Checklist

1. **Set environment variables:**
   ```bash
   APP_DEBUG=false
   MODULITE_CACHE_ENABLED=true
   MODULITE_CACHE_TTL=0
   ```

2. **Run optimization commands:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

3. **Verify cache is working:**
   ```bash
   php artisan modulite:status
   php artisan modulite:benchmark --iterations=100
   ```

4. **Set proper file permissions:**
   ```bash
   chmod 755 bootstrap/cache
   chown -R www-data:www-data bootstrap/cache
   ```

### Monitoring & Maintenance

**Cache monitoring:**
```bash
# Check cache status
php artisan modulite:status

# Clear cache if needed
php artisan modulite:clear-cache

# Rebuild cache
php artisan optimize
```

**Performance monitoring:**
```bash
# Benchmark performance
php artisan modulite:benchmark --iterations=1000

# Monitor memory usage
php artisan modulite:status --vvv
```

---

This configuration guide covers all aspects of Modulite configuration. For specific use cases or advanced configurations, refer to the examples in each section or check the [troubleshooting guide](TROUBLESHOOTING.md).
