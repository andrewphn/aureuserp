<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;
use Webkul\Account\Filament\Clusters\Configuration\Resources\AccountResource as BaseAccountResource;

class AccountResource extends BaseAccountResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'view'   => Pages\ViewAccount::route('/{record}'),
            'edit'   => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
