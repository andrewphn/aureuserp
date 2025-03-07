<?php

namespace Webkul\Blog\Filament\Customer\Resources\PostResource\Pages;

use Webkul\Blog\Filament\Customer\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Blog\Models\Post;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected static string $view = 'blogs::filament.customer.resources.post.pages.list-records';

    public function getTitle(): string | Htmlable
    {
        return __('blogs::filament/customer/resources/post/pages/list-records.navigation.title');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
    
    protected function getRecords(): Paginator
    {
        $query = Post::query();
        
        if (request()->has('search') && $search = request()->input('search')) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }
        
        $query->orderBy('published_at', 'desc');
        
        return $query->paginate(9);
    }
}
