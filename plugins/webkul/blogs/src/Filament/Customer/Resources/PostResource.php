<?php

namespace Webkul\Blog\Filament\Customer\Resources;

use Webkul\Blog\Filament\Customer\Resources\PostResource\Pages;
use Webkul\Blog\Models\Post;
use Filament\Resources\Resource;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $recordRouteKeyName = 'slug';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('blogs::filament/customer/resources/post.navigation.title');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'view' => Pages\ViewPost::route('/{record}'),
        ];
    }
}
