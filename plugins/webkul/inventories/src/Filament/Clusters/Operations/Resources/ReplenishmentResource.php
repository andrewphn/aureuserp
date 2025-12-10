<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource\Pages\ManageReplenishment;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Settings\WarehouseSettings;

/**
 * Replenishment Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class ReplenishmentResource extends Resource
{
    protected static ?string $model = OrderPoint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Operations::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/operations/resources/replenishment.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations/resources/replenishment.navigation.group');
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.product'))
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('is_storable', true)->whereNull('deleted_at'),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.warehouse'))
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('location_id', null)),
                Select::make('location_id')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.location'))
                    ->options(function (Get $get) {
                        $warehouseId = $get('warehouse_id');
                        if (!$warehouseId) {
                            return [];
                        }
                        return Location::where('warehouse_id', $warehouseId)
                            ->where('type', LocationType::INTERNAL)
                            ->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->visible(static::getWarehouseSettings()->enable_locations),
                TextInput::make('product_min_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.min-qty'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->helperText('Reorder point - when on-hand falls below this, trigger replenishment'),
                TextInput::make('product_max_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.max-qty'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Target quantity to order up to (optional)'),
                TextInput::make('qty_multiple')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.qty-multiple'))
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->helperText('Order in multiples of this quantity'),
                Radio::make('trigger')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.form.fields.trigger'))
                    ->options(OrderPointTrigger::class)
                    ->default(OrderPointTrigger::MANUAL)
                    ->required()
                    ->inline()
                    ->helperText('Manual: Requires action to order. Automatic: System creates orders.'),
            ])
            ->columns(1);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.product'))
                    ->searchable()
                    ->sortable()
                    ->url(fn (OrderPoint $record): string => route('filament.admin.inventory.resources.products.products.edit', ['record' => $record->product_id])),
                TextColumn::make('warehouse.name')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.warehouse'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('product_min_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.min-qty'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('product_max_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.max-qty'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('on_hand')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.on-hand'))
                    ->state(fn (OrderPoint $record): float => $record->product?->on_hand_quantity ?? 0)
                    ->numeric(decimalPlaces: 2)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            fn ($q) => $q->selectRaw('COALESCE((SELECT SUM(pq.quantity) FROM inventories_product_quantities pq
                                JOIN inventories_locations l ON pq.location_id = l.id
                                WHERE pq.product_id = inventories_order_points.product_id
                                AND l.type = ? AND l.is_scrap = 0), 0)', [LocationType::INTERNAL->value]),
                            $direction
                        );
                    }),
                TextColumn::make('status')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.status'))
                    ->state(function (OrderPoint $record): string {
                        $onHand = $record->product?->on_hand_quantity ?? 0;
                        $minQty = $record->product_min_qty ?? 0;

                        if ($onHand <= 0) {
                            return 'Out of Stock';
                        } elseif ($onHand <= $minQty) {
                            return 'Low Stock';
                        } else {
                            return 'OK';
                        }
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Out of Stock' => 'danger',
                        'Low Stock' => 'warning',
                        'OK' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('qty_to_order')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.qty-to-order'))
                    ->state(function (OrderPoint $record): float {
                        $onHand = $record->product?->on_hand_quantity ?? 0;
                        $maxQty = $record->product_max_qty ?? 0;
                        $minQty = $record->product_min_qty ?? 0;

                        if ($onHand < $minQty && $maxQty > 0) {
                            return max(0, $maxQty - $onHand);
                        }
                        return 0;
                    })
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('trigger')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.trigger'))
                    ->badge()
                    ->color(fn (OrderPointTrigger $state): string => match ($state) {
                        OrderPointTrigger::AUTOMATIC => 'success',
                        OrderPointTrigger::MANUAL => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups(
                collect([
                    Group::make('warehouse.name')
                        ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.groups.warehouse')),
                    Group::make('trigger')
                        ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.groups.trigger')),
                    Group::make('product.category.name')
                        ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.groups.category')),
                ])->filter(function ($group) {
                    return match ($group->getId()) {
                        default => true
                    };
                })->all()
            )
            ->filters([
                SelectFilter::make('status')
                    ->label('Stock Status')
                    ->options([
                        'low' => 'Low Stock / Out of Stock',
                        'ok' => 'OK',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'low' => $query->whereHas('product', function ($q) {
                                $q->whereRaw('COALESCE((SELECT SUM(pq.quantity) FROM inventories_product_quantities pq
                                    JOIN inventories_locations l ON pq.location_id = l.id
                                    WHERE pq.product_id = products_products.id
                                    AND l.type = ? AND l.is_scrap = 0), 0) <= inventories_order_points.product_min_qty', [LocationType::INTERNAL->value]);
                            }),
                            'ok' => $query->whereHas('product', function ($q) {
                                $q->whereRaw('COALESCE((SELECT SUM(pq.quantity) FROM inventories_product_quantities pq
                                    JOIN inventories_locations l ON pq.location_id = l.id
                                    WHERE pq.product_id = products_products.id
                                    AND l.type = ? AND l.is_scrap = 0), 0) > inventories_order_points.product_min_qty', [LocationType::INTERNAL->value]);
                            }),
                            default => $query,
                        };
                    }),
                SelectFilter::make('trigger')
                    ->label('Trigger Type')
                    ->options(OrderPointTrigger::class),
                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->relationship('warehouse', 'name'),
                QueryBuilder::make()
                    ->constraints(collect([
                        RelationshipConstraint::make('product')
                            ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.filters.product'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-shopping-bag'),
                        NumberConstraint::make('product_min_qty')
                            ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.filters.min-qty'))
                            ->icon('heroicon-o-scale'),
                        NumberConstraint::make('product_max_qty')
                            ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.filters.max-qty'))
                            ->icon('heroicon-o-scale'),
                    ])->filter()->values()->all()),
            ], layout: FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->slideOver(),
            )
            ->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make()
                    ->label(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function (array $data): array {
                        $data['creator_id'] = Auth::id();
                        $data['company_id'] = Auth::user()?->defaultCompany?->id;

                        // Set default warehouse location if not provided
                        if (empty($data['warehouse_id'])) {
                            $warehouse = Warehouse::first();
                            $data['warehouse_id'] = $warehouse?->id;
                        }

                        return $data;
                    })
                    ->before(function (CreateAction $action, array $data) {
                        // Check for duplicate product/warehouse combination
                        $exists = OrderPoint::where('product_id', $data['product_id'])
                            ->where('warehouse_id', $data['warehouse_id'])
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Duplicate Reorder Point')
                                ->body('A reorder point already exists for this product and warehouse combination.')
                                ->warning()
                                ->send();
                            $action->halt();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/replenishment.table.header-actions.create.notification.body')),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->defaultSort('product_min_qty', 'desc');
    }

    static public function getWarehouseSettings(): WarehouseSettings
    {
        return once(fn () => app(WarehouseSettings::class));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReplenishment::route('/'),
        ];
    }
}
