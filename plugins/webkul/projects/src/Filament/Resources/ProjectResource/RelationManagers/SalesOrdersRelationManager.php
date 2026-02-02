<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;

/**
 * Sales Orders Relation Manager
 *
 * Displays and manages sales orders for a specific project.
 */
class SalesOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Sales Orders';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-shopping-bag';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('partner.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->partner?->name),

                TextColumn::make('state')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('invoice_status')
                    ->label('Invoice Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '-')
                    ->color(fn ($state) => match ($state) {
                        InvoiceStatus::INVOICED => 'success',
                        InvoiceStatus::TO_INVOICE => 'warning',
                        InvoiceStatus::UP_SELLING => 'info',
                        InvoiceStatus::NO => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('amount_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('date_order')
                    ->label('Order Date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Salesperson')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('validity_date')
                    ->label('Expiration')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date_order', 'desc')
            ->filters([
                SelectFilter::make('state')
                    ->label('Status')
                    ->options(OrderState::options())
                    ->multiple(),

                SelectFilter::make('invoice_status')
                    ->label('Invoice Status')
                    ->options(InvoiceStatus::options())
                    ->multiple(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('create_quotation')
                    ->label('New Quotation')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => QuotationResource::getUrl('create', [
                        'project_id' => $this->getOwnerRecord()->id,
                    ])),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(function ($record) {
                        // Route to Order or Quotation resource based on state
                        if ($record->state === OrderState::SALE) {
                            return OrderResource::getUrl('view', ['record' => $record]);
                        }
                        return QuotationResource::getUrl('view', ['record' => $record]);
                    }),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading('No sales orders')
            ->emptyStateDescription('Create a quotation to get started.');
    }
}
