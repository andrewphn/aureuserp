<?php

namespace Webkul\Account\Filament\Resources\FiscalPositionResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Filament\Resources\FiscalPositionResource;

/**
 * Create Fiscal Position class
 *
 * @see \Filament\Resources\Resource
 */
class CreateFiscalPosition extends CreateRecord
{
    protected static string $resource = FiscalPositionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/resources/fiscal-position/pages/create-fiscal-position.notification.title'))
            ->body(__('accounts::filament/resources/fiscal-position/pages/create-fiscal-position.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['company_id'] = $user?->default_company_id;

        $data['creator_id'] = $user->id;

        return $data;
    }
}
