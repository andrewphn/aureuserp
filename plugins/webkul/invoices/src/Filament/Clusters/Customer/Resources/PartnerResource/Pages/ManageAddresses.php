<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\ManageAddresses as BaseManageAddresses;

/**
 * Manage Addresses class
 *
 * @see \Filament\Resources\Resource
 */
class ManageAddresses extends BaseManageAddresses
{
    protected static string $resource = PartnerResource::class;
}
