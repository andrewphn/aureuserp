<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Resources\FiscalPositionResource as BaseFiscalPositionResource;
use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

class FiscalPositionResource extends BaseFiscalPositionResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/configurations/resources/fiscal-position.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/configurations/resources/fiscal-position.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('invoices::filament/clusters/configurations/resources/fiscal-position.navigation.group');
    }

    public static function getPages(): array
    {
        return [
            'index'               => Pages\ListFiscalPositions::route('/'),
            'create'              => Pages\CreateFiscalPosition::route('/create'),
            'view'                => Pages\ViewFiscalPosition::route('/{record}'),
            'edit'                => Pages\EditFiscalPosition::route('/{record}/edit'),
            'fiscal-position-tax' => Pages\ManageFiscalPositionTax::route('/{record}/fiscal-position-tax'),
        ];
    }
}
