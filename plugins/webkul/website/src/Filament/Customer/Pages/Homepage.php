<?php

namespace Webkul\Website\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Website\Models\Page as PageModel;

/**
 * Homepage class
 *
 * @see \Filament\Resources\Resource
 */
class Homepage extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    /**
     * @var view-string
     */
    protected string $view = 'website::filament.customer.pages.homepage';

    public static function getNavigationLabel(): string
    {
        return 'Home';
    }

    /**
     * Get Route Path
     *
     * @param Panel $panel
     * @return string
     */
    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Homepage';
    }

    public function getContent(): string|Htmlable
    {
        $homePage = PageModel::where('slug', 'home')->first();

        return $homePage?->content ?? '';
    }
}
