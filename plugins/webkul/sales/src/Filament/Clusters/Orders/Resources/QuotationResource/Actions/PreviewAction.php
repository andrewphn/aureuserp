<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

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
            ->modalIcon('heroicon-s-document-text')
            ->modalHeading(__('Preview Quotation'))
            ->modalWidth(MaxWidth::SevenExtraLarge)
            ->modalFooterActions(function ($record) {
                return [];
            })
            ->modalContent(function ($record) {
                return view('sales::sales.quotation', ['record' => $record]);
            })
            ->color('gray');
    }
}
