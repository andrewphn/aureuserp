<?php

namespace Webkul\Blog\Filament\Front\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Blogs extends Page
{
    protected static string $view = 'blogs::filament.front.pages.blogs';

    public function getTitle(): string | Htmlable
    {
        return 'Our Latest Posts';
    }
}
