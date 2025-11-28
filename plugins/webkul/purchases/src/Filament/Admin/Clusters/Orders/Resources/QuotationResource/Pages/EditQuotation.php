<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource;

/**
 * Edit Quotation class
 *
 * @see \Filament\Resources\Resource
 */
class EditQuotation extends EditOrder
{
    protected static string $resource = QuotationResource::class;
}
