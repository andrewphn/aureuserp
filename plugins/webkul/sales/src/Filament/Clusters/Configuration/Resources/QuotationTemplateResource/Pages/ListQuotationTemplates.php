<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Quotation Templates class
 *
 * @see \Filament\Resources\Resource
 */
class ListQuotationTemplates extends ListRecords
{
    use HasTableViews;

    protected static string $resource = QuotationTemplateResource::class;

    /**
     * Get the header actions for the list page
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    /**
     * Get preset table views for filtering templates
     *
     * @return array<string, PresetView>
     */
    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('sales::filament/clusters/configurations/resources/quotation-template/pages/list-quotation-templates.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }
}
