<?php

namespace Webkul\Website\Filament\Admin\Resources\PartnerResource\Pages;

use Webkul\Website\Filament\Admin\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListPartners;

class ListPartners extends BaseListPartners
{
    protected static string $resource = PartnerResource::class;
}
