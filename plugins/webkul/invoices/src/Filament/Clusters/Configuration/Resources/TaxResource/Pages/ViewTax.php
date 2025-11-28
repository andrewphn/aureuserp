<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource\Pages;

use Webkul\Account\Filament\Resources\TaxResource\Pages\ViewTax as BaseViewTax;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource;

/**
 * View Tax class
 *
 * @see \Filament\Resources\Resource
 */
class ViewTax extends BaseViewTax
{
    protected static string $resource = TaxResource::class;
}
