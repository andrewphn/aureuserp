<?php

namespace Webkul\Account\Filament\Resources\JournalResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\JournalResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListJournals extends ListRecords
{
    use HasTableViews;

    protected static string $resource = JournalResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/journal/pages/list-journals.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
