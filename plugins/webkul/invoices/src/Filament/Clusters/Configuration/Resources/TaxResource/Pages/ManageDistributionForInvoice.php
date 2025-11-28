<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource\Pages;

use Webkul\Account\Filament\Resources\TaxResource\Pages\ManageDistributionForInvoice as BaseManageDistributionForInvoice;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource;

/**
 * Manage Distribution For Invoice class
 *
 * @see \Filament\Resources\Resource
 */
class ManageDistributionForInvoice extends BaseManageDistributionForInvoice
{
    protected static string $resource = TaxResource::class;
}
