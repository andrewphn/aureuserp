<?php

namespace Webkul\TimeOff\Filament\Clusters\Management\Resources\TimeOffResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\TimeOff\Filament\Clusters\Management\Resources\TimeOffResource;
use Webkul\TimeOff\Traits\TimeOffHelper;

/**
 * Create Time Off class
 *
 * @see \Filament\Resources\Resource
 */
class CreateTimeOff extends CreateRecord
{
    use TimeOffHelper;

    protected static string $resource = TimeOffResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('time-off::filament/clusters/management/resources/time-off/pages/create-time-off.notification.title'))
            ->body(__('time-off::filament/clusters/management/resources/time-off/pages/create-time-off.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutateTimeOffData($data, $this->record?->id);

    }
}
