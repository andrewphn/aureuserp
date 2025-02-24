<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\EditInvoice as BaseEditRecord;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;

class EditReceipts extends BaseEditRecord
{
    protected static string $resource = ReceiptsResource::class;
}
