<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Partner\Filament\Resources\PartnerResource\Pages\EditPartner as BaseEditPartner;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class EditVendor extends BaseEditPartner
{
    protected static string $resource = VendorResource::class;
}
