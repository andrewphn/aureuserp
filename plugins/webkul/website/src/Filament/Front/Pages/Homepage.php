<?php

namespace Webkul\Website\Filament\Front\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Filament\Pages\Page;

class Homepage extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    /**
     * @var view-string
     */
    protected static string $view = 'website::filament.front.pages.homepage';

    public static function getNavigationLabel(): string
    {
        return 'Home';
    }

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    public function getTitle(): string | Htmlable
    {
        return 'Homepage';
    }
}
