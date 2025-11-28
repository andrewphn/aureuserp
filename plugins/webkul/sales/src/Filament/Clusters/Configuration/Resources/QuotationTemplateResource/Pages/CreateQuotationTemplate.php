<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource;

/**
 * Create Quotation Template class
 *
 * @see \Filament\Resources\Resource
 */
class CreateQuotationTemplate extends CreateRecord
{
    protected static string $resource = QuotationTemplateResource::class;

    /**
     * Get the URL to redirect to after creation
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * Get the notification shown after creation
     *
     * @return Notification
     */
    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('sales::filament/clusters/configurations/resources/quotation-template/pages/create-quotation-template.notification.title'))
            ->body(__('sales::filament/clusters/configurations/resources/quotation-template/pages/create-quotation-template.notification.body'));
    }
}
