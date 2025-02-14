<?php

namespace Webkul\Product\Filament\Resources\CategoryResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Product\Filament\Resources\CategoryResource;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('products::filament/resources/category/pages/edit-category.notification.title'))
            ->body(__('products::filament/resources/category/pages/edit-category.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('products::filament/resources/category/pages/edit-category.header-actions.delete.notification.title'))
                        ->body(__('products::filament/resources/category/pages/edit-category.header-actions.delete.notification.body')),
                ),
        ];
    }
}
