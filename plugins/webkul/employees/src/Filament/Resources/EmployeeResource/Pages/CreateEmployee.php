<?php

namespace Webkul\Employee\Filament\Resources\EmployeeResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Filament\Resources\EmployeeResource;

/**
 * Create Employee class
 *
 * @see \Filament\Resources\Resource
 */
class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('employees::filament/resources/employee/pages/create-employee.notification.title'))
            ->body(__('employees::filament/resources/employee/pages/create-employee.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'creator_id' => Auth::user()->id,
        ];
    }
}
