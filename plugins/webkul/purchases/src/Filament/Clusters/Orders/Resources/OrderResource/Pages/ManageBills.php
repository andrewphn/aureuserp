<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;

class ManageBills extends ManageRelatedRecords
{
    protected static string $resource = OrderResource::class;

    protected static string $relationship = 'accountMoves';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/orders/resources/order/pages/manage-bills.navigation.title');
    }

    public function table(Table $table): Table
    {
        return BillResource::table($table)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => BillResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),
                
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => BillResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ]);
    }
}
