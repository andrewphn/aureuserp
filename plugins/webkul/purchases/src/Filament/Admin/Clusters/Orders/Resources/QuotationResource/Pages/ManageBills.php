<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\ManageBills as BaseManageBills;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;

/**
 * Manage Bills class
 *
 * @see \Filament\Resources\Resource
 */
class ManageBills extends BaseManageBills
{
    protected static string $resource = QuotationResource::class;
}
