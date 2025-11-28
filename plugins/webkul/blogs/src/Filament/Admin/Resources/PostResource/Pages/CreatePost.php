<?php

namespace Webkul\Blog\Filament\Admin\Resources\PostResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Blog\Filament\Admin\Resources\PostResource;

/**
 * Create Post class
 *
 * @see \Filament\Resources\Resource
 */
class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('blogs::filament/admin/resources/post/pages/create-post.notification.title'))
            ->body(__('blogs::filament/admin/resources/post/pages/create-post.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();

        $data['author_id'] = Auth::id();

        return $data;
    }
}
