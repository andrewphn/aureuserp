<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\CreateVendor as BaseCreateVendor;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class CreateVendor extends BaseCreateVendor
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sub_type'] = 'supplier';

        return $data;
    }
}
