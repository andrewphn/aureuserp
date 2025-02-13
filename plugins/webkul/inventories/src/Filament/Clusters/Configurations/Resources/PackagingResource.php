<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Webkul\Product\Filament\Resources\PackagingResource as BasePackagingResource;
use Filament\Tables\Table;
use Webkul\Inventory\Filament\Clusters\Configurations;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackagingResource\Pages;
use Webkul\Inventory\Models\Packaging;
use Webkul\Inventory\Settings\ProductSettings;

class PackagingResource extends BasePackagingResource
{
    protected static ?string $model = Packaging::class;

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
        return __('inventories::filament/clusters/configurations/resources/packaging.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/configurations/resources/packaging.navigation.title');
    }

    public static function form(Form $form): Form
    {
        $form = BasePackagingResource::form($form);

        $components = $form->getComponents();

        $components[] = Forms\Components\Select::make('package_type_id')
            ->label(__('inventories::filament/clusters/configurations/resources/packaging.form.package-type'))
            ->relationship('packageType', 'name')
            ->searchable()
            ->preload();

        $components[] = Forms\Components\Select::make('routes')
            ->label(__('inventories::filament/clusters/configurations/resources/packaging.form.routes'))
            ->relationship('routes', 'name')
            ->searchable()
            ->preload()
            ->multiple();

        $form->components($components); 

        return $form;
    }

    public static function table(Table $table): Table
    {
        $table = BasePackagingResource::table($table);

        $columns = $table->getColumns();

        $filters = $table->getFilters();

        $columns[] = Tables\Columns\TextColumn::make('packageType.name')
                ->label(__('inventories::filament/clusters/configurations/resources/packaging.table.columns.package-type'))
                ->numeric()
                ->sortable();

        $filters[] = Tables\Filters\SelectFilter::make('packageType')
                ->label(__('inventories::filament/clusters/configurations/resources/packaging.table.filters.package-type'))
                ->relationship('packageType', 'name')
                ->searchable()
                ->preload();

        $table->columns($columns);

        $table->filters($filters);

        return $table;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return BasePackagingResource::infolist($infolist);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePackagings::route('/'),
        ];
    }
}
