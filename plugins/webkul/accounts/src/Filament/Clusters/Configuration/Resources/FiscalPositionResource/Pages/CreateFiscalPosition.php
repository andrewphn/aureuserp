<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource;
use Webkul\Account\Models\FiscalPosition;

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
            ->title(__('accounts::filament/clusters/configurations/resources/fiscal-position/pages/create-fiscal-position.notification.title'))
            ->body(__('accounts::filament/clusters/configurations/resources/fiscal-position/pages/create-fiscal-position.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['company_id'] = $user?->default_company_id;

        $data['creator_id'] = $user->id;

        $data['sort'] = FiscalPosition::max('sort') + 1;

        return $data;
    }
}
