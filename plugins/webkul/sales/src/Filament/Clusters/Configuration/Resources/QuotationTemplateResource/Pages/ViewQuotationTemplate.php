<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource;

/**
 * View Quotation Template class
 *
 * @see \Filament\Resources\Resource
 */
class ViewQuotationTemplate extends ViewRecord
{
    protected static string $resource = QuotationTemplateResource::class;

    /**
     * Get the sub-navigation position for this page
     *
     * @return SubNavigationPosition
     */
    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * Get the header actions for viewing a template
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('sales::filament/clusters/configurations/resources/quotation-template/pages/view-quotation-template.header-actions.notification.delete.title'))
                        ->body(__('sales::filament/clusters/configurations/resources/quotation-template/pages/view-quotation-template.header-actions.notification.delete.body'))
                ),
        ];
    }
}
