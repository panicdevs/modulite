# 🎨 Usage Examples & Best Practices

This guide provides comprehensive examples of using Modulite in real-world scenarios, following Laravel and Filament best practices.

## 📋 Table of Contents

- [Basic Panel Setup](#basic-panel-setup)
- [Multi-Panel Applications](#multi-panel-applications)
- [Component Organization](#component-organization)
- [Advanced Configurations](#advanced-configurations)
- [Enterprise Patterns](#enterprise-patterns)
- [Performance Optimization](#performance-optimization)
- [Testing Strategies](#testing-strategies)


## Basic Panel Setup

### Simple Admin Panel

The most basic setup for an admin panel:

```php
<?php
// modules/Admin/Providers/Filament/Panels/AdminPanelProvider.php

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
                'gray' => '#6b7280',
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
                \Modules\Admin\Http\Middleware\AdminMiddleware::class,
            ])
            ->authMiddleware([
                'auth',
                \Modules\Admin\Http\Middleware\AdminAuthMiddleware::class,
            ]);
    }
}
```

### User Management Panel

A specialized panel for user management:

```php
<?php
// modules/User/Providers/Filament/Panels/UserPanelProvider.php

namespace Modules\User\Providers\Filament\Panels;

use Filament\Panel;
use Filament\PanelProvider;
use PanicDevs\Modulite\Attributes\FilamentPanel;

#[FilamentPanel(priority: 20)]
class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('users')
            ->path('/users')
            ->login()
            ->registration()
            ->passwordReset()
            ->colors([
                'primary' => '#3b82f6',
            ])
            ->font('Inter')
            ->favicon(asset('images/favicon.ico'))
            ->brandName('User Management')
            ->brandLogo(asset('images/user-logo.svg'))
            ->viteTheme('resources/css/filament/user/theme.css')
            ->discoverResources(
                in: module_path('User', 'Filament/User/Resources'),
                for: 'Modules\\User\\Filament\\User\\Resources'
            )
            ->resources([
                \Modules\User\Filament\User\Resources\UserResource::class,
                \Modules\User\Filament\User\Resources\RoleResource::class,
            ])
            ->pages([
                \Modules\User\Filament\User\Pages\Profile::class,
                \Modules\User\Filament\User\Pages\Settings::class,
            ])
            ->navigationGroups([
                'User Management',
                'Security',
                'Reports',
            ]);
    }
}
```

## Multi-Panel Applications

### E-commerce Platform Example

A complete e-commerce platform with multiple specialized panels:

#### 1. Admin Panel (Core Management)

```php
<?php
// modules/Core/Providers/Filament/Panels/AdminPanelProvider.php

#[FilamentPanel(priority: 100, environment: null)]
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login()
            ->colors(['primary' => '#dc2626'])
            ->brandName('E-Shop Admin')
            ->discoverResources(
                in: module_path('Core', 'Filament/Admin/Resources'),
                for: 'Modules\\Core\\Filament\\Admin\\Resources'
            )
            ->navigationGroups([
                'Dashboard',
                'User Management',
                'Content Management',
                'System',
                'Reports',
            ])
            ->middleware([
                'web',
                'auth',
                \Modules\Core\Http\Middleware\AdminMiddleware::class,
            ]);
    }
}
```

#### 2. Shop Management Panel

```php
<?php
// modules/Shop/Providers/Filament/Panels/ShopPanelProvider.php

#[FilamentPanel(priority: 90)]
class ShopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('shop')
            ->path('/shop/manage')
            ->login()
            ->colors(['primary' => '#059669'])
            ->brandName('Shop Manager')
            ->discoverResources(
                in: module_path('Shop', 'Filament/Shop/Resources'),
                for: 'Modules\\Shop\\Filament\\Shop\\Resources'
            )
            ->resources([
                \Modules\Shop\Filament\Shop\Resources\ProductResource::class,
                \Modules\Shop\Filament\Shop\Resources\CategoryResource::class,
                \Modules\Shop\Filament\Shop\Resources\OrderResource::class,
                \Modules\Shop\Filament\Shop\Resources\InventoryResource::class,
            ])
            ->pages([
                \Modules\Shop\Filament\Shop\Pages\Dashboard::class,
                \Modules\Shop\Filament\Shop\Pages\Analytics::class,
            ])
            ->widgets([
                \Modules\Shop\Filament\Shop\Widgets\SalesOverview::class,
                \Modules\Shop\Filament\Shop\Widgets\TopProducts::class,
                \Modules\Shop\Filament\Shop\Widgets\RecentOrders::class,
            ])
            ->navigationGroups([
                'Catalog',
                'Orders',
                'Inventory',
                'Analytics',
                'Settings',
            ]);
    }
}
```

#### 3. Customer Service Panel

```php
<?php
// modules/Support/Providers/Filament/Panels/SupportPanelProvider.php

#[FilamentPanel(priority: 80)]
class SupportPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('support')
            ->path('/support')
            ->login()
            ->colors(['primary' => '#7c3aed'])
            ->brandName('Customer Support')
            ->discoverResources(
                in: module_path('Support', 'Filament/Support/Resources'),
                for: 'Modules\\Support\\Filament\\Support\\Resources'
            )
            ->resources([
                \Modules\Support\Filament\Support\Resources\TicketResource::class,
                \Modules\Support\Filament\Support\Resources\CustomerResource::class,
                \Modules\Support\Filament\Support\Resources\KnowledgeBaseResource::class,
            ])
            ->pages([
                \Modules\Support\Filament\Support\Pages\Dashboard::class,
                \Modules\Support\Filament\Support\Pages\LiveChat::class,
            ])
            ->widgets([
                \Modules\Support\Filament\Support\Widgets\TicketStats::class,
                \Modules\Support\Filament\Support\Widgets\ResponseTime::class,
            ])
            ->middleware([
                'web',
                'auth',
                \Modules\Support\Http\Middleware\SupportAgentMiddleware::class,
            ]);
    }
}
```

### Environment-Specific Panels

#### Development Panel (Local Only)

```php
<?php
// modules/Debug/Providers/Filament/Panels/DebugPanelProvider.php

#[FilamentPanel(
    priority: 10,
    environment: 'local',
    autoRegister: true
)]
class DebugPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('debug')
            ->path('/debug')
            ->colors(['primary' => '#f59e0b'])
            ->brandName('Debug Panel')
            ->resources([
                \Modules\Debug\Filament\Debug\Resources\LogResource::class,
                \Modules\Debug\Filament\Debug\Resources\QueueJobResource::class,
                \Modules\Debug\Filament\Debug\Resources\CacheResource::class,
            ])
            ->pages([
                \Modules\Debug\Filament\Debug\Pages\SystemInfo::class,
                \Modules\Debug\Filament\Debug\Pages\DatabaseQueries::class,
                \Modules\Debug\Filament\Debug\Pages\ModuliteStatus::class,
            ])
            ->middleware(['web'])
            ->authGuard(null); // No auth required in development
    }
}
```

#### Staging Panel (Testing Environment)

```php
<?php
// modules/Testing/Providers/Filament/Panels/StagingPanelProvider.php

#[FilamentPanel(
    priority: 5,
    environment: 'staging',
    conditions: ['feature.staging_panel']
)]
class StagingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('staging')
            ->path('/staging')
            ->login()
            ->colors(['primary' => '#f97316'])
            ->brandName('Staging Environment')
            ->brandLogo(asset('images/staging-logo.svg'))
            ->resources([
                \Modules\Testing\Filament\Staging\Resources\TestDataResource::class,
                \Modules\Testing\Filament\Staging\Resources\TestResultResource::class,
            ])
            ->pages([
                \Modules\Testing\Filament\Staging\Pages\TestRunner::class,
                \Modules\Testing\Filament\Staging\Pages\DataSeeder::class,
            ])
            ->middleware([
                'web',
                'auth',
                \Modules\Testing\Http\Middleware\StagingMiddleware::class,
            ]);
    }
}
```

## Component Organization

### Modular Resource Structure

#### User Resource Example

```php
<?php
// modules/User/Filament/Admin/Resources/UserResource.php

namespace Modules\User\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\User\Entities\User;
use Modules\User\Filament\Admin\Resources\UserResource\Pages;
use Modules\User\Filament\Admin\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple(),
                Tables\Filters\Filter::make('verified')
                    ->query(fn ($query) => $query->whereNotNull('email_verified_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->icon('heroicon-o-check-circle'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->icon('heroicon-o-x-circle'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\PermissionsRelationManager::class,
            RelationManagers\ActivityRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['roles']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Roles' => $record->roles->pluck('name')->join(', '),
        ];
    }
}
```

### Custom Page Example

```php
<?php
// modules/Analytics/Filament/Admin/Pages/Dashboard.php

namespace Modules\Analytics\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Widgets\Widget;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'analytics::filament.admin.pages.dashboard';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Analytics Dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            \Modules\Analytics\Filament\Admin\Widgets\StatsOverview::class,
            \Modules\Analytics\Filament\Admin\Widgets\RevenueChart::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            \Modules\Analytics\Filament\Admin\Widgets\RecentActivity::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportData'),
            \Filament\Actions\Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshData'),
        ];
    }

    public function exportData(): void
    {
        // Export logic
        $this->notify('success', 'Data exported successfully!');
    }

    public function refreshData(): void
    {
        // Refresh logic
        $this->dispatch('refreshWidgets');
        $this->notify('success', 'Data refreshed!');
    }
}
```

### Widget Examples

#### Stats Overview Widget

```php
<?php
// modules/Analytics/Filament/Admin/Widgets/StatsOverview.php

namespace Modules\Analytics\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\User\Entities\User;
use Modules\Shop\Entities\Order;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('32% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Active Orders', Order::where('status', 'active')->count())
                ->description('12% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Revenue', '$' . number_format(Order::sum('total'), 2))
                ->description('7% decrease')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
```

#### Chart Widget

```php
<?php
// modules/Analytics/Filament/Admin/Widgets/RevenueChart.php

namespace Modules\Analytics\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Shop\Entities\Order;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Chart';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->pluck('revenue'),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => $data->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
```

## Advanced Configurations

### Custom Base Panel Provider

Create a base panel provider for consistent configuration:

```php
<?php
// foundation/Base/Providers/Filament/BaseModulePanelProvider.php

namespace Foundation\Base\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

abstract class BaseModulePanelProvider extends PanelProvider
{
    /**
     * Configure common panel settings
     */
    protected function configureBasePanel(Panel $panel, string $id, string $path): Panel
    {
        return $panel
            ->id($id)
            ->path($path)
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->brandName(config('app.name'))
            ->brandLogo(asset('images/logo.svg'))
            ->favicon(asset('images/favicon.ico'))
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups($this->getNavigationGroups())
            ->middleware($this->getBaseMiddleware())
            ->authMiddleware($this->getAuthMiddleware())
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->viteTheme($this->getViteTheme());
    }

    /**
     * Get navigation groups for this panel
     */
    protected function getNavigationGroups(): array
    {
        return [
            'Dashboard',
            'Management',
            'Reports',
            'Settings',
        ];
    }

    /**
     * Get base middleware stack
     */
    protected function getBaseMiddleware(): array
    {
        return [
            'web',
            'auth',
            \Foundation\Base\Http\Middleware\BaseMiddleware::class,
        ];
    }

    /**
     * Get authentication middleware
     */
    protected function getAuthMiddleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Get Vite theme path
     */
    protected function getViteTheme(): string
    {
        return 'resources/css/filament/theme.css';
    }
}
```

### Using Custom Base Provider

```php
<?php
// modules/Admin/Providers/Filament/Panels/AdminPanelProvider.php

use Foundation\Base\Providers\Filament\BaseModulePanelProvider;
use PanicDevs\Modulite\Attributes\FilamentPanel;

#[FilamentPanel(priority: 100)]
class AdminPanelProvider extends BaseModulePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->configureBasePanel($panel, 'admin', '/admin')
            ->default()
            ->discoverResources(
                in: module_path('Admin', 'Filament/Admin/Resources'),
                for: 'Modules\\Admin\\Filament\\Admin\\Resources'
            )
            ->pages([
                \Modules\Admin\Filament\Admin\Pages\Dashboard::class,
            ])
            ->widgets([
                \Modules\Admin\Filament\Admin\Widgets\AdminStats::class,
            ]);
    }

    protected function getNavigationGroups(): array
    {
        return [
            'Dashboard',
            'User Management',
            'Content',
            'System',
            'Reports',
        ];
    }

    protected function getAuthMiddleware(): array
    {
        return [
            'auth',
            \Modules\Admin\Http\Middleware\AdminMiddleware::class,
        ];
    }
}
```

### Conditional Panel Registration

```php
<?php
// modules/Enterprise/Providers/Filament/Panels/EnterprisePanelProvider.php

#[FilamentPanel(
    priority: 50,
    conditions: ['feature.enterprise_enabled', 'license.valid']
)]
class EnterprisePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Only register if enterprise features are enabled and license is valid
        if (!$this->shouldRegisterPanel()) {
            return $panel;
        }

        return $panel
            ->id('enterprise')
            ->path('/enterprise')
            ->login()
            ->colors(['primary' => '#6366f1'])
            ->brandName('Enterprise Dashboard')
            ->resources([
                \Modules\Enterprise\Filament\Enterprise\Resources\LicenseResource::class,
                \Modules\Enterprise\Filament\Enterprise\Resources\AdvancedAnalyticsResource::class,
            ]);
    }

    private function shouldRegisterPanel(): bool
    {
        return config('features.enterprise_enabled', false) && 
               app(\App\Services\LicenseService::class)->isValid();
    }
}
```

## Enterprise Patterns

### Multi-Tenant Panel Architecture

```php
<?php
// modules/Tenant/Providers/Filament/Panels/TenantPanelProvider.php

#[FilamentPanel(priority: 75)]
class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('/tenant')
            ->login()
            ->tenant(\App\Models\Team::class)
            ->tenantRegistration()
            ->tenantProfile()
            ->colors(['primary' => '#10b981'])
            ->discoverResources(
                in: module_path('Tenant', 'Filament/Tenant/Resources'),
                for: 'Modules\\Tenant\\Filament\\Tenant\\Resources'
            )
            ->middleware([
                'web',
                'auth',
                \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            ])
            ->tenantMiddleware([
                \Modules\Tenant\Http\Middleware\TenantMiddleware::class,
            ]);
    }
}
```

### API-Driven Panel

```php
<?php
// modules/Api/Providers/Filament/Panels/ApiManagerPanelProvider.php

#[FilamentPanel(priority: 60)]
class ApiManagerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('api')
            ->path('/api-manager')
            ->login()
            ->colors(['primary' => '#8b5cf6'])
            ->brandName('API Manager')
            ->resources([
                \Modules\Api\Filament\Api\Resources\ApiKeyResource::class,
                \Modules\Api\Filament\Api\Resources\EndpointResource::class,
                \Modules\Api\Filament\Api\Resources\RequestLogResource::class,
            ])
            ->pages([
                \Modules\Api\Filament\Api\Pages\ApiDocumentation::class,
                \Modules\Api\Filament\Api\Pages\RateLimiting::class,
            ])
            ->widgets([
                \Modules\Api\Filament\Api\Widgets\ApiUsageStats::class,
                \Modules\Api\Filament\Api\Widgets\ResponseTimeChart::class,
            ])
            ->middleware([
                'web',
                'auth',
                \Modules\Api\Http\Middleware\ApiManagerMiddleware::class,
            ]);
    }
}
```

## Performance Optimization

### Optimized Configuration for Large Applications

```php
<?php
// config/modulite.php - Production optimized

return [
    'cache' => [
        'enabled' => true,
        'ttl' => 0, // Never expires in production
        'file' => base_path('bootstrap/cache/modulite.php'),
        'memory_cache' => [
            'enabled' => true,
            'max_items' => 500,
        ],
    ],
    
    'performance' => [
        'lazy_discovery' => true,
        'memory_optimization' => [
            'batch_size' => 50,
            'clear_stat_cache' => true,
            'gc_after_scan' => true,
        ],
    ],
    
    'panels' => [
        'scanning' => [
            'max_depth' => 3,
            'excluded_directories' => [
                'tests', 'migrations', 'seeders', 'factories',
                '.git', 'node_modules', 'vendor', 'storage',
                'bootstrap/cache', 'public', 'database',
            ],
        ],
    ],
    
    'error_handling' => [
        'fail_silently' => true,
        'log_errors' => false,
    ],
    
    'logging' => [
        'enabled' => false,
    ],
];
```

### Custom Cache Strategy

```php
<?php
// app/Services/ModuliteCacheService.php

namespace App\Services;

use PanicDevs\Modulite\Contracts\CacheManagerInterface;
use Illuminate\Support\Facades\Redis;

class ModuliteCacheService implements CacheManagerInterface
{
    private string $prefix = 'modulite:';
    
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Redis::get($this->prefix . $key);
        return $value !== null ? unserialize($value) : $default;
    }
    
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $serialized = serialize($value);
        if ($ttl) {
            return Redis::setex($this->prefix . $key, $ttl, $serialized);
        }
        return Redis::set($this->prefix . $key, $serialized);
    }
    
    // Implement other interface methods...
}
```

Register custom cache service:

```php
<?php
// app/Providers/AppServiceProvider.php

use App\Services\ModuliteCacheService;
use PanicDevs\Modulite\Contracts\CacheManagerInterface;

public function register()
{
    if (app()->isProduction()) {
        $this->app->singleton(CacheManagerInterface::class, ModuliteCacheService::class);
    }
}
```

## Testing Strategies

### Unit Testing Panel Discovery

```php
<?php
// tests/Unit/ModulitePanelDiscoveryTest.php

namespace Tests\Unit;

use Tests\TestCase;
use PanicDevs\Modulite\Services\PanelScannerService;
use PanicDevs\Modulite\Attributes\FilamentPanel;

class ModulitePanelDiscoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable cache for testing
        config(['modulite.cache.enabled' => false]);
    }

    public function test_discovers_panels_with_attribute()
    {
        $scanner = app(PanelScannerService::class);
        $panels = $scanner->discoverPanels();
        
        $this->assertContains(
            'Modules\\Admin\\Providers\\Filament\\Panels\\AdminPanelProvider',
            $panels
        );
    }

    public function test_respects_environment_constraints()
    {
        app()->instance('env', 'production');
        
        $scanner = app(PanelScannerService::class);
        $panels = $scanner->discoverPanels();
        
        // Development-only panels should not be discovered
        $this->assertNotContains(
            'Modules\\Debug\\Providers\\Filament\\Panels\\DebugPanelProvider',
            $panels
        );
    }

    public function test_sorts_panels_by_priority()
    {
        $scanner = app(PanelScannerService::class);
        $panels = $scanner->discoverPanels();
        
        // Verify panels are sorted by priority
        $adminIndex = array_search('AdminPanelProvider', $panels);
        $userIndex = array_search('UserPanelProvider', $panels);
        
        $this->assertLessThan($userIndex, $adminIndex);
    }
}
```

### Feature Testing with Multiple Panels

```php
<?php
// tests/Feature/MultiPanelTest.php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\User\Entities\User;

class MultiPanelTest extends TestCase
{
    public function test_admin_panel_is_accessible()
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)->get('/admin');
        
        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }

    public function test_user_panel_requires_proper_permissions()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/users');
        
        $response->assertStatus(403);
    }

    public function test_debug_panel_only_available_in_development()
    {
        app()->instance('env', 'production');
        
        $response = $this->get('/debug');
        
        $response->assertStatus(404);
    }
}
```

---

This comprehensive examples guide demonstrates real-world usage patterns for Modulite, from basic setups to enterprise-grade applications. Each example follows Laravel and Filament best practices while leveraging Modulite's powerful auto-discovery features.
