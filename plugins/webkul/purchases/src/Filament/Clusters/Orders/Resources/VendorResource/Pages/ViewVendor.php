<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ViewPartner as BaseViewPartner;

class ViewVendor extends BaseViewPartner
{
    protected static string $resource = VendorResource::class;
}
