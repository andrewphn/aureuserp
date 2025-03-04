<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Filament\Pages\SubNavigationPosition;
use Webkul\Account\Filament\Resources\CreditNoteResource\Pages\ListCreditNotes as BaseListInvoices;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;

class ListCreditNotes extends BaseListInvoices
{
    protected static string $resource = CreditNotesResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }
}
