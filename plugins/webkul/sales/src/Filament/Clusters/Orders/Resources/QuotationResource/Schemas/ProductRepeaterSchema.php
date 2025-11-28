<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Product\Models\Packaging;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Product;
use Webkul\Sale\Services\Pricing\LineTotalsCalculator;
use Webkul\Sale\Services\Pricing\PackagingOptimizer;
use Webkul\Sale\Services\Pricing\UnitOfMeasureConverter;
use Webkul\Sale\Services\Pricing\VendorPriceCalculator;
use Webkul\Sale\Settings;
use Webkul\Sale\Settings\PriceSettings;
use Webkul\Sale\Settings\ProductSettings;
use Webkul\Support\Filament\Forms\Components\Repeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;
use Webkul\Support\Models\UOM;

/**
 * Product Repeater Schema
 *
 * Extracts the complex product line repeater from QuotationResource.
 * Following FilamentPHP 4 schema organization patterns.
 */
class ProductRepeaterSchema
{
    /**
     * Get the product repeater component
     */
    public static function make(): Repeater
    {
        return Repeater::make('products')
            ->relationship('lines')
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.title'))
            ->addActionLabel(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.add-product'))
            ->collapsible()
            ->collapsed(false)
            ->defaultItems(0)
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->deletable(fn ($record): bool => ! in_array($record?->state, [OrderState::CANCEL]) && $record?->state !== OrderState::SALE)
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
            ->addable(fn ($record): bool => ! in_array($record?->state, [OrderState::CANCEL]))
            ->columnManagerColumns(2)
            ->table(fn ($record) => static::getTableColumns($record))
            ->schema(static::getFormSchema())
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, $record, $livewire) => static::mutateProductRelationship($data, $record))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data, $record, $livewire) => static::mutateProductRelationship($data, $record));
    }

    /**
     * Get table columns for repeater display
     */
    private static function getTableColumns($record): array
    {
        return array_merge(
            [
                TableColumn::make('product_id')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.product'))
                    ->width(250)
                    ->markAsRequired()
                    ->toggleable(),
            ],
            // Pricing configuration columns (visible by default)
            static::getPricingTableColumns(),
            // Physical characteristic columns (hidden by default)
            static::getPhysicalTableColumns(),
            [
                TableColumn::make('product_qty')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.quantity'))
                    ->width(150)
                    ->markAsRequired(),
                TableColumn::make('qty_delivered')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.qty-delivered'))
                    ->width(150)
                    ->toggleable()
                    ->markAsRequired()
                    ->visible(fn () => in_array($record?->state, [OrderState::SALE])),
                TableColumn::make('qty_invoiced')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.qty-invoiced'))
                    ->width(150)
                    ->markAsRequired()
                    ->toggleable()
                    ->visible(fn () => in_array($record?->state, [OrderState::SALE])),
                TableColumn::make('product_uom_id')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.uom'))
                    ->width(150)
                    ->markAsRequired()
                    ->visible(fn () => resolve(ProductSettings::class)->enable_uom),
                TableColumn::make('customer_lead')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.lead-time'))
                    ->width(150)
                    ->markAsRequired()
                    ->toggleable(isToggledHiddenByDefault: true),
                TableColumn::make('product_packaging_qty')
                    ->toggleable()
                    ->width(180)
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.packaging-qty'))
                    ->visible(fn () => resolve(ProductSettings::class)->enable_packagings),
                TableColumn::make('product_packaging_id')
                    ->toggleable()
                    ->width(200)
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.packaging'))
                    ->visible(fn () => resolve(ProductSettings::class)->enable_packagings),
                TableColumn::make('price_unit')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.unit-price'))
                    ->width(150)
                    ->markAsRequired(),
                TableColumn::make('margin')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.margin'))
                    ->width(100)
                    ->toggleable()
                    ->visible(fn () => resolve(PriceSettings::class)->enable_margin),
                TableColumn::make('margin_percent')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.margin-percentage'))
                    ->width(100)
                    ->toggleable()
                    ->visible(fn () => resolve(PriceSettings::class)->enable_margin),
                TableColumn::make('taxes')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.taxes'))
                    ->width(250)
                    ->toggleable(),
                TableColumn::make('discount')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.discount-percentage'))
                    ->width(250)
                    ->toggleable()
                    ->visible(fn () => resolve(Settings\PriceSettings::class)->enable_discount),
                TableColumn::make('price_subtotal')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.columns.amount'))
                    ->width(100)
                    ->toggleable(),
            ]
        );
    }

    /**
     * Get form schema fields
     */
    private static function getFormSchema(): array
    {
        return array_merge(
            [
                Toggle::make('show_all_products')
                    ->label('Show All Product Types')
                    ->default(false)
                    ->live()
                    ->inline()
                    ->helperText('By default, only service products are shown. Toggle to see materials and other product types.'),

                static::getProductSelectField(),
            ],
            // Pricing attribute selectors
            static::getPricingAttributeFields(),
            // Physical attribute selectors
            static::getPhysicalAttributeFields(),
            [
                static::getQuantityField(),
                static::getQtyDeliveredField(),
                static::getQtyInvoicedField(),
                static::getUomField(),
                static::getLeadTimeField(),
                static::getPackagingQtyField(),
                static::getPackagingField(),
                static::getPriceUnitField(),
                static::getMarginField(),
                static::getMarginPercentField(),
                static::getTaxesField(),
                static::getDiscountField(),
                static::getPriceSubtotalField(),
                Hidden::make('product_uom_qty')->default(0),
                Hidden::make('price_tax')->default(0),
                Hidden::make('price_total')->default(0),
                Hidden::make('purchase_price')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.cost'))
                    ->default(0),
                Hidden::make('attribute_selections')
                    ->default('[]')
                    ->dehydrated(),
            ]
        );
    }

    /**
     * Get product select field
     */
    private static function getProductSelectField(): Select
    {
        return Select::make('product_id')
            ->label(fn (ProductSettings $settings) => $settings->enable_variants
                ? __('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.product-variants')
                : __('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.product-simple'))
            ->relationship(
                name: 'product',
                titleAttribute: 'name',
                modifyQueryUsing: function ($query, Settings\ProductSettings $settings, Get $get) {
                    if (!$get('show_all_products')) {
                        $query->where('type', 'service');
                    }

                    if (! $settings?->enable_variants) {
                        return $query->whereNull('parent_id')
                            ->where(function ($q) {
                                $q->where('is_configurable', true)
                                    ->orWhere(function ($subq) {
                                        $subq->whereNull('is_configurable')
                                            ->orWhere('is_configurable', false);
                                    });
                            });
                    }

                    return $query->withTrashed()->where(function ($q) {
                        $q->whereNull('parent_id')
                            ->orWhereNotNull('parent_id');
                    });
                }
            )
            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name.($record->trashed() ? ' (Deleted)' : ''))
            ->disableOptionWhen(function ($label, $record) {
                $isDeleted = str_contains($label, ' (Deleted)');
                $isOrderLocked = $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL]));
                return $isDeleted || $isOrderLocked;
            })
            ->searchable()
            ->preload()
            ->suffixIcon(fn (Get $get) => !$get('show_all_products') ? 'heroicon-o-funnel' : null)
            ->suffixIconColor('primary')
            ->live()
            ->dehydrated(true)
            ->afterStateUpdated(fn (Set $set, Get $get) => static::afterProductUpdated($set, $get))
            ->required()
            ->selectablePlaceholder(false);
    }

    /**
     * Get quantity field
     */
    private static function getQuantityField(): TextInput
    {
        return TextInput::make('product_qty')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.quantity'))
            ->required()
            ->default(1)
            ->numeric()
            ->maxValue(99999999999)
            ->live()
            ->afterStateHydrated(fn (Set $set, Get $get) => static::afterProductQtyUpdated($set, $get))
            ->afterStateUpdated(fn (Set $set, Get $get) => static::afterProductQtyUpdated($set, $get))
            ->readOnly(fn ($record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get qty delivered field
     */
    private static function getQtyDeliveredField(): TextInput
    {
        return TextInput::make('qty_delivered')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.qty-delivered'))
            ->required()
            ->default(1)
            ->numeric()
            ->maxValue(99999999999)
            ->live()
            ->readOnly(fn ($record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])))
            ->visible(fn ($record): bool => in_array($record?->order?->state, [OrderState::SALE]));
    }

    /**
     * Get qty invoiced field
     */
    private static function getQtyInvoicedField(): TextInput
    {
        return TextInput::make('qty_invoiced')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.qty-invoiced'))
            ->required()
            ->default(1)
            ->numeric()
            ->maxValue(99999999999)
            ->live()
            ->readOnly()
            ->visible(fn ($record): bool => in_array($record?->order?->state, [OrderState::SALE]));
    }

    /**
     * Get UOM select field
     */
    private static function getUomField(): Select
    {
        return Select::make('product_uom_id')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.uom'))
            ->relationship('uom', 'name', fn ($query) => $query->where('category_id', 1)->orderBy('id'))
            ->required()
            ->live()
            ->default(UOM::first()?->id)
            ->selectablePlaceholder(false)
            ->afterStateUpdated(fn (Set $set, Get $get) => static::afterUOMUpdated($set, $get))
            ->visible(fn (Settings\ProductSettings $settings) => $settings->enable_uom)
            ->disableOptionWhen(fn ($value, $record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get lead time field
     */
    private static function getLeadTimeField(): TextInput
    {
        return TextInput::make('customer_lead')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.lead-time'))
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->maxValue(99999999999)
            ->required()
            ->readOnly(fn ($record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get packaging qty field
     */
    private static function getPackagingQtyField(): TextInput
    {
        return TextInput::make('product_packaging_qty')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.packaging-qty'))
            ->live()
            ->numeric()
            ->minValue(0)
            ->maxValue(99999999999)
            ->default(0)
            ->afterStateUpdated(fn (Set $set, Get $get) => static::afterProductPackagingQtyUpdated($set, $get))
            ->visible(fn (ProductSettings $settings) => $settings->enable_packagings);
    }

    /**
     * Get packaging select field
     */
    private static function getPackagingField(): Select
    {
        return Select::make('product_packaging_id')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.packaging'))
            ->relationship('productPackaging', 'name')
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(fn (Set $set, Get $get) => static::afterProductPackagingUpdated($set, $get))
            ->visible(fn (Settings\ProductSettings $settings) => $settings->enable_packagings)
            ->disableOptionWhen(fn ($value, $record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get price unit field
     */
    private static function getPriceUnitField(): TextInput
    {
        return TextInput::make('price_unit')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.unit-price'))
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->maxValue(99999999999)
            ->required()
            ->live()
            ->afterStateUpdated(fn (Set $set, Get $get) => static::calculateLineTotals($set, $get))
            ->readOnly(fn ($record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get margin field
     */
    private static function getMarginField(): TextInput
    {
        return TextInput::make('margin')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.margin'))
            ->numeric()
            ->default(0)
            ->maxValue(99999999999)
            ->live()
            ->visible(fn (Settings\PriceSettings $settings) => $settings->enable_margin)
            ->afterStateUpdated(fn (Set $set, Get $get) => static::calculateLineTotals($set, $get))
            ->readOnly();
    }

    /**
     * Get margin percent field
     */
    private static function getMarginPercentField(): TextInput
    {
        return TextInput::make('margin_percent')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.margin-percentage'))
            ->numeric()
            ->default(0)
            ->maxValue(100)
            ->live()
            ->visible(fn (Settings\PriceSettings $settings) => $settings->enable_margin)
            ->afterStateUpdated(fn (Set $set, Get $get) => static::calculateLineTotals($set, $get))
            ->readOnly();
    }

    /**
     * Get taxes field
     */
    private static function getTaxesField(): Select
    {
        return Select::make('taxes')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.taxes'))
            ->relationship('taxes', 'name', fn (Builder $query) => $query->where('type_tax_use', TypeTaxUse::SALE->value))
            ->searchable()
            ->multiple()
            ->preload()
            ->afterStateHydrated(fn (Get $get, Set $set) => static::calculateLineTotals($set, $get))
            ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateLineTotals($set, $get))
            ->live()
            ->disableOptionWhen(fn ($value, $record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get discount field
     */
    private static function getDiscountField(): TextInput
    {
        return TextInput::make('discount')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.discount-percentage'))
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->maxValue(100)
            ->live()
            ->visible(fn (Settings\PriceSettings $settings) => $settings->enable_discount)
            ->afterStateUpdated(fn (Set $set, Get $get) => static::calculateLineTotals($set, $get))
            ->readOnly(fn ($record): bool => $record && ($record->order?->locked || in_array($record?->order?->state, [OrderState::CANCEL])));
    }

    /**
     * Get price subtotal field
     */
    private static function getPriceSubtotalField(): TextInput
    {
        return TextInput::make('price_subtotal')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.products.fields.amount'))
            ->default(0)
            ->readOnly();
    }

    // ==================== ATTRIBUTE METHODS ====================

    /**
     * Get pricing attribute table columns
     */
    private static function getPricingTableColumns(): array
    {
        return collect(AttributeHelper::getPricingAttributes())
            ->map(function ($attributeName, $attributeId) {
                return TableColumn::make("attribute_{$attributeId}")
                    ->label($attributeName)
                    ->width(match($attributeName) {
                        'Pricing Level' => 220,
                        'Material Category' => 280,
                        'Finish Option' => 250,
                        default => 200,
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(false);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get physical attribute table columns
     */
    private static function getPhysicalTableColumns(): array
    {
        return collect(AttributeHelper::getPhysicalAttributes())
            ->map(function ($attributeName, $attributeId) {
                return TableColumn::make("attribute_{$attributeId}")
                    ->label($attributeName)
                    ->width(match($attributeName) {
                        'Construction Style' => 200,
                        'Door Style' => 180,
                        'Primary Material' => 220,
                        'Finish Type' => 180,
                        'Edge Profile' => 180,
                        'Drawer Box Construction' => 240,
                        'Door Overlay Type' => 200,
                        'Box Material' => 180,
                        default => 200,
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(true);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get pricing attribute form fields
     */
    private static function getPricingAttributeFields(): array
    {
        return collect(AttributeHelper::getPricingAttributes())
            ->map(fn ($attributeName, $attributeId) => AttributeHelper::makeAttributeField($attributeId, $attributeName, true))
            ->toArray();
    }

    /**
     * Get physical attribute form fields
     */
    private static function getPhysicalAttributeFields(): array
    {
        return collect(AttributeHelper::getPhysicalAttributes())
            ->map(fn ($attributeName, $attributeId) => AttributeHelper::makeAttributeField($attributeId, $attributeName, false))
            ->toArray();
    }

    // ==================== CALLBACK METHODS ====================

    /**
     * Handle product update
     */
    public static function afterProductUpdated(Set $set, Get $get): void
    {
        if (! $get('product_id')) {
            return;
        }

        $product = Product::withTrashed()->find($get('product_id'));

        $set('product_uom_id', $product->uom_id);

        $uomQuantity = UnitOfMeasureConverter::convert($get('product_uom_id'), $get('product_qty'));

        $set('product_uom_qty', round($uomQuantity, 2));

        if ($product->is_configurable) {
            $set('price_unit', round(floatval($product->price ?? 0), 2));

            $existingSelections = $get('attribute_selections');
            if (empty($existingSelections) || $existingSelections === '[]') {
                $set('attribute_selections', '[]');
                $attributes = \DB::table('products_product_attributes')
                    ->where('product_id', $product->id)
                    ->pluck('attribute_id')
                    ->toArray();
                foreach ($attributes as $attributeId) {
                    $set("attribute_{$attributeId}", null);
                }
            }
        } else {
            $priceUnit = VendorPriceCalculator::calculate(
                productId: (int) $get('product_id'),
                partnerId: $get('../../partner_id') ? (int) $get('../../partner_id') : null,
                quantity: floatval($get('product_qty') ?? 1),
                currencyId: $get('../../currency_id') ? (int) $get('../../currency_id') : null,
                uomId: $get('product_uom_id') ? (int) $get('product_uom_id') : null
            );
            $set('price_unit', round($priceUnit, 2));
        }

        $set('taxes', $product->productTaxes->pluck('id')->toArray());

        $packaging = PackagingOptimizer::findOptimal($get('product_id'), round($uomQuantity, 2));

        $set('product_packaging_id', $packaging['packaging_id'] ?? null);
        $set('product_packaging_qty', $packaging['packaging_qty'] ?? null);
        $set('purchase_price', $product->cost ?? 0);

        static::calculateLineTotals($set, $get);
    }

    /**
     * Handle quantity update
     */
    public static function afterProductQtyUpdated(Set $set, Get $get): void
    {
        if (! $get('product_id')) {
            return;
        }

        $uomQuantity = UnitOfMeasureConverter::convert($get('product_uom_id'), $get('product_qty'));

        $set('product_uom_qty', round($uomQuantity, 2));

        $packaging = PackagingOptimizer::findOptimal($get('product_id'), $uomQuantity);

        $set('product_packaging_id', $packaging['packaging_id'] ?? null);
        $set('product_packaging_qty', $packaging['packaging_qty'] ?? null);

        static::calculateLineTotals($set, $get);
    }

    /**
     * Handle UOM update
     */
    public static function afterUOMUpdated(Set $set, Get $get): void
    {
        if (! $get('product_id')) {
            return;
        }

        $uomQuantity = UnitOfMeasureConverter::convert($get('product_uom_id'), $get('product_qty'));

        $set('product_uom_qty', round($uomQuantity, 2));

        $packaging = PackagingOptimizer::findOptimal($get('product_id'), $uomQuantity);

        $set('product_packaging_id', $packaging['packaging_id'] ?? null);
        $set('product_packaging_qty', $packaging['packaging_qty'] ?? null);

        $priceUnit = VendorPriceCalculator::calculate(
            productId: (int) $get('product_id'),
            partnerId: $get('../../partner_id') ? (int) $get('../../partner_id') : null,
            quantity: floatval($get('product_qty') ?? 1),
            currencyId: $get('../../currency_id') ? (int) $get('../../currency_id') : null,
            uomId: $get('product_uom_id') ? (int) $get('product_uom_id') : null
        );

        $set('price_unit', round($priceUnit, 2));

        static::calculateLineTotals($set, $get);
    }

    /**
     * Handle packaging qty update
     */
    public static function afterProductPackagingQtyUpdated(Set $set, Get $get): void
    {
        if (! $get('product_id')) {
            return;
        }

        if ($get('product_packaging_id')) {
            $packaging = Packaging::find($get('product_packaging_id'));

            $packagingQty = floatval($get('product_packaging_qty') ?? 0);
            $productUOMQty = $packagingQty * $packaging->qty;

            $set('product_uom_qty', round($productUOMQty, 2));

            $uom = Uom::find($get('product_uom_id'));
            $productQty = $uom ? $productUOMQty * $uom->factor : $productUOMQty;

            $set('product_qty', round($productQty, 2));
        }

        static::calculateLineTotals($set, $get);
    }

    /**
     * Handle packaging update
     */
    public static function afterProductPackagingUpdated(Set $set, Get $get): void
    {
        if (! $get('product_id')) {
            return;
        }

        if ($get('product_packaging_id')) {
            $packaging = Packaging::find($get('product_packaging_id'));

            $productUOMQty = $get('product_uom_qty') ?: 1;

            if ($packaging) {
                $packagingQty = $productUOMQty / $packaging->qty;
                $set('product_packaging_qty', $packagingQty);
            }
        } else {
            $set('product_packaging_qty', null);
        }

        static::calculateLineTotals($set, $get);
    }

    /**
     * Calculate line totals
     */
    public static function calculateLineTotals(Set $set, Get $get, ?string $prefix = ''): void
    {
        if (! $get($prefix.'product_id')) {
            $emptyTotals = LineTotalsCalculator::getEmptyTotals();
            foreach ($emptyTotals as $key => $value) {
                $set($prefix.$key, $value);
            }
            return;
        }

        $totals = LineTotalsCalculator::calculate(
            priceUnit: floatval($get($prefix.'price_unit') ?? 0),
            quantity: floatval($get($prefix.'product_qty') ?? 1),
            purchasePrice: floatval($get($prefix.'purchase_price') ?? 0),
            discount: floatval($get($prefix.'discount') ?? 0),
            taxIds: $get($prefix.'taxes') ?? []
        );

        foreach ($totals as $key => $value) {
            $set($prefix.$key, $value);
        }
    }

    /**
     * Mutate product relationship data
     */
    public static function mutateProductRelationship(array $data, $record): array
    {
        $product = Product::withTrashed()->find($data['product_id']);

        $qtyDeliveredMethod = \Webkul\Sale\Enums\QtyDeliveredMethod::MANUAL;

        if (\Webkul\Support\Package::isPluginInstalled('inventories')) {
            $qtyDeliveredMethod = \Webkul\Sale\Enums\QtyDeliveredMethod::STOCK_MOVE;
        }

        return [
            'name'                 => $product->name,
            'qty_delivered_method' => $qtyDeliveredMethod,
            'product_uom_id'       => $data['product_uom_id'] ?? $product->uom_id,
            'currency_id'          => $record->currency_id,
            'partner_id'           => $record->partner_id,
            'creator_id'           => \Illuminate\Support\Facades\Auth::id(),
            'company_id'           => \Illuminate\Support\Facades\Auth::user()->default_company_id,
            ...$data,
        ];
    }
}
