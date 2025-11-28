<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\EditPartner as BaseEditPartner;

/**
 * Edit Partner class
 *
 * @see \Filament\Resources\Resource
 */
class EditPartner extends BaseEditPartner
{
    protected static string $resource = PartnerResource::class;
}
