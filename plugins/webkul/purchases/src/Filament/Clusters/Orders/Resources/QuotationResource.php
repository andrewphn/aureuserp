<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources;

use Filament\Pages\SubNavigationPosition;
use Webkul\Purchase\Filament\Clusters\Orders;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

class QuotationResource extends OrderResource
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Orders::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/orders/resources/quotation.navigation.title');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view'   => Pages\ViewQuotation::route('/{record}'),
            'edit'   => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
