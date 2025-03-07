<?php

namespace Webkul\Blog\Filament\Admin\Resources\BlogResource\Pages;

use Webkul\Blog\Filament\Admin\Resources\BlogResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBlog extends CreateRecord
{
    protected static string $resource = BlogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('blogs::filament/resources/blog/pages/create-blog.notification.title'))
            ->body(__('blogs::filament/resources/blog/pages/create-blog.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();

        return $data;
    }
}
