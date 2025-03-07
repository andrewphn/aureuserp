<?php

namespace Webkul\Blog\Filament\Admin\Resources\BlogResource\Pages;

use Webkul\Blog\Filament\Admin\Resources\BlogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListBlogs extends ListRecords
{
    use HasTableViews;

    protected static string $resource = BlogResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('projects::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.tabs.all'))
                ->badge(ActivityPlan::where('plugin', 'projects')->count()),
            'archived' => Tab::make(__('projects::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.tabs.archived'))
                ->badge(ActivityPlan::where('plugin', 'projects')->onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('blogs::filament/resources/blog/pages/list-blogs.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
