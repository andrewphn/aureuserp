<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource\Pages;

use Webkul\Account\Filament\Resources\TaxResource\Pages\CreateTax as BaseCreateTax;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource;

/**
 * Create Tax class
 *
 * @see \Filament\Resources\Resource
 */
class CreateTax extends BaseCreateTax
{
    protected static string $resource = TaxResource::class;
}
