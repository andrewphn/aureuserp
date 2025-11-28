<?php

namespace Webkul\Support\Filament\Resources\ActivityTypeResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Support\Filament\Resources\ActivityTypeResource;

/**
 * Create Activity Type class
 *
 * @see \Filament\Resources\Resource
 */
class CreateActivityType extends CreateRecord
{
    protected static string $resource = ActivityTypeResource::class;

    protected static ?string $pluginName = 'support';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected static function getPluginName()
    {
        return static::$pluginName;
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('support::filament/resources/activity-type/pages/create-activity-type.notification.title'))
            ->body(__('support::filament/resources/activity-type/pages/create-activity-type.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['plugin'] = static::getPluginName();

        return $data;
    }
}
