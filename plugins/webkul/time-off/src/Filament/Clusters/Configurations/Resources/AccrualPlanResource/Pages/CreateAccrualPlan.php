<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources\AccrualPlanResource\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\AccrualPlanResource;

/**
 * Create Accrual Plan class
 *
 * @see \Filament\Resources\Resource
 */
class CreateAccrualPlan extends CreateRecord
{
    protected static string $resource = AccrualPlanResource::class;

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('time-off::filament/clusters/configurations/resources/accrual-plan/pages/create-accrual-plan.notification.title'))
            ->body(__('time-off::filament/clusters/configurations/resources/accrual-plan/pages/create-accrual-plan.notification.body'));
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
