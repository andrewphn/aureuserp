<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner as BaseCreatePartner;

/**
 * Create Partner class
 *
 * @see \Filament\Resources\Resource
 */
class CreatePartner extends BaseCreatePartner
{
    protected static string $resource = PartnerResource::class;
}
