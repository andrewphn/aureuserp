<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\IncoTermResource\Pages;
use Webkul\Account\Filament\Resources\IncoTermResource as BaseIncoTermResource;

class IncoTermResource extends BaseIncoTermResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncoTerms::route('/'),
        ];
    }
}
