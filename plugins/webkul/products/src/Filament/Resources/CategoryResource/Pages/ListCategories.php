<?php

namespace Webkul\Product\Filament\Resources\CategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Product\Filament\Resources\CategoryResource;
use Webkul\Product\Models\Category;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListCategories extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('products::filament/resources/category/pages/list-categories.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] = $user->default_company_id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('products::filament/resources/category/pages/list-categories.header-actions.create.notification.title'))
                        ->body(__('products::filament/resources/category/pages/list-categories.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('products::filament/resources/category/pages/list-categories.tabs.all'))
                ->icon('heroicon-s-folder')
                ->favorite()
                ->setAsDefault()
                ->badge(Category::count()),
            'archived' => PresetView::make(__('products::filament/resources/category/pages/list-categories.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(Category::onlyTrashed()->count())
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }
}
