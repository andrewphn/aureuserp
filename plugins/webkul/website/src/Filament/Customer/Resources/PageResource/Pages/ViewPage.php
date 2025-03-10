<?php

namespace Webkul\Website\Filament\Customer\Resources\PageResource\Pages;

use Webkul\Website\Filament\Customer\Resources\PageResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewPage extends ViewRecord
{
    protected static string $resource = PageResource::class;

    protected static string $view = 'website::filament.customer.resources.page.pages.view-record';

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->title;
    }
}
