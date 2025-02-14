<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankResource\Pages;
use Webkul\Partner\Filament\Resources\BankResource as BaseBankResource;
use Webkul\Support\Models\Bank;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationGroup(): string
    {
        return __('contacts::filament/clusters/configurations/resources/bank.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/bank.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return BaseBankResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseBankResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBanks::route('/'),
        ];
    }
}
