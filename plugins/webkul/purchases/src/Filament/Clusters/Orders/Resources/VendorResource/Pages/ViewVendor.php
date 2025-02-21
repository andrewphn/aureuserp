<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ViewPartner as BaseViewPartner;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class ViewVendor extends BaseViewPartner
{
    protected static string $resource = VendorResource::class;
}
