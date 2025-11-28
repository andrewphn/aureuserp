<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityPlanResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ActivityPlanResource;

/**
 * Edit Activity Plan class
 *
 * @see \Filament\Resources\Resource
 */
class EditActivityPlan extends EditRecord
{
    protected static string $resource = ActivityPlanResource::class;

    /**
     * Get the URL to redirect to after saving
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * Get the notification shown after saving
     *
     * @return Notification
     */
    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('sales::filament/clusters/configurations/resources/activity-plan/pages/edit-activity-plan.notification.title'))
            ->body(__('sales::filament/clusters/configurations/resources/activity-plan/pages/edit-activity-plan.notification.body'));
    }

    /**
     * Get the header actions for editing an activity plan
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('sales::filament/clusters/configurations/resources/activity-plan/pages/edit-activity-plan.header-actions.delete.notification.title'))
                        ->body(__('sales::filament/clusters/configurations/resources/activity-plan/pages/edit-activity-plan.header-actions.delete.notification.body')),
                ),
        ];
    }
}
