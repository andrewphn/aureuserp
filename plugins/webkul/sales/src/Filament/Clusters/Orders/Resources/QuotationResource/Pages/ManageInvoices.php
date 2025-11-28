<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;
use Livewire\Livewire;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;

/**
 * Manage Invoices class
 *
 * @see \Filament\Resources\Resource
 */
class ManageInvoices extends ManageRelatedRecords
{
    protected static string $resource = QuotationResource::class;

    protected static string $relationship = 'accountMoves';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    /**
     * Get the sub-navigation position
     *
     * @return \Filament\Pages\Enums\SubNavigationPosition
     */
    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('Invoices');
    }

    /**
     * Get the navigation badge
     *
     * @param mixed $parameters
     * @return ?string
     */
    public static function getNavigationBadge($parameters = []): ?string
    {
        return Livewire::current()->getRecord()->accountMoves()->count();
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return InvoiceResource::table($table)
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),

                EditAction::make()
                    ->url(fn ($record) => InvoiceResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ]);
    }
}
