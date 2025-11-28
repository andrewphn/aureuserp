<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxGroupResource\Pages;

use Webkul\Account\Filament\Resources\TaxGroupResource\Pages\CreateTaxGroup as BaseCreateTaxGroup;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxGroupResource;

/**
 * Create Tax Group class
 *
 * @see \Filament\Resources\Resource
 */
class CreateTaxGroup extends BaseCreateTaxGroup
{
    protected static string $resource = TaxGroupResource::class;
}
