<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource;
use Webkul\Partner\Filament\Resources\BankAccountResource;

class ManageBankAccounts extends ManageRelatedRecords
{
    protected static string $resource = VendorResource::class;

    protected static string $relationship = 'bankAccounts';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/vendor/pages/manage-bank-account.title');
    }

    public function form(Form $form): Form
    {
        return BankAccountResource::form($form);
    }

    public function table(Table $table): Table
    {
        return BankAccountResource::table($table)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('invoices::filament/clusters/vendors/resources/vendor/pages/manage-bank-account.table.header-actions.create.title'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        return $data;
                    })
            ]);
    }
}
