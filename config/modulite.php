<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Modulite Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file allows you to customize how Modulite discovers
    | and registers Filament panels and components across your modular application.
    |
    | Modulite provides automatic discovery of:
    | - Panel Providers (for creating new Filament panels)
    | - Resources, Pages, and Widgets (for existing panels)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Panel Discovery
    |--------------------------------------------------------------------------
    |
    | Configure how Modulite discovers Filament Panel Provider classes.
    | Panel providers are classes that define new Filament panels and their
    | configuration. Discovery is based on file location and naming patterns.
    |
    */
    'panels' => [
        /*
        |--------------------------------------------------------------------------
        | Discovery Locations
        |--------------------------------------------------------------------------
        |
        | Define where to look for Panel Provider classes.
        | Supports glob patterns and wildcards for flexible discovery.
        |
        */
        'locations' => [
            'modules/*/Providers/Filament/Panels',
            'foundation/*/Providers/Filament/Panels',
        ],

        /*
        |--------------------------------------------------------------------------
        | Naming Patterns
        |--------------------------------------------------------------------------
        |
        | File and class naming patterns to identify panel providers.
        | Files must match at least one pattern to be considered for discovery.
        |
        */
        'patterns' => [
            'files'   => ['*PanelProvider.php', '*Panel.php'],
            'classes' => ['*PanelProvider', '*Panel'],
        ],

        /*
        |--------------------------------------------------------------------------
        | Validation Rules
        |--------------------------------------------------------------------------
        |
        | Rules to validate discovered panel provider classes.
        | Classes must meet these criteria to be registered.
        |
        */
        'validation' => [
            'strict_inheritance'        => env('MODULITE_STRICT_INHERITANCE', false),
            'must_extend'               => 'Filament\PanelProvider',
            'must_be_instantiable'      => true,
            'check_panel_method'        => true,
            'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_BASE_CLASSES', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Registration Options
        |--------------------------------------------------------------------------
        |
        | Configure how discovered panels are registered with Filament.
        |
        */
        'registration' => [
            'auto_register'            => env('MODULITE_AUTO_REGISTER_PANELS', true),
            'sort_by'                  => 'priority', // 'priority', 'name', 'none'
            'respect_environment'      => true,
            'validate_before_register' => app()->hasDebugModeEnabled(),
        ],

        /*
        |--------------------------------------------------------------------------
        | Scanning Options
        |--------------------------------------------------------------------------
        |
        | Performance and behavior options for panel discovery scanning.
        |
        */
        'scanning' => [
            'max_depth'            => 5,
            'follow_symlinks'      => false,
            'extensions'           => ['php'],
            'excluded_directories' => [
                'tests',
                'migrations',
                'seeders',
                'factories',
                '.git',
                'node_modules',
                'vendor',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Discovery
    |--------------------------------------------------------------------------
    |
    | Configure how Modulite discovers Filament components (Resources, Pages,
    | Widgets) within existing panels. Components are automatically registered
    | to their respective panels based on directory structure and naming.
    |
    */
    'components' => [
        /*
        |--------------------------------------------------------------------------
        | Discovery Locations
        |--------------------------------------------------------------------------
        |
        | Define where to look for Filament components. Placeholders available:
        | - {panel}: Panel ID (e.g., 'admin', 'manager')
        | - {module}: Module name from directory structure
        |
        | Directory structure examples:
        | - modules/User/Filament/Admin/Resources/UserResource.php
        | - modules/Blog/Filament/Manager/Pages/DashboardPage.php
        |
        */
        'locations' => [
            'modules/*/Filament/{panel}/Resources',
            'modules/*/Filament/{panel}/Pages',
            'modules/*/Filament/{panel}/Widgets',
            'foundation/*/Filament/{panel}/Resources',
            'foundation/*/Filament/{panel}/Pages',
            'foundation/*/Filament/{panel}/Widgets',
        ],

        /*
        |--------------------------------------------------------------------------
        | Component Types
        |--------------------------------------------------------------------------
        |
        | Configure which component types to discover and their settings.
        |
        */
        'types' => [
            'resources' => [
                'enabled'                   => true,
                'strict_inheritance'        => env('MODULITE_STRICT_COMPONENT_INHERITANCE', false),
                'must_extend'               => 'Filament\Resources\Resource',
                'naming_pattern'            => '*Resource.php',
                'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_RESOURCE_CLASSES', true),
            ],
            'pages' => [
                'enabled'                   => true,
                'strict_inheritance'        => env('MODULITE_STRICT_COMPONENT_INHERITANCE', false),
                'must_extend'               => 'Filament\Pages\Page',
                'naming_pattern'            => '*Page.php',
                'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_PAGE_CLASSES', true),
            ],
            'widgets' => [
                'enabled'                   => true,
                'strict_inheritance'        => env('MODULITE_STRICT_COMPONENT_INHERITANCE', false),
                'must_extend'               => 'Filament\Widgets\Widget',
                'naming_pattern'            => '*Widget.php',
                'allow_custom_base_classes' => env('MODULITE_ALLOW_CUSTOM_WIDGET_CLASSES', true),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Registration Options
        |--------------------------------------------------------------------------
        |
        | Configure how discovered components are registered with panels.
        |
        */
        'registration' => [
            'auto_register'            => env('MODULITE_AUTO_REGISTER_COMPONENTS', true),
            'sort_by'                  => 'name', // 'name', 'priority', 'none'
            'validate_before_register' => app()->hasDebugModeEnabled(),
            'group_by_module'          => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Scanning Options
        |--------------------------------------------------------------------------
        |
        | Performance and behavior options for component discovery scanning.
        |
        */
        'scanning' => [
            'max_depth'            => 3,
            'follow_symlinks'      => false,
            'extensions'           => ['php'],
            'excluded_directories' => [
                'tests',
                'migrations',
                'seeders',
                'factories',
                '.git',
                'node_modules',
                'vendor',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching strategies for optimal performance. Modulite uses
    | a simple file-based cache similar to Laravel's bootstrap cache system.
    | This provides fast loading while being easy to manage and clear.
    |
    */
    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Enable/Disable
        |--------------------------------------------------------------------------
        |
        | Master switch for caching. When disabled, scanning happens on every
        | request. Recommended: true for production, false for development.
        |
        */
        'enabled' => env('MODULITE_CACHE_ENABLED', !app()->hasDebugModeEnabled()),

        /*
        |--------------------------------------------------------------------------
        | Cache File Path
        |--------------------------------------------------------------------------
        |
        | Path to the main cache file. This file stores all discovered panels
        | and components in a simple PHP array format for fast loading.
        | Similar to Laravel's bootstrap cache system.
        |
        */
        'file' => base_path('bootstrap/cache/modulite.php'),

        /*
        |--------------------------------------------------------------------------
        | Cache TTL (Time To Live)
        |--------------------------------------------------------------------------
        |
        | How long to cache discovered data in seconds. In development,
        | shorter TTL allows for quicker iteration and testing.
        | Set to 0 in production for maximum performance (never expires).
        |
        */
        'ttl' => env('MODULITE_CACHE_TTL', app()->hasDebugModeEnabled() ? 300 : 0),

        /*
        |--------------------------------------------------------------------------
        | Auto-invalidation
        |--------------------------------------------------------------------------
        |
        | Automatically invalidate cache when module files are modified.
        | Only works in development mode for performance reasons.
        |
        */
        'auto_invalidate' => app()->hasDebugModeEnabled(),

        /*
        |--------------------------------------------------------------------------
        | Memory Cache
        |--------------------------------------------------------------------------
        |
        | In-memory caching for the current request to avoid repeated file reads.
        |
        */
        'memory_cache' => [
            'enabled'   => true,
            'max_items' => 1000,
        ],
    ],

    /*
        |--------------------------------------------------------------------------
        | Performance Configuration
        |--------------------------------------------------------------------------
        |
        | Configure performance optimizations and scanning behavior.
        |
        | For maximum production performance:
        | - Set cache.ttl to 0 (never expires)
        | - Enable lazy_discovery
        | - Disable auto_invalidate in production
        | - Use "php artisan optimize" to ensure cache is built
        |
        */
    'performance' => [
        /*
        |--------------------------------------------------------------------------
        | Lazy Discovery
        |--------------------------------------------------------------------------
        |
        | Defer scanning until Filament is actually being resolved.
        | This improves application boot time by avoiding unnecessary scans.
        |
        */
        'lazy_discovery' => env('MODULITE_LAZY_DISCOVERY', true),

        /*
        |--------------------------------------------------------------------------
        | Memory Optimization
        |--------------------------------------------------------------------------
        |
        | Options to reduce memory usage during large codebase scanning.
        |
        */
        'memory_optimization' => [
            'batch_size'       => 100,
            'clear_stat_cache' => true,
            'gc_after_scan'    => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Concurrent Processing
        |--------------------------------------------------------------------------
        |
        | Enable concurrent processing for faster scanning of large codebases.
        | Requires appropriate PHP extensions and environment support.
        |
        */
        'concurrent' => [
            'enabled'     => false,
            'max_workers' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Integration
    |--------------------------------------------------------------------------
    |
    | Configure integration with nwidart/laravel-modules package.
    |
    */
    'modules' => [
        /*
        |--------------------------------------------------------------------------
        | Module System Integration
        |--------------------------------------------------------------------------
        |
        | Configure how Modulite integrates with your module system.
        |
        */
        'namespace'               => 'modules',
        'scan_only_enabled'       => true,
        'respect_module_priority' => true,

        /*
        |--------------------------------------------------------------------------
        | Module Status Caching
        |--------------------------------------------------------------------------
        |
        | Cache module status information to avoid repeated filesystem checks.
        |
        */
        'status_cache_ttl' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Debugging
    |--------------------------------------------------------------------------
    |
    | Configure logging and debugging features for development and monitoring.
    |
    */
    'logging' => [
        /*
        |--------------------------------------------------------------------------
        | Logging Control
        |--------------------------------------------------------------------------
        |
        | Enable/disable logging and configure log channels.
        |
        */
        'enabled' => env('MODULITE_LOGGING_ENABLED', app()->hasDebugModeEnabled()),
        'channel' => env('MODULITE_LOG_CHANNEL', 'stack'),
        'level'   => env('MODULITE_LOG_LEVEL', 'info'),

        /*
        |--------------------------------------------------------------------------
        | Performance Logging
        |--------------------------------------------------------------------------
        |
        | Log performance metrics for optimization and monitoring.
        |
        */
        'log_discovery_time' => app()->hasDebugModeEnabled(),
        'log_cache_hits'     => app()->hasDebugModeEnabled(),
        'log_scan_stats'     => app()->hasDebugModeEnabled(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how errors and exceptions are handled during discovery.
    |
    */
    'error_handling' => [
        /*
        |--------------------------------------------------------------------------
        | Error Behavior
        |--------------------------------------------------------------------------
        |
        | Control whether errors are thrown or handled silently.
        |
        */
        'fail_silently'       => !app()->hasDebugModeEnabled(),
        'log_errors'          => true,
        'max_errors_per_scan' => 10,

        /*
        |--------------------------------------------------------------------------
        | Validation Errors
        |--------------------------------------------------------------------------
        |
        | Configure handling of validation errors during discovery.
        |
        */
        'throw_on_invalid_class'        => app()->hasDebugModeEnabled(),
        'throw_on_missing_requirements' => app()->hasDebugModeEnabled(),
    ],
];
