# 🔧 Troubleshooting Guide

This comprehensive troubleshooting guide will help you resolve common issues with Modulite and optimize its performance.

## 📋 Table of Contents

- [Quick Diagnostic Commands](#quick-diagnostic-commands)
- [Common Issues](#common-issues)
- [Cache Issues](#cache-issues)
- [Panel Discovery Issues](#panel-discovery-issues)
- [Component Discovery Issues](#component-discovery-issues)
- [Performance Issues](#performance-issues)
- [Memory Issues](#memory-issues)
- [Environment-Specific Issues](#environment-specific-issues)
- [Advanced Debugging](#advanced-debugging)
- [FAQ](#frequently-asked-questions)

## Quick Diagnostic Commands

Before diving into specific issues, run these commands to get an overview:

```bash
# Check Modulite status
php artisan modulite:status --vvv

# Clear and rebuild cache
php artisan modulite:clear-cache
php artisan optimize

# Test performance
php artisan modulite:benchmark

# Check for panels manually
php artisan modulite:discover-panels --verbose

# Check Laravel optimization
php artisan optimize:clear
php artisan config:cache
```

## Common Issues

### ❌ Panels Not Being Discovered

**Symptoms:**
- Filament panels don't appear
- Getting "Panel not found" errors
- `php artisan modulite:status` shows 0 panels

**Diagnostic Steps:**

1. **Check file location and naming:**
   ```bash
   # Verify your panel files are in the configured locations
   find modules/ -name "*PanelProvider.php" -type f
   find foundation/ -name "*PanelProvider.php" -type f
   ```

2. **Verify the attribute is present:**
   ```php
   // ✅ Correct
   #[FilamentPanel]
   class AdminPanelProvider extends PanelProvider
   
   // ❌ Missing attribute
   class AdminPanelProvider extends PanelProvider
   ```

3. **Check namespace and class name:**
   ```php
   // File: modules/User/Providers/Filament/Panels/AdminPanelProvider.php
   namespace Modules\User\Providers\Filament\Panels; // ✅ Correct namespace
   
   class AdminPanelProvider extends PanelProvider // ✅ Correct class name
   ```

4. **Verify configuration paths:**
   ```bash
   php artisan config:show modulite.panels.locations
   ```

**Solutions:**

1. **Fix file structure:**
   ```bash
   # Expected structure
   modules/
   └── YourModule/
       └── Providers/
           └── Filament/
               └── Panels/
                   └── YourPanelProvider.php
   ```

2. **Add missing attribute:**
   ```php
   use PanicDevs\Modulite\Attributes\FilamentPanel;
   
   #[FilamentPanel]
   class YourPanelProvider extends PanelProvider
   ```

3. **Update configuration if using custom paths:**
   ```php
   // config/modulite.php
   'panels' => [
       'locations' => [
           'your/custom/path/*/Panels', // Add your custom path
       ],
   ],
   ```

### ❌ Filament Panel Provider Not Registered

**Symptoms:**
- Panel discovered but not working
- Routes not registered
- Panel configuration not applied

**Diagnostic Steps:**

1. **Check if panel class is valid:**
   ```bash
   php artisan tinker
   >>> class_exists('Modules\Admin\Providers\Filament\Panels\AdminPanelProvider')
   >>> (new ReflectionClass('Modules\Admin\Providers\Filament\Panels\AdminPanelProvider'))->isInstantiable()
   ```

2. **Verify panel method exists:**
   ```php
   public function panel(Panel $panel): Panel
   {
       return $panel->id('admin'); // Must return configured panel
   }
   ```

**Solutions:**

1. **Ensure proper inheritance:**
   ```php
   use Filament\PanelProvider;
   
   class AdminPanelProvider extends PanelProvider // ✅
   ```

2. **Implement required methods:**
   ```php
   public function panel(Panel $panel): Panel
   {
       return $panel
           ->id('admin')
           ->path('/admin');
   }
   ```

### ❌ Multiple Panel Registration

**Symptoms:**
- Same panel registered multiple times
- Conflicting panel configurations
- Routes collision errors

**Solutions:**

1. **Check for duplicate files:**
   ```bash
   find . -name "*AdminPanel*" -type f
   ```

2. **Ensure unique panel IDs:**
   ```php
   // Each panel must have unique ID
   return $panel->id('admin');    // ✅
   return $panel->id('manager');  // ✅
   return $panel->id('admin');    // ❌ Duplicate
   ```

3. **Use priority to control registration order:**
   ```php
   #[FilamentPanel(priority: 100)]
   class MainPanelProvider extends PanelProvider
   
   #[FilamentPanel(priority: 50)]
   class SecondaryPanelProvider extends PanelProvider
   ```

## Cache Issues

### ❌ Cache Not Working

**Symptoms:**
- Slow panel loading
- Changes not reflected immediately
- `modulite:status` shows cache disabled

**Diagnostic Steps:**

1. **Check cache configuration:**
   ```bash
   php artisan config:show modulite.cache
   ```

2. **Verify cache file permissions:**
   ```bash
   ls -la bootstrap/cache/modulite.php
   ```

3. **Check cache directory writability:**
   ```bash
   touch bootstrap/cache/test.txt && rm bootstrap/cache/test.txt
   ```

**Solutions:**

1. **Enable cache:**
   ```env
   MODULITE_CACHE_ENABLED=true
   ```

2. **Fix permissions:**
   ```bash
   chmod 755 bootstrap/cache
   chown -R www-data:www-data bootstrap/cache  # For production
   ```

3. **Recreate cache directory:**
   ```bash
   mkdir -p bootstrap/cache
   chmod 755 bootstrap/cache
   ```

### ❌ Stale Cache Issues

**Symptoms:**
- Changes to panels not reflected
- Old panel configurations still active
- New panels not discovered

**Solutions:**

1. **Clear Modulite cache:**
   ```bash
   php artisan modulite:clear-cache
   ```

2. **Clear all Laravel caches:**
   ```bash
   php artisan optimize:clear
   ```

3. **Enable auto-invalidation in development:**
   ```env
   # .env.local
   MODULITE_CACHE_ENABLED=false
   # or
   MODULITE_CACHE_TTL=300  # 5 minutes
   ```

### ❌ Cache File Corruption

**Symptoms:**
- Syntax errors in cache file
- PHP parse errors
- Cache loading failures

**Solutions:**

1. **Delete corrupted cache file:**
   ```bash
   rm bootstrap/cache/modulite.php
   ```

2. **Rebuild cache:**
   ```bash
   php artisan optimize
   php artisan modulite:status
   ```

3. **Check for file system issues:**
   ```bash
   # Check disk space
   df -h
   
   # Check for file system errors
   dmesg | grep -i error
   ```

## Panel Discovery Issues

### ❌ Scanning Timeout

**Symptoms:**
- Discovery process takes too long
- Memory exhaustion during scanning
- Timeout errors

**Solutions:**

1. **Reduce scan depth:**
   ```php
   'scanning' => [
       'max_depth' => 3, // Reduce from default 5
   ]
   ```

2. **Add more excluded directories:**
   ```php
   'excluded_directories' => [
       'tests',
       'vendor',
       'node_modules',
       'storage',
       'public',
       'database/migrations',
       '.git',
       '.idea',
       '.vscode',
   ]
   ```

3. **Enable memory optimization:**
   ```php
   'performance' => [
       'memory_optimization' => [
           'batch_size' => 50,
           'gc_after_scan' => true,
       ],
   ]
   ```

### ❌ Permission Denied Errors

**Symptoms:**
- Cannot read module directories
- File access denied errors
- Scanner fails silently

**Solutions:**

1. **Fix directory permissions:**
   ```bash
   find modules/ -type d -exec chmod 755 {} \;
   find modules/ -type f -exec chmod 644 {} \;
   ```

2. **Check SELinux (if applicable):**
   ```bash
   setsebool -P httpd_can_network_connect 1
   ```

3. **Verify ownership:**
   ```bash
   chown -R www-data:www-data modules/
   ```

## Component Discovery Issues

### ❌ Components Not Found

**Symptoms:**
- Resources/Pages/Widgets not auto-registered
- Empty component discovery results

**Diagnostic Steps:**

1. **Check component file structure:**
   ```bash
   find modules/ -path "*/Filament/*/Resources/*.php" -type f
   find modules/ -path "*/Filament/*/Pages/*.php" -type f
   find modules/ -path "*/Filament/*/Widgets/*.php" -type f
   ```

2. **Verify naming patterns:**
   ```bash
   # Should match *Resource.php, *Page.php, *Widget.php
   ls modules/*/Filament/*/Resources/
   ```

**Solutions:**

1. **Fix directory structure:**
   ```
   modules/
   └── User/
       └── Filament/
           └── Admin/              # Panel name
               ├── Resources/
               │   └── UserResource.php
               ├── Pages/
               │   └── DashboardPage.php
               └── Widgets/
                   └── StatsWidget.php
   ```

2. **Ensure proper inheritance:**
   ```php
   use Filament\Resources\Resource;
   
   class UserResource extends Resource // ✅
   ```

3. **Enable component types:**
   ```php
   'components' => [
       'types' => [
           'resources' => ['enabled' => true],
           'pages' => ['enabled' => true],
           'widgets' => ['enabled' => true],
       ],
   ]
   ```

### ❌ Components Not Registered to Panel

**Symptoms:**
- Components discovered but not appearing in panel
- Panel navigation empty

**Solutions:**

1. **Verify panel configuration:**
   ```php
   public function panel(Panel $panel): Panel
   {
       return $panel
           ->discoverResources(/* ... */)  // Enable resource discovery
           ->discoverPages(/* ... */)      // Enable page discovery
           ->discoverWidgets(/* ... */);   // Enable widget discovery
   }
   ```

2. **Check component registration:**
   ```php
   // config/modulite.php
   'components' => [
       'registration' => [
           'auto_register' => true, // Enable auto-registration
       ],
   ]
   ```

## Performance Issues

### ❌ Slow Application Boot

**Symptoms:**
- Application takes long to start
- High response times on first request
- Memory usage spikes

**Solutions:**

1. **Enable lazy discovery:**
   ```env
   MODULITE_LAZY_DISCOVERY=true
   ```

2. **Optimize cache settings:**
   ```env
   MODULITE_CACHE_ENABLED=true
   MODULITE_CACHE_TTL=0
   ```

3. **Use Laravel optimizations:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### ❌ High Memory Usage

**Symptoms:**
- Memory limit exceeded errors
- Slow garbage collection
- System becomes unresponsive

**Solutions:**

1. **Enable memory optimization:**
   ```php
   'performance' => [
       'memory_optimization' => [
           'batch_size' => 25,          // Smaller batches
           'clear_stat_cache' => true,
           'gc_after_scan' => true,
       ],
   ]
   ```

2. **Increase PHP memory limit temporarily:**
   ```bash
   php -d memory_limit=512M artisan modulite:status
   ```

3. **Optimize scanning scope:**
   ```php
   'scanning' => [
       'max_depth' => 2,
       'excluded_directories' => [
           'tests', 'vendor', 'node_modules', 'storage'
       ],
   ]
   ```

### ❌ Slow Cache Operations

**Symptoms:**
- Cache writes are slow
- File I/O bottlenecks
- High disk usage

**Solutions:**

1. **Use faster storage:**
   ```php
   // Move cache to RAM disk (Linux)
   'cache' => [
       'file' => '/tmp/modulite.php',
   ]
   ```

2. **Optimize cache content:**
   ```php
   'cache' => [
       'memory_cache' => [
           'enabled' => true,
           'max_items' => 500, // Reduce if memory constrained
       ],
   ]
   ```

## Memory Issues

### ❌ Memory Limit Exceeded

**Error Message:**
```
PHP Fatal error: Allowed memory size exhausted
```

**Solutions:**

1. **Increase PHP memory limit:**
   ```ini
   ; php.ini
   memory_limit = 512M
   ```

2. **Optimize batch processing:**
   ```php
   'performance' => [
       'memory_optimization' => [
           'batch_size' => 10,  // Very small batches
           'gc_after_scan' => true,
       ],
   ]
   ```

3. **Reduce scanning scope:**
   ```php
   'panels' => [
       'scanning' => [
           'max_depth' => 1,  // Only scan immediate subdirectories
       ],
   ]
   ```

### ❌ Memory Leaks

**Symptoms:**
- Memory usage grows over time
- Long-running processes consume increasing memory
- Eventual out-of-memory errors

**Solutions:**

1. **Enable garbage collection:**
   ```php
   'performance' => [
       'memory_optimization' => [
           'gc_after_scan' => true,
           'clear_stat_cache' => true,
       ],
   ]
   ```

2. **Use production-optimized settings:**
   ```php
   'cache' => [
       'memory_cache' => [
           'max_items' => 100,  // Limit in-memory cache
       ],
   ]
   ```

## Environment-Specific Issues

### 🐳 Docker/Sail Issues

**Common Problems:**

1. **File permission issues:**
   ```bash
   # In docker-compose.yml, ensure proper user mapping
   user: "${WWWUSER:-1000}:${WWWGROUP:-1000}"
   ```

2. **Volume mount performance:**
   ```yaml
   # Use delegated mounts for better performance on macOS
   volumes:
     - '.:/var/www/html:delegated'
   ```

3. **Cache directory permissions:**
   ```bash
   docker exec -it laravel.test chmod -R 755 bootstrap/cache
   ```

### ☁️ Production Server Issues

**Common Problems:**

1. **File ownership:**
   ```bash
   chown -R www-data:www-data /var/www/html
   chmod -R 755 /var/www/html/bootstrap/cache
   ```

2. **SELinux context:**
   ```bash
   setsebool -P httpd_exec_mem 1
   setsebool -P httpd_can_network_connect 1
   ```

3. **PHP configuration:**
   ```ini
   ; Production php.ini optimizations
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

### 🧪 Testing Environment Issues

**Common Problems:**

1. **Test isolation:**
   ```php
   // In test setup
   protected function setUp(): void
   {
       parent::setUp();
       $this->app['config']->set('modulite.cache.enabled', false);
   }
   ```

2. **Test database:**
   ```php
   'cache' => [
       'file' => storage_path('framework/testing/modulite.php'),
   ]
   ```

## Advanced Debugging

### Enable Debug Logging

1. **Full debug configuration:**
   ```env
   MODULITE_LOGGING_ENABLED=true
   MODULITE_LOG_LEVEL=debug
   MODULITE_LOG_CHANNEL=stack
   ```

2. **Custom log channel:**
   ```php
   // config/logging.php
   'channels' => [
       'modulite' => [
           'driver' => 'daily',
           'path' => storage_path('logs/modulite.log'),
           'level' => 'debug',
       ],
   ],
   ```

3. **Check logs:**
   ```bash
   tail -f storage/logs/modulite.log
   ```

### Profiling Performance

1. **Use Xdebug profiler:**
   ```ini
   xdebug.mode=profile
   xdebug.output_dir=/tmp/xdebug
   ```

2. **Benchmark specific operations:**
   ```bash
   php artisan modulite:benchmark --iterations=10 --show-details
   ```

3. **Memory profiling:**
   ```php
   // Add to panel discovery code for debugging
   echo "Memory usage: " . memory_get_peak_usage(true) / 1024 / 1024 . "MB\n";
   ```

### Using Laravel Telescope

1. **Install and configure Telescope:**
   ```bash
   composer require laravel/telescope
   php artisan telescope:install
   ```

2. **Monitor Modulite operations:**
   - Cache operations
   - Service provider registration
   - File system operations

## Frequently Asked Questions

### Q: Why are my panels not being discovered?

**A:** Check these common issues:
1. Missing `#[FilamentPanel]` attribute
2. File not in configured scan locations
3. Class naming doesn't match patterns
4. Cache is stale - clear with `php artisan modulite:clear-cache`

### Q: How can I improve discovery performance?

**A:** Try these optimizations:
1. Enable caching: `MODULITE_CACHE_ENABLED=true`
2. Use lazy discovery: `MODULITE_LAZY_DISCOVERY=true`
3. Reduce scan depth and exclude unnecessary directories
4. Use `php artisan optimize` for Laravel optimizations

### Q: Can I use custom base classes for panels?

**A:** Yes, set these configurations:
```php
'validation' => [
    'strict_inheritance' => false,
    'allow_custom_base_classes' => true,
]
```

### Q: How do I debug discovery issues?

**A:** Use these debugging tools:
1. `php artisan modulite:status --vvv`
2. Enable debug logging: `MODULITE_LOGGING_ENABLED=true`
3. Check specific locations: `php artisan modulite:discover-panels --verbose`

### Q: Is Modulite compatible with Laravel Octane?

**A:** Yes, but consider these points:
1. Enable static caching for better performance
2. Use memory optimization settings
3. Test thoroughly as worker processes persist

### Q: How do I handle multi-tenant applications?

**A:** For multi-tenancy:
1. Use environment-based panel registration
2. Implement custom conditions in `#[FilamentPanel]`
3. Consider tenant-specific cache keys

### Q: Can I exclude specific modules from discovery?

**A:** Yes, configure module exclusions:
```php
'modules' => [
    'excluded_modules' => ['TestModule', 'DevModule'],
]
```

### Q: What's the impact on application performance?

**A:** With proper configuration:
- **Development**: Minimal impact with caching disabled
- **Production**: Near-zero impact with persistent caching
- **Benchmark**: Use `php artisan modulite:benchmark` to measure

### Q: How do I migrate from manual panel registration?

**A:** Follow these steps:
1. Add `#[FilamentPanel]` attributes to existing providers
2. Remove manual registration from service providers
3. Test thoroughly with `php artisan modulite:status`
4. Clear caches and verify functionality

---

For additional help, please:
1. Check the [Configuration Guide](CONFIGURATION.md)
2. Review the [API Reference](API_REFERENCE.md)
3. Open an issue on [GitHub](https://github.com/panicdevs/modulite/issues)
