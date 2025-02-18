<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;
use Filament\Tables\Table;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource as BaseInvoiceResource;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\InvoiceResource;

class RefundResource extends BaseInvoiceResource
{
    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

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
        return InvoiceResource::table($table);
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
