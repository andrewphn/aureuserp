<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

use Filament\Tables\Table;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\ListVendors as BaseListVendors;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource;

class ListVendors extends BaseListVendors
{
    protected static string $resource = VendorResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('sub_type', 'supplier'));

        return $table;
    }
}
