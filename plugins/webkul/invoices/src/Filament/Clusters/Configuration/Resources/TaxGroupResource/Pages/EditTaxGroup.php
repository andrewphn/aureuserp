<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxGroupResource\Pages;

use Webkul\Account\Filament\Resources\TaxGroupResource\Pages\EditTaxGroup as BaseEditTaxGroup;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxGroupResource;

/**
 * Edit Tax Group class
 *
 * @see \Filament\Resources\Resource
 */
class EditTaxGroup extends BaseEditTaxGroup
{
    protected static string $resource = TaxGroupResource::class;
}
