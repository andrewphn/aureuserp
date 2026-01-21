<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkul\Support\PluginManager;

/**
 * Customer Panel Provider service provider
 *
 * @see \Filament\Resources\Resource
 */
class CustomerPanelProvider extends PanelProvider
{
    /**
     * Panel
     *
     * @param Panel $panel
     * @return Panel
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('/')
            ->homeUrl(url('/'))
            ->login()
            ->authPasswordBroker('customers')
            ->passwordReset()
            ->registration()
            ->profile(isSimple: false)
            ->favicon(asset('images/logo-light.svg'))
            ->brandLogo(asset('images/logo.svg'))
            ->darkMode(false)
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->topNavigation()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => <<<'HTML'
                    <!-- Dark/Light Mode Favicons -->
                    <link rel="icon" type="image/svg+xml" href="/images/logo-light.svg" media="(prefers-color-scheme: light)">
                    <link rel="icon" type="image/svg+xml" href="/images/logo-dark.svg" media="(prefers-color-scheme: dark)">
                    <link rel="icon" type="image/png" href="/tcs_logo.png">
                    <!-- Apple Touch Icons (use PNG for better compatibility) -->
                    <link rel="apple-touch-icon" sizes="180x180" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="152x152" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="144x144" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="120x120" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="114x114" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="76x76" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="72x72" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="60x60" href="/tcs_logo.png">
                    <link rel="apple-touch-icon" sizes="57x57" href="/tcs_logo.png">
                    <meta name="apple-mobile-web-app-title" content="TCS ERP">
                HTML
            )
            ->plugins([
                PluginManager::make(),
            ])
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
            ->authGuard('customer');
    }
}
