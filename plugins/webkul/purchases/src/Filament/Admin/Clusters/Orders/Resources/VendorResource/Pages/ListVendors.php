<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\ListVendors as BaseListVendors;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Vendors class
 *
 * @see \Filament\Resources\Resource
 */
class ListVendors extends BaseListVendors
{
    use HasTableViews;

    protected static string $resource = VendorResource::class;

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        $table = parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('sub_type', 'supplier'));

        return $table;
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('purchases::filament/admin/clusters/orders/resources/vendor/pages/list-vendors.tabs.all'))
                ->icon('heroicon-s-building-office-2')
                ->favorite()
                ->setAsDefault()
                ->badge(Partner::where('sub_type', 'supplier')->count()),
        ];
    }
}
