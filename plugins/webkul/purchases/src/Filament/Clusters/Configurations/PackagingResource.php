<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources;

use Webkul\Purchase\Filament\Clusters\Configurations;
use Webkul\Purchase\Filament\Clusters\Configurations\Resources\PackagingResource\Pages;
use Webkul\Purchase\Settings\ProductSettings;
use Webkul\Product\Filament\Resources\PackagingResource as BasePackagingResource;

class PackagingResource extends BasePackagingResource
{
    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function isDiscovered(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        return app(ProductSettings::class)->enable_packagings;
    }

    public static function getNavigationGroup(): string
    {
        return __('purchases::filament/clusters/configurations/resources/packaging.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/configurations/resources/packaging.navigation.title');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePackagings::route('/'),
        ];
    }
}
