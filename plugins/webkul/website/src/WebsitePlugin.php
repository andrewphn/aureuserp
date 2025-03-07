<?php

namespace Webkul\Website;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\Support\Package;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Collection;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\MenuItem;
use Webkul\Website\Filament\Customer\Clusters\Account;

class WebsitePlugin implements Plugin
{
    public function getId(): string
    {
        return 'website';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'customer', function (Panel $panel) {
                $panel
                    ->discoverResources(in: $this->getPluginBasePath('/Filament/Customer/Resources'), for: 'Webkul\\Website\\Filament\\Customer\\Resources')
                    ->discoverPages(in: $this->getPluginBasePath('/Filament/Customer/Pages'), for: 'Webkul\\Website\\Filament\\Customer\\Pages')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Customer/Clusters'), for: 'Webkul\\Website\\Filament\\Customer\\Clusters')
                    ->discoverClusters(in: $this->getPluginBasePath('/Filament/Customer/Widgets'), for: 'Webkul\\Website\\Filament\\Customer\\Widgets')
                    ->userMenuItems([
                        'my_account' => MenuItem::make()->label('My Account')
                            ->url(fn (): string => Account::getUrl())
                            ->sort(2),
                    ]) 
                    ->renderHook(
                        PanelsRenderHook::TOPBAR_END,
                        fn (): string => view('website::filament.customer.header.auth-links', [
                            'navigationItems' => $this->getTopNavigationItems(),
                        ])->render(),
                    )
                    ->renderHook(
                        PanelsRenderHook::FOOTER,
                        fn (): string => view('website::filament.customer.footer.index', [
                            'navigationItems' => $this->getFooterNavigationItems(),
                        ])->render(),
                    );
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function getPluginBasePath($path = null): string
    {
        $reflector = new \ReflectionClass(get_class($this));

        return dirname($reflector->getFileName()).($path ?? '');
    }

    protected function getTopNavigationItems(): Collection
    {
       return new Collection([
            NavigationItem::make('Login')
                ->url(filament()->getLoginUrl())
                ->visible(! filament()->auth()->check()),
            NavigationItem::make('Register')
                ->url(filament()->getRegistrationUrl())
                ->visible( ! filament()->auth()->check()),
       ]);
    }

    protected function getFooterNavigationItems(): Collection
    {
        return new Collection([
            NavigationItem::make('Home')
                ->url('/'),
            NavigationItem::make('About Us')
                ->url('/about-us'),
            NavigationItem::make('Contact Us')
                ->url('/contact-us'),
            NavigationItem::make('Privacy Policy')
                ->url('/privacy-policy'),
            NavigationItem::make('Terms & Conditions')
                ->url('/terms-conditions'),
            NavigationItem::make('Services')
                ->url('/services'),
        ]);
    }
}
