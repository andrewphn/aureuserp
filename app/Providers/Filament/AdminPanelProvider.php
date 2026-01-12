<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkul\Support\Filament\Pages\Profile;
use Webkul\Support\PluginManager;

/**
 * Admin Panel Provider service provider
 *
 * @see \Filament\Resources\Resource
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Panel
     *
     * @param Panel $panel
     * @return Panel
     */
    public function panel(Panel $panel): Panel
    {
        set_time_limit(300);

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode()
            ->login(\App\Filament\Auth\Login::class)
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo-light.svg'))
            ->darkModeBrandLogo(asset('images/logo-dark.svg'))
            ->brandLogoHeight('2rem')
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary' => Color::hex('#D4A574'), // TCS Gold - woodworking theme
                'gray' => Color::Neutral, // Changed from Slate to Neutral for true gray (no blue tint)
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            ->unsavedChangesAlerts()
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label(fn() => filament()->auth()->user()?->name)
                    ->url(fn(): string => Profile::getUrl()),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('admin.navigation.dashboard'))
                    ->icon('icon-dashboard'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.contact'))
                    ->icon('icon-contacts'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.sale'))
                    ->icon('icon-sales'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.purchase'))
                    ->icon('icon-purchases'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.invoice'))
                    ->icon('icon-invoices'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.inventory'))
                    ->icon('icon-inventories'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.project'))
                    ->icon('icon-projects'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.employee'))
                    ->icon('icon-employees'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.time-off'))
                    ->icon('icon-time-offs'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.recruitment'))
                    ->icon('icon-recruitments'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.website'))
                    ->icon('icon-website'),
                NavigationGroup::make()
                    ->label(__('admin.navigation.setting'))
                    ->icon('icon-settings'),
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm'      => 1,
                        'lg'      => 2,
                        'xl'      => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm'      => 1,
                        'lg'      => 2,
                        'xl'      => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm'      => 2,
                    ]),
                PluginManager::make(),
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),
            ])
            ->pages([
                \App\Filament\Pages\ManageFooter::class,
                \App\Filament\Pages\TestFooter::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => <<<'HTML'
                    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=3.0, user-scalable=yes">
                    <style>
                        /* Note: Removed html { zoom: 0.9 } as it breaks Floating UI dropdown positioning */
                        /* Floating UI calculates positions using getBoundingClientRect() (viewport coords) */
                        /* but zoom affects CSS positioning differently, causing ~10% offset errors */

                        /* Two-Column Layout Responsive Utilities */
                        @media (min-width: 1280px) {
                            .xl\:flex-row { flex-direction: row !important; }
                            .xl\:items-start { align-items: flex-start !important; }
                            .xl\:gap-6 { gap: 1.5rem !important; }
                            .xl\:w-80 { width: 20rem !important; }
                            .xl\:max-w-xs { max-width: 20rem !important; }
                            .xl\:flex-shrink-0 { flex-shrink: 0 !important; }
                            .xl\:sticky { position: sticky !important; }
                            .xl\:top-20 { top: 5rem !important; }
                        }
                        .flex-1 { flex: 1 1 0% !important; }
                        .min-w-0 { min-width: 0 !important; }
                        .gap-4 { gap: 1rem !important; }

                        /* TCS Discovery Phase Colors */
                        :root {
                            --tcs-discovery-900: #1e3a8a;
                            --tcs-discovery-700: #1d4ed8;
                            --tcs-discovery-500: #3b82f6;
                            --tcs-discovery-300: #93c5fd;
                            --tcs-discovery-100: #dbeafe;
                        }

                        /* Wizard Step 1: Discovery Blue 900 (Deepest) */
                        .fi-sc-wizard-header-step:nth-child(1).fi-active .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(1).fi-completed .fi-sc-wizard-header-step-icon-ctn {
                            background-color: #1e3a8a !important;
                            border-color: #1e3a8a !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(1).fi-active .fi-sc-wizard-header-step-label {
                            color: #1e3a8a !important;
                        }

                        /* Wizard Step 2: Discovery Blue 700 */
                        .fi-sc-wizard-header-step:nth-child(2).fi-active .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(2).fi-completed .fi-sc-wizard-header-step-icon-ctn {
                            background-color: #1d4ed8 !important;
                            border-color: #1d4ed8 !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(2).fi-active .fi-sc-wizard-header-step-label {
                            color: #1d4ed8 !important;
                        }

                        /* Wizard Step 3: Discovery Blue 500 (Base) */
                        .fi-sc-wizard-header-step:nth-child(3).fi-active .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(3).fi-completed .fi-sc-wizard-header-step-icon-ctn {
                            background-color: #3b82f6 !important;
                            border-color: #3b82f6 !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(3).fi-active .fi-sc-wizard-header-step-label {
                            color: #3b82f6 !important;
                        }

                        /* Wizard Step 4: Discovery Blue 300 (Light) */
                        .fi-sc-wizard-header-step:nth-child(4).fi-active .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(4).fi-completed .fi-sc-wizard-header-step-icon-ctn {
                            background-color: #93c5fd !important;
                            border-color: #93c5fd !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(4).fi-active .fi-sc-wizard-header-step-icon-ctn .fi-icon {
                            color: #1e3a8a !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(4).fi-active .fi-sc-wizard-header-step-label {
                            color: #3b82f6 !important;
                        }

                        /* Wizard Step 5: Discovery Blue 100 (Lightest) */
                        .fi-sc-wizard-header-step:nth-child(5).fi-active .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(5).fi-completed .fi-sc-wizard-header-step-icon-ctn {
                            background-color: #dbeafe !important;
                            border-color: #3b82f6 !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(5).fi-active .fi-sc-wizard-header-step-icon-ctn .fi-icon,
                        .fi-sc-wizard-header-step:nth-child(5).fi-completed .fi-sc-wizard-header-step-icon-ctn .fi-icon {
                            color: #1d4ed8 !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(5).fi-active .fi-sc-wizard-header-step-label {
                            color: #1d4ed8 !important;
                        }

                        /* White icons for dark backgrounds (Steps 1-3) */
                        .fi-sc-wizard-header-step:nth-child(1).fi-active .fi-sc-wizard-header-step-icon-ctn .fi-icon,
                        .fi-sc-wizard-header-step:nth-child(1).fi-completed .fi-sc-wizard-header-step-icon-ctn .fi-icon,
                        .fi-sc-wizard-header-step:nth-child(2).fi-active .fi-sc-wizard-header-step-icon-ctn .fi-icon,
                        .fi-sc-wizard-header-step:nth-child(2).fi-completed .fi-sc-wizard-header-step-icon-ctn .fi-icon,
                        .fi-sc-wizard-header-step:nth-child(3).fi-active .fi-sc-wizard-header-step-icon-ctn .fi-icon,
                        .fi-sc-wizard-header-step:nth-child(3).fi-completed .fi-sc-wizard-header-step-icon-ctn .fi-icon {
                            color: white !important;
                        }

                        /* Dark icon for Step 4 completed */
                        .fi-sc-wizard-header-step:nth-child(4).fi-completed .fi-sc-wizard-header-step-icon-ctn .fi-icon {
                            color: #1e3a8a !important;
                        }

                        /* Pending step border colors */
                        .fi-sc-wizard-header-step:nth-child(1):not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(2):not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(3):not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-icon-ctn {
                            border-color: #93c5fd !important;
                        }
                        .fi-sc-wizard-header-step:nth-child(4):not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-icon-ctn,
                        .fi-sc-wizard-header-step:nth-child(5):not(.fi-active):not(.fi-completed) .fi-sc-wizard-header-step-icon-ctn {
                            border-color: #dbeafe !important;
                        }
                    </style>
                HTML
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn (): string => view('filament.scripts.global-app-js')->render()
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => auth()->check()
                    ? Blade::render('@livewire(\App\Livewire\HeaderClockWidget::class)')
                    : ''
            )
            // TEMPORARILY DISABLED: Global footer bar
            // ->renderHook(
            //     PanelsRenderHook::BODY_END,
            //     function (): string {
            //         // Global footer with feature flag for staged migration
            //         // v1 = Original Blade-only implementation
            //         // v2 = New FilamentPHP v4 compliant widget
            //         $footerVersion = config('footer.version', 'v1');
            //
            //         if ($footerVersion === 'v2') {
            //             // New FilamentPHP v4 widget - render Livewire component
            //             $content = \Livewire\Livewire::mount(\App\Filament\Widgets\GlobalContextFooter::class);
            //         } else {
            //             // Original v1 footer (fallback)
            //             $content = view('filament.components.project-sticky-footer-global')->render();
            //         }
            //
            //         // Always include project selector modal (used by both versions)
            //         $content .= view('filament.components.project-selector-modal')->render();
            //
            //         return $content;
            //     }
            // )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // AuthenticateSession middleware causes issues with automated browser testing
                // (Playwright, Selenium, etc.) because it validates session hashes and logs out
                // automated browsers. Disable in testing environments.
                ...((env('BROWSER_TESTING') !== true && env('BROWSER_TESTING') !== 'true') ? [AuthenticateSession::class] : []),
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
