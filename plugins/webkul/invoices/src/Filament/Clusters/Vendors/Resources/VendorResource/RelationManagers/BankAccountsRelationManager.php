<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Partner\Filament\Resources\BankAccountResource;

/**
 * Bank Accounts Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return BankAccountResource::form($schema);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return BankAccountResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label(__('invoices::filament/clusters/vendors/resources/vendor/relation-manager/bank-account-relation-manager.create-bank-account'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function (array $data): array {
                        return $data;
                    }),
            ]);
    }
}
