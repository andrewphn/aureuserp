<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource;
use Webkul\Partner\Filament\Resources\BankAccountResource;

/**
 * Manage Bank Accounts class
 *
 * @see \Filament\Resources\Resource
 */
class ManageBankAccounts extends ManageRelatedRecords
{
    protected static string $resource = VendorResource::class;

    protected static string $relationship = 'bankAccounts';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/vendor/pages/manage-bank-account.title');
    }

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
                    ->label(__('invoices::filament/clusters/vendors/resources/vendor/pages/manage-bank-account.table.header-actions.create.title'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function (array $data): array {
                        return $data;
                    }),
            ]);
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();

        $breadcrumbs = [
            $resource::getUrl() => $resource::getBreadcrumb(),
            ...(filled($breadcrumb = $this->getBreadcrumb()) ? [$breadcrumb] : []),
        ];

        $cluster = static::getCluster();

        if (filled($cluster)) {
            return [
                $cluster::getUrl() => $cluster::getClusterBreadcrumb(),
                ...$breadcrumbs,
            ];
        }

        return $breadcrumbs;
    }
}
