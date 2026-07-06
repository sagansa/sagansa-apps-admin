<?php

namespace App\Providers\Filament;

use App\Models\Employee;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Pages\Auth\Register;
use App\Filament\Widgets\ProductStockWidget;
use App\Filament\Widgets\StockOverviewWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Akses')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Transaksi')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Kas')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Persediaan')
                    ->icon('heroicon-o-cube')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Toko')
                    ->icon('heroicon-o-building-storefront')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('SDM')
                    ->icon('heroicon-o-user-group')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Aset')
                    ->icon('heroicon-o-truck')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Utilitas')
                    ->icon('heroicon-o-bolt')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Sistem')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ])
            ->default()
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->subNavigationPosition(SubNavigationPosition::Top)
            ->id('admin')
            ->path('admin')
            ->login()
            ->PasswordReset()
            ->registration()
            ->emailVerification()
            ->maxContentWidth('full')
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profil Saya')
                    ->url(fn (): string => \App\Filament\Pages\MyProfile::getUrl())
                    ->icon('heroicon-o-user'),
            ])
            // ->profile()
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                ProductStockWidget::class,
                StockOverviewWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->databaseNotifications()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make('super_admin'),
            ]);
    }
}
