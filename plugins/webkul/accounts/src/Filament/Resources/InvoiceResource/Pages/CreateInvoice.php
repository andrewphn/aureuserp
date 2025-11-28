<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Facades\Account;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Support\Concerns\HasRepeaterColumnManager;

/**
 * Create Invoice class
 *
 * @see \Filament\Resources\Resource
 */
class CreateInvoice extends CreateRecord
{
    use HasRepeaterColumnManager;

    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/resources/invoice/pages/create-invoice.notification.title'))
            ->body(__('accounts::filament/resources/invoice/pages/create-invoice.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['move_type'] ??= MoveType::OUT_INVOICE;

        $data['date'] = now();

        return $data;
    }

    /**
     * After Create
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        Account::computeAccountMove($this->getRecord());
    }
}
