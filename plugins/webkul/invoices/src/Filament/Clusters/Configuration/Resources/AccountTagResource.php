<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Clusters\Configuration\Resources\AccountTagResource as BaseAccountTagResource;
use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountTagResource\Pages;

class AccountTagResource extends BaseAccountTagResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountTags::route('/'),
        ];
    }
}
