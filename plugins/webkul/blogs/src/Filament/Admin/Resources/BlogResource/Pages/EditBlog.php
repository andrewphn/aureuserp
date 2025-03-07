<?php

namespace Webkul\Blog\Filament\Admin\Resources\BlogResource\Pages;

use Webkul\Blog\Filament\Admin\Resources\BlogResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('blogs::filament/resources/blog/pages/edit-blog.notification.title'))
            ->body(__('blogs::filament/resources/blog/pages/edit-blog.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('blogs::filament/resources/blog/pages/edit-blog.header-actions.delete.notification.title'))
                        ->body(__('blogs::filament/resources/blog/pages/edit-blog.header-actions.delete.notification.body')),
                ),
        ];
    }
}
