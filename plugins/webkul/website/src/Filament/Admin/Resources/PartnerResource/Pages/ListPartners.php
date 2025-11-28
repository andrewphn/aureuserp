<?php

namespace Webkul\Website\Filament\Admin\Resources\PartnerResource\Pages;

use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListPartners;
use Webkul\Website\Filament\Admin\Resources\PartnerResource;

/**
 * List Partners class
 *
 * @see \Filament\Resources\Resource
 */
class ListPartners extends BaseListPartners
{
    protected static string $resource = PartnerResource::class;
}
