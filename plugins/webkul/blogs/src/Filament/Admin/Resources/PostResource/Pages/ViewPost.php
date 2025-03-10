<?php

namespace Webkul\Blog\Filament\Admin\Resources\PostResource\Pages;

use Webkul\Blog\Filament\Admin\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('blogs::filament/admin/resources/post/pages/view-post.header-actions.delete.notification.title'))
                        ->body(__('blogs::filament/admin/resources/post/pages/view-post.header-actions.delete.notification.body')),
                ),
        ];
    }
}
