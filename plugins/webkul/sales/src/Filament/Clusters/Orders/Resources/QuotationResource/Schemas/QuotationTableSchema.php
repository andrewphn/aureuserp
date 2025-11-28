<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Schemas;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Sale\Enums\OrderState;

/**
 * Quotation Table Schema
 *
 * Extracts the table configuration from QuotationResource.
 * Following FilamentPHP 4 schema organization patterns.
 */
class QuotationTableSchema
{
    /**
     * Configure the table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::getColumns())
            ->filtersFormColumns(2)
            ->filters(static::getFilters())
            ->groups(static::getGroups())
            ->recordActions(static::getRecordActions())
            ->toolbarActions(static::getToolbarActions())
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => static::canSelectRecord($record)
            )
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('currency');
            });
    }

    /**
     * Get table columns
     */
    private static function getColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.number'))
                ->searchable()
                ->toggleable()
                ->sortable(),
            TextColumn::make('state')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.status'))
                ->placeholder('-')
                ->badge()
                ->toggleable()
                ->sortable(),
            TextColumn::make('invoice_status')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.invoice-status'))
                ->placeholder('-')
                ->badge()
                ->sortable(),
            TextColumn::make('created_at')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.creation-date'))
                ->placeholder('-')
                ->date()
                ->sortable(),
            TextColumn::make('amount_untaxed')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.untaxed-amount'))
                ->placeholder('-')
                ->summarize(Sum::make()->label('Total'))
                ->money(fn ($record) => $record->currency->code)
                ->sortable(),
            TextColumn::make('amount_tax')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.amount-tax'))
                ->placeholder('-')
                ->summarize(Sum::make()->label('Taxes'))
                ->money(fn ($record) => $record->currency->code)
                ->sortable(),
            TextColumn::make('amount_total')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.amount-total'))
                ->placeholder('-')
                ->summarize(Sum::make()->label('Total Amount'))
                ->money(fn ($record) => $record->currency->code)
                ->sortable(),
            TextColumn::make('commitment_date')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.commitment-date'))
                ->placeholder('-')
                ->date()
                ->sortable(),
            TextColumn::make('expected_date')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.expected-date'))
                ->placeholder('-')
                ->date()
                ->sortable(),
            TextColumn::make('partner.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.customer'))
                ->placeholder('-')
                ->searchable()
                ->sortable(),
            TextColumn::make('user.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.sales-person'))
                ->placeholder('-')
                ->sortable(),
            TextColumn::make('team.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.sales-team'))
                ->placeholder('-')
                ->sortable(),
            TextColumn::make('client_order_ref')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.customer-reference'))
                ->placeholder('-')
                ->badge()
                ->searchable()
                ->sortable(),
        ];
    }

    /**
     * Get table filters
     */
    private static function getFilters(): array
    {
        return [
            QueryBuilder::make()
                ->constraintPickerColumns(2)
                ->constraints([
                    RelationshipConstraint::make('user.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.sales-person'))
                        ->icon('heroicon-o-user')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.sales-person'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('utm_source_id.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.utm-source'))
                        ->icon('heroicon-o-speaker-wave')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.utm-source'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('company.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.company'))
                        ->icon('heroicon-o-building-office')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.company'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('partner.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.customer'))
                        ->icon('heroicon-o-user')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.customer'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('journal.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.journal'))
                        ->icon('heroicon-o-speaker-wave')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.journal'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('partnerInvoice.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.invoice-address'))
                        ->icon('heroicon-o-map')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.invoice-address'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('partnerShipping.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.shipping-address'))
                        ->icon('heroicon-o-map')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.shipping-address'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('fiscalPosition.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.fiscal-position'))
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.fiscal-position'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('paymentTerm.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.payment-term'))
                        ->icon('heroicon-o-currency-dollar')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.payment-term'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    RelationshipConstraint::make('currency.name')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.currency'))
                        ->icon('heroicon-o-banknotes')
                        ->multiple()
                        ->selectable(
                            IsRelatedToOperator::make()
                                ->titleAttribute('name')
                                ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.currency'))
                                ->searchable()
                                ->multiple()
                                ->preload(),
                        ),
                    DateConstraint::make('created_at')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.created-at')),
                    DateConstraint::make('updated_at')
                        ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.updated-at')),
                ]),
        ];
    }

    /**
     * Get table groups
     */
    private static function getGroups(): array
    {
        return [
            Tables\Grouping\Group::make('medium.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.medium'))
                ->collapsible(),
            Tables\Grouping\Group::make('utmSource.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.source'))
                ->collapsible(),
            Tables\Grouping\Group::make('team.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.team'))
                ->collapsible(),
            Tables\Grouping\Group::make('user.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.sales-person'))
                ->collapsible(),
            Tables\Grouping\Group::make('currency.full_name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.currency'))
                ->collapsible(),
            Tables\Grouping\Group::make('company.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.company'))
                ->collapsible(),
            Tables\Grouping\Group::make('partner.name')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.customer'))
                ->collapsible(),
            Tables\Grouping\Group::make('date_order')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.quotation-date'))
                ->date()
                ->collapsible(),
            Tables\Grouping\Group::make('commitment_date')
                ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.commitment-date'))
                ->date()
                ->collapsible(),
        ];
    }

    /**
     * Get record actions
     */
    private static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Model $record) => $record->state == OrderState::SALE)
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/orders/resources/quotation.table.actions.delete.notification.title'))
                            ->body(__('sales::filament/clusters/orders/resources/quotation.table.actions.delete.notification.body'))
                    ),
                ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/orders/resources/quotation.table.actions.force-delete.notification.title'))
                            ->body(__('sales::filament/clusters/orders/resources/quotation.table.actions.force-delete.notification.body'))
                    ),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/orders/resources/quotation.table.actions.restore.notification.title'))
                            ->body(__('sales::filament/clusters/orders/resources/quotation.table.actions.restore.notification.body'))
                    ),
            ]),
        ];
    }

    /**
     * Get toolbar actions
     */
    private static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.delete.notification.title'))
                            ->body(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.delete.notification.body'))
                    ),
                ForceDeleteBulkAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.force-delete.notification.title'))
                            ->body(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.force-delete.notification.body'))
                    ),
                RestoreBulkAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.restore.notification.title'))
                            ->body(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.restore.notification.body'))
                    ),
            ]),
        ];
    }

    /**
     * Check if record can be selected
     */
    private static function canSelectRecord(Model $record): bool
    {
        // Need to check delete permission from the resource
        return $record->state !== OrderState::SALE;
    }
}
