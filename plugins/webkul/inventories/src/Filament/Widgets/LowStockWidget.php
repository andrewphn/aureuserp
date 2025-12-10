<?php

namespace Webkul\Inventory\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Models\OrderPoint;

/**
 * Low Stock Widget - Shows products below their reorder points
 */
class LowStockWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public function getTableHeading(): string
    {
        return 'Low Stock Items';
    }

    public function getTableDescription(): ?string
    {
        $count = $this->getLowStockCount();

        if ($count === 0) {
            return 'All items are above their reorder points.';
        }

        return "{$count} item(s) need attention";
    }

    protected function getLowStockCount(): int
    {
        return OrderPoint::query()
            ->whereHas('product', function (Builder $query) {
                $query->whereRaw(
                    'COALESCE((SELECT SUM(pq.quantity) FROM inventories_product_quantities pq
                    JOIN inventories_locations l ON pq.location_id = l.id
                    WHERE pq.product_id = products_products.id
                    AND l.type = ? AND l.is_scrap = 0), 0) <= inventories_order_points.product_min_qty',
                    [LocationType::INTERNAL->value]
                );
            })
            ->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderPoint::query()
                    ->with(['product', 'warehouse'])
                    ->whereHas('product', function (Builder $query) {
                        $query->whereRaw(
                            'COALESCE((SELECT SUM(pq.quantity) FROM inventories_product_quantities pq
                            JOIN inventories_locations l ON pq.location_id = l.id
                            WHERE pq.product_id = products_products.id
                            AND l.type = ? AND l.is_scrap = 0), 0) <= inventories_order_points.product_min_qty',
                            [LocationType::INTERNAL->value]
                        );
                    })
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('on_hand')
                    ->label('On Hand')
                    ->state(fn (OrderPoint $record): float => $record->product?->on_hand_quantity ?? 0)
                    ->numeric(decimalPlaces: 0)
                    ->color(fn ($state, OrderPoint $record): string =>
                        $state <= 0 ? 'danger' : ($state <= ($record->product_min_qty ?? 0) ? 'warning' : 'success')
                    ),
                TextColumn::make('product_min_qty')
                    ->label('Reorder Point')
                    ->numeric(decimalPlaces: 0),
                TextColumn::make('qty_to_order')
                    ->label('Qty Needed')
                    ->state(function (OrderPoint $record): float {
                        $onHand = $record->product?->on_hand_quantity ?? 0;
                        $maxQty = $record->product_max_qty ?? $record->product_min_qty ?? 0;
                        return max(0, $maxQty - $onHand);
                    })
                    ->numeric(decimalPlaces: 0)
                    ->color('danger'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (OrderPoint $record): string {
                        $onHand = $record->product?->on_hand_quantity ?? 0;
                        return $onHand <= 0 ? 'Out of Stock' : 'Low Stock';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Out of Stock' ? 'danger' : 'warning'),
            ])
            ->defaultSort('on_hand', 'asc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->headerActions([
                Action::make('view_all')
                    ->label('View All Reorder Points')
                    ->url(ReplenishmentResource::getUrl('index'))
                    ->icon('heroicon-o-arrow-right')
                    ->color('gray'),
            ])
            ->emptyStateHeading('All Stock Levels OK')
            ->emptyStateDescription('No products are below their reorder points.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
