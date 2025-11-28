<?php

namespace Webkul\Field\Filament\Resources\FieldResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Field\FieldsColumnManager;
use Webkul\Field\Filament\Resources\FieldResource;

/**
 * Edit Field class
 *
 * @see \Filament\Resources\Resource
 */
class EditField extends EditRecord
{
    protected static string $resource = FieldResource::class;

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('fields::filament/resources/field/pages/edit-field.notification.title'))
            ->body(__('fields::filament/resources/field/pages/edit-field.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * After Save
     *
     * @return void
     */
    protected function afterSave(): void
    {
        FieldsColumnManager::updateColumn($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
