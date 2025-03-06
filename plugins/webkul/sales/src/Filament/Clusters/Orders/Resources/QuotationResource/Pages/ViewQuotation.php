<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Filament\Actions;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Chatter\Filament\Actions as ChatterActions;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions as BaseActions;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterActions\ChatterAction::make()
                ->setResource($this->getResource()),
            BaseActions\BackToQuotationAction::make(),
            BaseActions\CancelQuotationAction::make(),
            BaseActions\ConfirmAction::make(),
            BaseActions\CreateInvoiceAction::make(),
            BaseActions\PreviewAction::make(),
            BaseActions\SendByEmailAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
