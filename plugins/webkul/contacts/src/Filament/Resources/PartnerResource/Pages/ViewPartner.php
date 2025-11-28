<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ViewPartner as BaseViewPartner;

/**
 * View Partner class
 *
 * @see \Filament\Resources\Resource
 */
class ViewPartner extends BaseViewPartner
{
    protected static string $resource = PartnerResource::class;
}
