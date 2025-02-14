<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Contact\Filament\Clusters\Configurations;
use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource\Pages;
use Webkul\Partner\Filament\Resources\BankAccountResource as BaseBankAccountResource;
use Webkul\Partner\Models\BankAccount;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationGroup(): string
    {
        return __('contacts::filament/clusters/configurations/resources/bank-account.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('contacts::filament/clusters/configurations/resources/bank-account.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return BaseBankAccountResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseBankAccountResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBankAccounts::route('/'),
        ];
    }
}
