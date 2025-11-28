<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource\Pages;

use Webkul\Account\Filament\Resources\TaxResource\Pages\ListTaxes as BaseListTaxes;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource;

/**
 * List Taxes class
 *
 * @see \Filament\Resources\Resource
 */
class ListTaxes extends BaseListTaxes
{
    protected static string $resource = TaxResource::class;
}
