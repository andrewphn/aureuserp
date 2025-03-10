<?php

namespace Webkul\Blog\Filament\Customer\Resources\PostResource\Pages;

use Webkul\Blog\Filament\Customer\Resources\PostResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected static string $view = 'blogs::filament.customer.resources.post.pages.view-record';

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->title;
    }
}
