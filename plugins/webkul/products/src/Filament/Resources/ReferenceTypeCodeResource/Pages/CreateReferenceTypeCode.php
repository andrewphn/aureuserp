<?php

namespace Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages;

use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Product\Filament\Resources\ReferenceTypeCodeResource;

class CreateReferenceTypeCode extends CreateRecord
{
    protected static string $resource = ReferenceTypeCodeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();
        $data['code'] = strtoupper($data['code']);
        return $data;
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Creation Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title('Type Code Created')
            ->body('The reference type code has been created successfully.');
    }
}
