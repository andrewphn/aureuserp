<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\TeamResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\TeamResource;

/**
 * Create Team class
 *
 * @see \Filament\Resources\Resource
 */
class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

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
            ->title(__('sales::filament/clusters/configurations/resources/team/pages/create-team.notification.title'))
            ->body(__('sales::filament/clusters/configurations/resources/team/pages/create-team.notification.body'));
    }

    /**
     * Mutate form data before creating the record
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['creator_id'] = $user->id;

        return $data;
    }
}
