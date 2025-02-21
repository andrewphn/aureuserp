<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\EditInvoice as BaseEditRecord;

class EditReceipts extends BaseEditRecord
{
    protected static string $resource = ReceiptsResource::class;
}
