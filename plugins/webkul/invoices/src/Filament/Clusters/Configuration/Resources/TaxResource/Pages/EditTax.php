<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource\Pages;

use Webkul\Account\Filament\Resources\TaxResource\Pages\EditTax as BaseEditTax;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource;

/**
 * Edit Tax class
 *
 * @see \Filament\Resources\Resource
 */
class EditTax extends BaseEditTax
{
    protected static string $resource = TaxResource::class;
}
