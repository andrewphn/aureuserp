<?php

namespace Webkul\Account\Filament\Resources\CreditNoteResource\Pages;

use Filament\Notifications\Notification;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Facades\Account;
use Webkul\Account\Filament\Resources\CreditNoteResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\CreateInvoice as CreateRecord;

/**
 * Create Credit Note class
 *
 * @see \Filament\Resources\Resource
 */
class CreateCreditNote extends CreateRecord
{
    protected static string $resource = CreditNoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/resources/credit-note/pages/create-credit-note.notification.title'))
            ->body(__('accounts::filament/resources/credit-note/pages/create-credit-note.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['move_type'] ??= MoveType::OUT_REFUND;

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
