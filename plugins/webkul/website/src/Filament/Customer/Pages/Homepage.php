<?php

namespace Webkul\Website\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Pages\SimplePage;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Webkul\TcsCms\Models\HomeSection;
use Webkul\TcsCms\Models\Page as TcsCmsPage;
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

    // Use simple layout without panel navigation
    protected static string $layout = 'tcs-cms::layouts.public';

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
        return 'TCS Woodwork - Custom Cabinetry & Fine Woodworking';
    }

    /**
     * Get home sections for rendering
     */
    public function getHomeSections(): Collection
    {
        return HomeSection::where('is_active', true)
            ->orderBy('position')
            ->get();
    }

    /**
     * Check if there are home sections to display
     */
    public function hasHomeSections(): bool
    {
        return $this->getHomeSections()->count() > 0;
    }

    /**
     * Get content from TCS CMS pages first, fallback to website pages
     */
    public function getContent(): string|Htmlable
    {
        // Try TCS CMS page first
        $tcsCmsPage = TcsCmsPage::where('slug', 'home')
            ->orWhere('slug', 'homepage')
            ->orWhere('layout', 'homepage')
            ->where('is_published', true)
            ->first();

        if ($tcsCmsPage) {
            return $tcsCmsPage->content ?? '';
        }

        // Fallback to website pages
        $homePage = PageModel::where('slug', 'home')->first();

        return $homePage?->content ?? '';
    }

    /**
     * Get TCS CMS page blocks for rendering
     */
    public function getBlocks(): array
    {
        $tcsCmsPage = TcsCmsPage::where('slug', 'home')
            ->orWhere('slug', 'homepage')
            ->orWhere('layout', 'homepage')
            ->where('is_published', true)
            ->first();

        return $tcsCmsPage?->mutated_blocks ?? [];
    }

    /**
     * Check if TCS CMS page has blocks
     */
    public function hasBlocks(): bool
    {
        return count($this->getBlocks()) > 0;
    }
}
