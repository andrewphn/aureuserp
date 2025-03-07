<?php

namespace Webkul\Blog\Filament\Admin\Resources\BlogResource\Pages;

use Webkul\Blog\Filament\Admin\Resources\BlogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewBlog extends ViewRecord
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('blogs::filament/resources/blog/pages/view-blog.header-actions.delete.notification.title'))
                        ->body(__('blogs::filament/resources/blog/pages/view-blog.header-actions.delete.notification.body')),
                ),
        ];
    }
}
