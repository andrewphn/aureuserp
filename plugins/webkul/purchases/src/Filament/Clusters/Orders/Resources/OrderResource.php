<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Account\Models\Tax;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Filament\Resources\IncoTermResource;
use Filament\Support\Facades\FilamentView;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Illuminate\Database\Eloquent\Model;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Purchase\Enums;
use Webkul\Purchase\Models\Order;
use Webkul\Support\Models\Currency;
use Webkul\Purchase\Livewire\Summary;
use Webkul\Purchase\Settings;
use Webkul\Purchase\Models\Product;
use Webkul\Purchase\Filament\Clusters\Products\Resources\ProductResource;

class OrderResource extends Resource
{
    use HasCustomFields;

    protected static ?string $model = Order::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(Enums\OrderState::options())
                    ->default(Enums\OrderState::DRAFT)
                    ->disabled(),
                Forms\Components\Section::make(__('purchases::filament/clusters/orders/resources/order.form.sections.general.title'))
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('partner_id')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.vendor'))
                                    ->relationship(
                                        'partner',
                                        'name',
                                        fn ($query) => $query->where('sub_type', 'supplier')
                                    )
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->createOptionForm(fn (Form $form) => VendorResource::form($form))
                                    ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT])),
                                Forms\Components\TextInput::make('partner_reference')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.vendor-reference'))
                                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.vendor-reference-tooltip')),
                                Forms\Components\Select::make('requisition_id')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.agreement'))
                                    ->relationship('requisition', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('currency_id')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.currency'))
                                    ->relationship('currency', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT])),
                            ]),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\DateTimePicker::make('approved_at')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.confirmation-date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->default(now())
                                    ->disabled()
                                    ->visible(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT])),
                                Forms\Components\DateTimePicker::make('ordered_at')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.order-deadline'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->default(now())
                                    ->hidden(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT])),
                                Forms\Components\DateTimePicker::make('planned_at')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.sections.general.fields.expected-arrival'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT, Enums\OrderState::PURCHASE])),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Tabs::make()
                    ->schema([
                        Forms\Components\Tabs\Tab::make(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.title'))
                            ->schema([
                                static::getProductRepeater(),
                                Forms\Components\Livewire::make(Summary::class, function (Forms\Get $get) {
                                    return [
                                        'products' => $get('products'),
                                    ];
                                })
                                    ->live()
                                    ->reactive(),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.title'))
                            ->schema(static::mergeCustomFormFields([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.buyer'))
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(Auth::id())
                                            ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT, Enums\OrderState::PURCHASE])),
                                        Forms\Components\Select::make('company_id')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.company'))
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(Auth::user()->default_company_id)
                                            ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT])),
                                        Forms\Components\TextInput::make('reference')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.source-document')),
                                        Forms\Components\Select::make('incoterm_id')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.incoterm'))
                                            ->relationship('incoterm', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm(fn (Form $form) => IncoTermResource::form($form))
                                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.incoterm-tooltip'))
                                            ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT, Enums\OrderState::PURCHASE])),
                                        Forms\Components\TextInput::make('reference')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.incoterm-location'))
                                            ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT, Enums\OrderState::PURCHASE])),
                                    ]),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('payment_term_id')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.incoterm'))
                                            ->relationship('paymentTerm', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT, Enums\OrderState::PURCHASE])),
                                        Forms\Components\Select::make('fiscal_position_id')
                                            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.additional.fields.fiscal-position'))
                                            ->relationship('fiscalPosition', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn ($record): bool => $record && ! in_array($record?->state, [Enums\OrderState::DRAFT, Enums\OrderState::SENT, Enums\OrderState::PURCHASE])),
                                    ]),
                            ]))
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make(__('purchases::filament/clusters/orders/resources/order.form.tabs.terms.title'))
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->hiddenLabel(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function getProductRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('products')
            ->relationship('lines')
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.title'))
            ->addActionLabel(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.add-product-line'))
            ->collapsible()
            ->defaultItems(0)
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation())
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Hidden::make('currency_id')
                                    ->default(Currency::first()->id),
                                Forms\Components\Select::make('product_id')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.product'))
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateHydrated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            self::updateProductCalculations($state, $set, $get);
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            self::updateProductCalculations($state, $set, $get);
                                        }
                                    })
                                    ->required(),
                                Forms\Components\Hidden::make('name')
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('product_qty')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.quantity'))
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($get('product_id')) {
                                            self::updateLineCalculations($set, $get);
                                        }
                                    }),
                                Forms\Components\Select::make('uom_id')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.unit'))
                                    ->relationship(
                                        'uom',
                                        'name',
                                        fn ($query) => $query->where('category_id', 1),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->visible(fn (Settings\ProductSettings $settings) => $settings->enable_uom),
                                Forms\Components\Select::make('taxes')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.taxes'))
                                    ->relationship(
                                        'taxes',
                                        'name',
                                        fn ($query) => $query->where('type_tax_use', TypeTaxUse::PURCHASE->value),
                                    )
                                    ->searchable()
                                    ->multiple()
                                    ->preload()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        if ($get('product_id')) {
                                            $product = Product::find($get('product_id'));
                                            $product->productTaxes()->sync($state);
                                            self::updateLineCalculations($set, $get);
                                        }
                                    })
                                    ->live(),
                                Forms\Components\TextInput::make('discount')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.discount-percentage'))
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($get('product_id')) {
                                            self::updateLineCalculations($set, $get);
                                        }
                                    }),
                                Forms\Components\TextInput::make('price_unit')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.unit-price'))
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($get('product_id')) {
                                            self::updateLineCalculations($set, $get);
                                        }
                                    }),
                                Forms\Components\TextInput::make('price_subtotal')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.sub-total'))
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly(),
                                Forms\Components\TextInput::make('price_total')
                                    ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.fields.total'))
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly(),
                            ]),
                    ])->columns(2),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record) {
                $product = Product::find($data['product_id']);

                $data = array_merge($data, [
                    'name'                    => $product->name,
                    'state'                   => $record->state->value,
                    'product_uom_qty'         => $data['product_qty'],
                    'product_packaging_qty'   => $data['product_qty'],
                    'qty_received_method'     => 'manual',
                    'uom_id'                  => $data['uom_id'] ?? $product->uom_id,
                    'currency_id'             => $record->currency_id,
                    'partner_id'              => $record->partner_id,
                    'creator_id'              => Auth::id(),
                    'company_id'              => Auth::user()->default_company_id,
                ]);

                return $data;
            });
    }

    private static function updateProductCalculations($productId, Forms\Set $set, Forms\Get $get): void
    {
        $product = Product::find($productId);

        $quantity = floatval($get('quantity') ?? 1);

        $priceUnit = floatval($product->price);

        $set('name', $product->name);

        $set('price_unit', $priceUnit);

        $set('tax', $product->productTaxes->pluck('id')->toArray());

        self::calculateTotals($quantity, $priceUnit, floatval($get('discount')), $product->productTaxes->pluck('id')->toArray(), $set);
    }

    private static function updateLineCalculations(Forms\Set $set, Forms\Get $get): void
    {
        $quantity = floatval($get('quantity') ?? 1);

        $priceUnit = floatval($get('price_unit') ?? 0);

        $discount = floatval($get('discount') ?? 0);

        $taxIds = $get('tax') ?? [];

        self::calculateTotals($quantity, $priceUnit, $discount, $taxIds, $set);
    }

    private static function calculateTotals(float $quantity, float $priceUnit, float $discount, array $taxIds, Forms\Set $set): void
    {
        $baseAmount = $quantity * $priceUnit;

        $discountAmount = $baseAmount * ($discount / 100);

        $subtotalBeforeTax = $baseAmount - $discountAmount;

        $taxAmount = 0;

        $includedTaxAmount = 0;

        if (! empty($taxIds)) {
            $taxes = Tax::whereIn('id', $taxIds)->get();

            foreach ($taxes as $tax) {
                $taxValue = floatval($tax->amount);

                if ($tax->include_base_amount) {
                    $includedTaxRate = $taxValue / 100;
                    $includedTaxAmount += $subtotalBeforeTax - ($subtotalBeforeTax / (1 + $includedTaxRate));
                }
            }

            $subtotalExcludingIncludedTax = $subtotalBeforeTax - $includedTaxAmount;

            foreach ($taxes as $tax) {
                $taxValue = floatval($tax->amount);

                if (! $tax->include_base_amount) {
                    $taxAmount += $subtotalExcludingIncludedTax * ($taxValue / 100);
                }
            }
        }

        $set('price_subtotal', number_format($subtotalBeforeTax, 2, '.', ''));

        $set('price_total', number_format($subtotalBeforeTax + $taxAmount, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
