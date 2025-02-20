<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;
use Filament\Tables\Table;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource as BaseInvoiceResource;

class RefundResource extends BaseInvoiceResource
{
    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Vendors::class;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getModelLabel(): string
    {
        return __('Refunds');
    }

    public static function getNavigationLabel(): string
    {
        return __('Refunds');
    }

    public static function table(Table $table): Table
    {
        return BaseInvoiceResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefunds::route('/'),
            'create' => Pages\CreateRefund::route('/create'),
            'edit' => Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}
