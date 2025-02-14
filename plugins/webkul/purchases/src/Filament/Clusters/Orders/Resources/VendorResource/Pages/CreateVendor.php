<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner;

class CreateVendor extends CreatePartner
{
    protected static string $resource = VendorResource::class;
}
