<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\EditPartner as BaseEditPartner;

class EditVendor extends BaseEditPartner
{
    protected static string $resource = VendorResource::class;
}
