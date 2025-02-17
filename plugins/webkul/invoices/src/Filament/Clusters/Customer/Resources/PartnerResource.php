<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Partner\Filament\Resources\PartnerResource as BasePartnerResource;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource\Pages;

class PartnerResource extends BasePartnerResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $cluster = Customer::class;

    public static function getModelLabel(): string
    {
        return __('Customers');
    }

    public static function getNavigationLabel(): string
    {
        return __('Customers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Invoices');
    }

    public static function table(Table $table): Table
    {
        return BasePartnerResource::table($table)
            ->contentGrid([
                'sm'  => 1,
                'md'  => 2,
                'xl'  => 3,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'     => Pages\ListPartners::route('/'),
            'create'    => Pages\CreatePartner::route('/create'),
            'view'      => Pages\ViewPartner::route('/{record}'),
            'edit'      => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
