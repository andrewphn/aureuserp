<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\IndustryResource\Pages;
use Webkul\Partner\Filament\Resources\IndustryResource as BaseIndustryResource;
use Webkul\Partner\Models\Industry;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/industry.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return BaseIndustryResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseIndustryResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIndustries::route('/'),
        ];
    }
}
