<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageContacts as BaseManageContacts;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class ManageContacts extends BaseManageContacts
{
    protected static string $resource = VendorResource::class;
}
