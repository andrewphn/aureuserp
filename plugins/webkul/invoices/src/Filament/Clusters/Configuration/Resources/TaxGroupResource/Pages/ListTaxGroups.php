<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxGroupResource\Pages;

use Webkul\Account\Filament\Resources\TaxGroupResource\Pages\ListTaxGroups as BaseListTaxGroups;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxGroupResource;

/**
 * List Tax Groups class
 *
 * @see \Filament\Resources\Resource
 */
class ListTaxGroups extends BaseListTaxGroups
{
    protected static string $resource = TaxGroupResource::class;
}
