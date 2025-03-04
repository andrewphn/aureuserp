<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages\ManageBills as BaseManageBills;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource;

class ManageBills extends BaseManageBills
{
    protected static string $resource = QuotationResource::class;
}
