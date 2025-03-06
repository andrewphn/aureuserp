<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;

class PreviewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.preview-quotation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Preview'))
            ->modalHeading(__('Preview Quotation'))
            ->modalFooterActions(fn ($record) => [])
            ->modalContent(fn ($record) => view('sales::sales.quotation', ['record' => $record]))
            ->color('gray');
    }
}
