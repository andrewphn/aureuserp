<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\EditVendor as BaseEditVendor;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource;

/**
 * Edit Vendor class
 *
 * @see \Filament\Resources\Resource
 */
class EditVendor extends BaseEditVendor
{
    protected static string $resource = VendorResource::class;
}
