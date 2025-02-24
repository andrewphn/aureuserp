<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class CreateVendor extends CreatePartner
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sub_type'] = 'supplier';

        return $data;
    }
}
