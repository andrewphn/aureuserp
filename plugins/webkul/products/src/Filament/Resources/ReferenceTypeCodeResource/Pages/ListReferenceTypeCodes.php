<?php

namespace Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Product\Filament\Resources\ReferenceTypeCodeResource;
use Webkul\Product\Models\ReferenceTypeCode;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListReferenceTypeCodes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = ReferenceTypeCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Type Code')
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $data['creator_id'] = Auth::id();
                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Type Code Created')
                        ->body('The reference type code has been created successfully.'),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make('All Type Codes')
                ->icon('heroicon-s-tag')
                ->favorite()
                ->setAsDefault()
                ->badge(ReferenceTypeCode::count()),
            'active' => PresetView::make('Active')
                ->icon('heroicon-s-check-circle')
                ->favorite()
                ->badge(ReferenceTypeCode::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'inactive' => PresetView::make('Inactive')
                ->icon('heroicon-s-x-circle')
                ->badge(ReferenceTypeCode::where('is_active', false)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false)),
        ];
    }
}
