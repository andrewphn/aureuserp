<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\ManageAddresses as BaseManageAddresses;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource;

/**
 * Manage Addresses class
 *
 * @see \Filament\Resources\Resource
 */
class ManageAddresses extends BaseManageAddresses
{
    protected static string $resource = VendorResource::class;
}
