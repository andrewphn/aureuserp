<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageAddresses as BaseManageAddresses;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class ManageAddresses extends BaseManageAddresses
{
    protected static string $resource = VendorResource::class;
}
