<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\SalesOverviewWidget;
use App\Http\Controllers\Admin\OrderInvoiceController;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(isSimple: false)
            // Optional 2-step login: each admin turns it on in their profile (Google Authenticator / Authy)
            ->multiFactorAuthentication([\Filament\Auth\MultiFactor\App\AppAuthentication::make()->recoverable()])
            ->brandName('Eternity Gadget')
            ->favicon(asset('brand-icons/favicon-32.png'))
            ->colors([
                'primary' => Color::hex('#F1592A'),
            ])
            // Red warning on every admin page while the admin password is a known weak one
            ->renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, fn (): string => \App\Support\WeakPasswordWarning::render())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Most-used sections first
            ->navigationGroups(['Sales', 'Catalog', 'Customers', 'Homepage', 'Navigation', 'Content', 'Settings', 'System'])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                SalesOverviewWidget::class,
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('orders/{order}/invoice.pdf', [OrderInvoiceController::class, 'download'])
                    ->name('orders.invoice');
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
