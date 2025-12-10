<?php

namespace Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Product\Filament\Resources\ReferenceTypeCodeResource;

class EditReferenceTypeCode extends EditRecord
{
    protected static string $resource = ReferenceTypeCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['code'] = strtoupper($data['code']);
        return $data;
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title('Type Code Updated')
            ->body('The reference type code has been updated successfully.');
    }
}
