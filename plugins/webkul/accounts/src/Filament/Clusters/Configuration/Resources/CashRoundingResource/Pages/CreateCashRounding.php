<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource;

class CreateCashRounding extends CreateRecord
{
    protected static string $resource = CashRoundingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/clusters/configurations/resources/cash-rounding/pages/create-cash-rounding.notification.title'))
            ->body(__('accounts::filament/clusters/configurations/resources/cash-rounding/pages/create-cash-rounding.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::user()->id;

        return $data;
    }
}
