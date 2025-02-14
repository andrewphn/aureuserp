<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;
use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource as BaseCashRoundingResource;

class CashRoundingResource extends BaseCashRoundingResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCashRounding::route('/'),
            'create' => Pages\CreateCashRounding::route('/create'),
            'view'   => Pages\ViewCashRounding::route('/{record}'),
            'edit'   => Pages\EditCashRounding::route('/{record}/edit'),
        ];
    }
}
