<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;
use Webkul\Sale\Models\Order;
use Filament\Resources\Resource;
use Webkul\Sale\Livewire\Summary;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\OrderState;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Tax;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderDisplayType;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Sale\Models\SaleOrderLine;
use Webkul\Sale\Models\OrderTemplate;
use Webkul\Sale\Models\Product;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

class QuotationResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Orders::class;

    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/quotation.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/quotation.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Hidden::make('currency_id')
                            ->default(Currency::first()->id),
                        ProgressStepper::make('state')
                            ->hiddenLabel()
                            ->inline()
                            ->options(OrderState::class)
                            ->default(OrderState::DRAFT->value)
                            ->columnSpan('full')
                            ->disabled()
                            ->live()
                            ->reactive(),
                    ])->columns(2),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Tabs::make()
                                    ->tabs([
                                        Forms\Components\Tabs\Tab::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.title'))
                                            ->schema([
                                                static::getProductRepeater(),
                                                static::getSectionRepeater(),
                                                static::getNoteRepeater(),
                                                Forms\Components\Livewire::make(Summary::class, function (Get $get) {
                                                    return [
                                                        'products' => $get('products'),
                                                    ];
                                                })
                                                    ->live()
                                                    ->reactive()
                                            ]),
                                        Forms\Components\Tabs\Tab::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.title'))
                                            ->schema([
                                                Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.title'))
                                                    ->schema([
                                                        Forms\Components\Grid::make(2)
                                                            ->schema([
                                                                Forms\Components\Select::make('user_id')
                                                                    ->relationship('user', 'name')
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.sales-person')),
                                                                Forms\Components\Select::make('team_id')
                                                                    ->relationship('team', 'name')
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.sales-team')),
                                                                Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.fieldset.signature-and-payment.title'))
                                                                    ->schema([
                                                                        Forms\Components\Toggle::make('require_signature')
                                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.fieldset.signature-and-payment.fields.online-signature')),
                                                                        Forms\Components\Toggle::make('require_payment')
                                                                            ->live()
                                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.fieldset.signature-and-payment.fields.online-payment')),
                                                                        Forms\Components\TextInput::make('prepayment_percentage')
                                                                            ->prefix('of')
                                                                            ->suffix('%')
                                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.fieldset.signature-and-payment.fields.prepayment-percentage'))
                                                                            ->visible(fn(Get $get) => $get('require_payment') === true),
                                                                    ])->columns(1),
                                                                Forms\Components\TextInput::make('client_order_ref')
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.customer-reference')),
                                                            ])
                                                    ]),
                                                Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.invoicing.title'))
                                                    ->schema([
                                                        Forms\Components\Select::make('fiscal_position_id')
                                                            ->relationship('fiscalPosition', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.invoicing.fields.fiscal-position')),
                                                        Forms\Components\Select::make('journal_id')
                                                            ->relationship('journal', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.invoicing.fields.invoicing-journal'))
                                                    ]),
                                                Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.shipping.title'))
                                                    ->schema([
                                                        Forms\Components\DateTimePicker::make('commitment_date')
                                                            ->native(false)
                                                            ->suffixIcon('heroicon-o-calendar')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.shipping.fields.commitment-date')),
                                                    ]),
                                                Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.title'))
                                                    ->schema([
                                                        Forms\Components\TextInput::make('origin')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.source-document')),
                                                        Forms\Components\Select::make('medium_id')
                                                            ->relationship('medium', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.medium')),
                                                        Forms\Components\Select::make('source_id')
                                                            ->relationship('utmSource', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.source')),
                                                    ]),
                                            ]),
                                        Forms\Components\Tabs\Tab::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.term-and-conditions.title'))
                                            ->schema([
                                                Forms\Components\RichEditor::make('note')
                                                    ->hiddenLabel()
                                            ]),
                                    ])
                                    ->persistTabInQueryString(),
                            ])
                            ->columnSpan(['lg' => 2]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('partner_id')
                                            ->relationship('partner', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                if ($state) {
                                                    if ($get('partner_invoice_id') === null) {
                                                        $set('partner_invoice_id', $state);
                                                    }

                                                    if ($get('partner_shipping_id') === null) {
                                                        $set('partner_shipping_id', $state);
                                                    }
                                                }
                                            })
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.fields.customer')),
                                        Forms\Components\Placeholder::make('partner_address')
                                            ->hiddenLabel()
                                            ->visible(
                                                fn(Get $get) =>
                                                Partner::with('addresses')->find($get('partner_id'))?->addresses->isNotEmpty()
                                            )
                                            ->content(function (Get $get) {
                                                $partner = Partner::with('addresses.state', 'addresses.country')->find($get('partner_id'));

                                                if (
                                                    ! $partner
                                                    || $partner->addresses->isEmpty()
                                                ) {
                                                    return null;
                                                }

                                                $address = $partner->addresses->first();

                                                return sprintf(
                                                    "%s\n%s%s\n%s, %s %s\n%s",
                                                    $address->name ?? '',
                                                    $address->street1 ?? '',
                                                    $address->street2 ? ', ' . $address->street2 : '',
                                                    $address->city ?? '',
                                                    $address->state ? $address->state->name : '',
                                                    $address->zip ?? '',
                                                    $address->country ? $address->country->name : ''
                                                );
                                            }),
                                        Forms\Components\Select::make('payment_term_id')
                                            ->relationship('paymentTerm', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.fields.payment-terms')),
                                        Forms\Components\Select::make('sale_order_template_id')
                                            ->relationship('quotationTemplate', 'name')
                                            ->searchable()
                                            ->live()
                                            ->preload()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                $orderTemplate = OrderTemplate::find($state);

                                                if ($orderTemplate) {
                                                    $initialProducts = collect($orderTemplate->products)
                                                        ->map(function ($item) {
                                                            $qty = $item->quantity ?? 0;
                                                            $price = $item->product?->price ?? 0;

                                                            return [
                                                                'product_id' => $item->product_id ?? null,
                                                                'name' => $item->name,
                                                                'product_uom_qty' => $qty,
                                                                'tax' => $item->product?->productTaxes->pluck('id')->toArray() ?? [],
                                                                'customer_lead' => 1,
                                                                'price_unit' => $price,
                                                                'price_subtotal' => number_format($price * $qty, 2, '.', ''),
                                                                'price_total' => number_format($price * $qty, 2, '.', '')
                                                            ];
                                                        })
                                                        ->toArray();

                                                    $set('products', $initialProducts);

                                                    $initialSections = collect($orderTemplate->sections)
                                                        ->map(fn($item) => [
                                                            'product_id' => $item->product_id ?? null,
                                                            'name'       => $item->name,
                                                            'quantity'   => $item->quantity ?? null,
                                                        ])
                                                        ->toArray();

                                                    $set('sections', $initialSections);

                                                    $initialNotes = collect($orderTemplate->notes)
                                                        ->map(fn($item) => [
                                                            'product_id' => $item->product_id ?? null,
                                                            'name'       => $item->name,
                                                            'quantity'   => $item->quantity ?? null,
                                                        ])
                                                        ->toArray();

                                                    $set('notes', $initialNotes);
                                                }
                                            })
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.fields.quotation-template'))
                                    ]),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.fieldset.invoice-and-delivery-addresses.title'))
                                            ->schema([
                                                Forms\Components\Select::make('partner_invoice_id')
                                                    ->relationship('partnerInvoice', 'name')
                                                    ->searchable()
                                                    ->required()
                                                    ->preload()
                                                    ->live()
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.fieldset.invoice-and-delivery-addresses.fields.invoice-address')),
                                                Forms\Components\Select::make('partner_shipping_id')
                                                    ->relationship('partnerShipping', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.fieldset.invoice-and-delivery-addresses.fields.delivery-address')),
                                            ])->columns(1)
                                    ]),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.fieldset.expiration-and-quotation-date.title'))
                                            ->schema([
                                                Forms\Components\DatePicker::make('validity_date')
                                                    ->live()
                                                    ->native(false)
                                                    ->suffixIcon('heroicon-o-calendar')
                                                    ->default(now()->addDays(30)->format('Y-m-d'))
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.fieldset.expiration-and-quotation-date.fields.expiration-date')),
                                                Forms\Components\DatePicker::make('date_order')
                                                    ->live()
                                                    ->native(false)
                                                    ->suffixIcon('heroicon-o-calendar')
                                                    ->default(now())
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.fieldset.expiration-and-quotation-date.fields.quotation-date')),
                                            ])->columns(1),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columns(3),
            ])
            ->columns('full');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.number'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.status'))
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => OrderState::options()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        OrderState::DRAFT->value => 'gray',
                        OrderState::SENT->value => 'primary',
                        OrderState::SALE->value => 'success',
                        OrderState::CANCEL->value => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_status')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.invoice-status'))
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => InvoiceStatus::options()[$state] ?? $state)
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.creation-date'))
                    ->placeholder('-')
                    ->searchable()
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commitment_date')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.commitment-date'))
                    ->placeholder('-')
                    ->searchable()
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expected_date')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.expected-date'))
                    ->placeholder('-')
                    ->searchable()
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.customer'))
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.sales-person'))
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('team.name')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.sales-team'))
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_untaxed')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.untaxed-amount'))
                    ->placeholder('-')
                    ->searchable()
                    ->summarize(Sum::make()->label('Total'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_tax')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.amount-tax'))
                    ->placeholder('-')
                    ->searchable()
                    ->summarize(Sum::make()->label('Taxes'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_total')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.amount-total'))
                    ->placeholder('-')
                    ->searchable()
                    ->summarize(Sum::make()->label('Total Amount'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_order_ref')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.columns.customer-reference'))
                    ->placeholder('-')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filtersFormColumns(2)
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraintPickerColumns(2)
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('user.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('utm_source_id.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('company.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('partner.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('journal.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('partnerInvoice.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('partnerShipping.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('fiscalPosition.name')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.fiscal-position'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.fiscal-position'))
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                                OrderState::DRAFT->value
                            ),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('paymentTerm.name')
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
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('currency.name')
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
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.created-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('updated_at')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.table.filters.updated-at')),
                    ]),
            ])
            ->groups([
                Tables\Grouping\Group::make('medium.name')
                    ->label(__('Medium'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.medium'))
                    ->collapsible(),
                Tables\Grouping\Group::make('utmSource.name')
                    ->label(__('Source'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.source'))
                    ->collapsible(),
                Tables\Grouping\Group::make('team.name')
                    ->label(__('Team'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.team'))
                    ->collapsible(),
                Tables\Grouping\Group::make('user.name')
                    ->label(__('Sales Person'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.sales-person'))
                    ->collapsible(),
                Tables\Grouping\Group::make('currency.full_name')
                    ->label(__('Currency'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.currency'))
                    ->collapsible(),
                Tables\Grouping\Group::make('company.name')
                    ->label(__('Company'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.company'))
                    ->collapsible(),
                Tables\Grouping\Group::make('partner.name')
                    ->label(__('Customer'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.customer'))
                    ->collapsible(),
                Tables\Grouping\Group::make('date_order')
                    ->label(__('Quotation Date'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.quotation-date'))
                    ->date()
                    ->collapsible(),
                Tables\Grouping\Group::make('commitment_date')
                    ->label(__('Commitment Date'))
                    ->label(__('sales::filament/clusters/orders/resources/quotation.table.groups.commitment-date'))
                    ->date()
                    ->collapsible(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.table.actions.delete.notification.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.table.actions.delete.notification.body'))
                        ),
                    Tables\Actions\ForceDeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.table.actions.force-delete.notification.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.table.actions.force-delete.notification.body'))
                        ),
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.table.actions.restore.notification.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.table.actions.restore.notification.body'))
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.restore.notification.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.restore.notification.body'))
                        ),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.force-delete.notification.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.force-delete.notification.body'))
                        ),
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.restore.notification.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.table.bulk-actions.restore.notification.body'))
                        ),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(['default' => 3])
                    ->schema([
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Tabs::make('Tabs')
                                    ->tabs([
                                        Infolists\Components\Tabs\Tab::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.title'))
                                            ->schema([
                                                Infolists\Components\RepeatableEntry::make('salesOrderLines')
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.product'))
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('product.name')
                                                            ->icon('heroicon-o-shopping-bag')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.product')),
                                                        Infolists\Components\TextEntry::make('name')
                                                            ->icon('heroicon-o-document')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.name')),
                                                        Infolists\Components\RepeatableEntry::make('product.productTaxes')
                                                            ->contained(false)
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                Infolists\Components\TextEntry::make('name')
                                                                    ->badge()
                                                                    ->tooltip(fn($state) => $state)
                                                                    ->icon('heroicon-o-receipt-percent')
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.taxes')),
                                                            ])
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.tax')),
                                                        Infolists\Components\TextEntry::make('product_uom_qty')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.quantity'))
                                                            ->numeric(),
                                                        Infolists\Components\TextEntry::make('price_unit')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.unit-price'))
                                                            ->icon('heroicon-o-banknotes')
                                                            ->money('USD'),
                                                        Infolists\Components\TextEntry::make('price_subtotal')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.subtotal'))
                                                            ->icon('heroicon-o-banknotes')
                                                            ->money('USD'),
                                                        Infolists\Components\TextEntry::make('price_total')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.total'))
                                                            ->icon('heroicon-o-banknotes')
                                                            ->money('USD'),
                                                    ])
                                                    ->columns(6),
                                                Infolists\Components\RepeatableEntry::make('salesOrderSectionLines')
                                                    ->hidden(fn($record) => $record->salesOrderSectionLines->isEmpty())
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('product.name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.product')),
                                                        Infolists\Components\TextEntry::make('name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.name')),
                                                        Infolists\Components\TextEntry::make('quantity')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.quantity'))
                                                            ->numeric(),
                                                    ])
                                                    ->columns(3),
                                                Infolists\Components\RepeatableEntry::make('salesOrderNoteLines')
                                                    ->hidden(fn($record) => $record->salesOrderNoteLines->isEmpty())
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('product.name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.product')),
                                                        Infolists\Components\TextEntry::make('name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.name')),
                                                        Infolists\Components\TextEntry::make('quantity')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.products.fields.quantity'))
                                                            ->numeric(),
                                                    ])
                                                    ->columns(3),
                                                Infolists\Components\Livewire::make(Summary::class, function ($record) {
                                                    return [
                                                        'products' => $record->salesOrderLines->map(function ($item) {
                                                            return [
                                                                ...$item->toArray(),
                                                                'tax' => $item?->product?->productTaxes->pluck('id')->toArray() ?? [],
                                                            ];
                                                        })->toArray(),
                                                    ];
                                                }),
                                            ]),
                                        Infolists\Components\Tabs\Tab::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.title'))
                                            ->schema([
                                                Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.title'))
                                                    ->schema([
                                                        Infolists\Components\Grid::make()
                                                            ->schema([
                                                                Infolists\Components\TextEntry::make('user.name')
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fields.sales-person'))
                                                                    ->placeholder('—')
                                                                    ->icon('heroicon-o-user'),
                                                                Infolists\Components\TextEntry::make('team.name')
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fields.sales-team'))
                                                                    ->placeholder('—')
                                                                    ->icon('heroicon-o-users'),
                                                                Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fieldset.signature-and-payment.title'))
                                                                    ->schema([
                                                                        Infolists\Components\IconEntry::make('require_signature')
                                                                            ->boolean()
                                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fieldset.signature-and-payment.fields.online-signature'))
                                                                            ->placeholder('—'),
                                                                        Infolists\Components\IconEntry::make('require_payment')
                                                                            ->boolean()
                                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fieldset.signature-and-payment.fields.online-payment'))
                                                                            ->placeholder('—'),
                                                                        Infolists\Components\IconEntry::make('prepayment_percentage')
                                                                            ->boolean()
                                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fieldset.signature-and-payment.fields.prepayment-percentage'))
                                                                            ->placeholder('—'),
                                                                    ]),
                                                                Infolists\Components\TextEntry::make('client_order_ref')
                                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.sales.fields.customer-reference'))
                                                                    ->placeholder('—')
                                                                    ->icon('heroicon-o-document'),
                                                            ])->columns(2),
                                                    ]),
                                                Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.invoicing.title'))
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('fiscalPosition.name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.invoicing.fields.fiscal-position'))
                                                            ->placeholder('—')
                                                            ->icon('heroicon-o-receipt-percent'),
                                                        Infolists\Components\TextEntry::make('journal.name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.invoicing.fields.invoicing-journal'))
                                                            ->placeholder('—')
                                                            ->icon('heroicon-o-book-open'),
                                                    ]),
                                                Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.shipping.title'))
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('commitment_date')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.shipping.fields.commitment-date'))
                                                            ->placeholder('—')
                                                            ->icon('heroicon-o-calendar'),
                                                    ]),
                                                Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.tracking.title'))
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('origin')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.tracking.fields.source-document'))
                                                            ->placeholder('—')
                                                            ->icon('heroicon-o-globe-alt'),
                                                        Infolists\Components\TextEntry::make('medium.name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.tracking.fields.medium'))
                                                            ->placeholder('—'),
                                                        Infolists\Components\TextEntry::make('source.name')
                                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.other-information.fieldset.tracking.fields.source'))
                                                            ->placeholder('—'),
                                                    ]),
                                            ]),
                                        Infolists\Components\Tabs\Tab::make(__('sales::filament/clusters/orders/resources/quotation.infolist.tabs.term-and-conditions.title'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('note')
                                                    ->markdown()
                                                    ->columnSpanFull()
                                                    ->icon('heroicon-o-information-circle'),
                                            ]),
                                    ])->persistTabInQueryString(),
                            ])->columnSpan(2),
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fields.customer'))
                                            ->placeholder('—')
                                            ->size(TextEntrySize::Large)
                                            ->icon('heroicon-o-identification'),
                                        Infolists\Components\TextEntry::make('partner.name')
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fields.customer'))
                                            ->placeholder('—')
                                            ->icon('heroicon-o-user-circle'),
                                        Infolists\Components\TextEntry::make('partner_address')
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fields.partner-address'))
                                            ->placeholder('—')
                                            ->icon('heroicon-o-map'),
                                        Infolists\Components\TextEntry::make('paymentTerm.name')
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fields.payment-terms'))
                                            ->placeholder('—')
                                            ->icon('heroicon-o-credit-card'),
                                        Infolists\Components\TextEntry::make('quotationTemplate.name')
                                            ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fields.quotation-template'))
                                            ->placeholder('—')
                                            ->icon('heroicon-o-document-duplicate'),
                                    ]),
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.fieldset.invoice-and-delivery-addresses.title'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('partnerInvoice.name')
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fieldset.invoice-and-delivery-addresses.fields.invoice-address'))
                                                    ->placeholder('—')
                                                    ->icon('heroicon-o-home'),
                                                Infolists\Components\TextEntry::make('partnerShipping.name')
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fieldset.invoice-and-delivery-addresses.fields.delivery-address'))
                                                    ->placeholder('—')
                                                    ->icon('heroicon-o-truck'),
                                            ]),
                                    ]),
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.infolist.fieldset.expiration-and-quotation-date.title'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('validity_date')
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fieldset.expiration-and-quotation-date.fields.expiration-date'))
                                                    ->date()
                                                    ->placeholder('—')
                                                    ->icon('heroicon-o-clock'),
                                                Infolists\Components\TextEntry::make('date_order')
                                                    ->label(__('sales::filament/clusters/orders/resources/quotation.infolist.fieldset.expiration-and-quotation-date.fields.quotation-date'))
                                                    ->date()
                                                    ->placeholder('—')
                                                    ->icon('heroicon-o-calendar'),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    public static function getProductRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('products')
            ->relationship('salesOrderLines')
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->reorderable()
            ->collapsible()
            ->defaultItems(0)
            ->cloneable()
            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
            ->deleteAction(
                fn(Action $action) => $action->requiresConfirmation(),
            )
            ->extraItemActions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->action(function (array $arguments, $livewire, $state): void {
                        $redirectUrl = ProductResource::getUrl('edit', ['record' => $state[$arguments['item']]['product_id']]);

                        $livewire->redirect($redirectUrl, navigate: FilamentView::hasSpaMode());
                    }),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(function ($data) {
                $data['sort'] = SaleOrderLine::max('sort') + 1;
                $data['company_id'] = $data['company_id'] ?? Company::first()->id;
                $data['product_uom_id'] = $data['product_uom_id'] ?? UOM::first()->id;
                $data['creator_id'] = $data['creator_id'] ?? User::first()->id;
                $data['customer_lead'] = $data['customer_lead'] ?? 0;

                return $data;
            })
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.product'))
                                    ->placeholder('-')
                                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $quantity = floatval($get('product_uom_qty') ?? 1);
                                            $priceUnit = floatval($product->price);

                                            $set('name', $product->name);
                                            $set('price_unit', $priceUnit);

                                            $subtotal = $quantity * $priceUnit;
                                            $set('price_subtotal', number_format($subtotal, 2, '.', ''));
                                            $set('price_total', number_format($subtotal, 2, '.', ''));

                                            $set('tax', $product->productTaxes->pluck('id')->toArray());
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $quantity = floatval($get('product_uom_qty') ?? 1);
                                            $priceUnit = floatval($product->price);

                                            $set('name', $product->name);
                                            $set('price_unit', $priceUnit);

                                            $subtotal = $quantity * $priceUnit;
                                            $set('price_subtotal', number_format($subtotal, 2, '.', ''));
                                            $set('price_total', number_format($subtotal, 2, '.', ''));

                                            $set('tax', $product->productTaxes->pluck('id')->toArray());
                                        }
                                    })
                                    ->required(),
                                Forms\Components\Hidden::make('name')
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('product_uom_qty')
                                    ->placeholder('-')
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($get('product_id')) {
                                            $product = Product::find($get('product_id'));
                                            $quantity = floatval($state);
                                            $priceUnit = floatval($get('price_unit') ?? $product->price);

                                            $subtotal = $quantity * $priceUnit;
                                            $set('price_subtotal', number_format($subtotal, 2, '.', ''));
                                            $set('price_total', number_format($subtotal, 2, '.', ''));
                                        }
                                    })
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.quantity')),
                                Forms\Components\Select::make('tax')
                                    ->options(Tax::where('type_tax_use', TypeTaxUse::SALE->value)->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->placeholder('-')
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.taxes'))
                                    ->multiple()
                                    ->preload()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($get('product_id')) {
                                            $product = Product::find($get('product_id'));

                                            $product->productTaxes()->sync($state);
                                        }
                                    })
                                    ->live(),
                                Forms\Components\TextInput::make('customer_lead')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->placeholder('-')
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.lead-time')),
                                Forms\Components\TextInput::make('price_unit')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($get('product_id')) {
                                            $quantity = floatval($get('product_uom_qty') ?? 1);
                                            $priceUnit = floatval($state);

                                            $subtotal = $quantity * $priceUnit;

                                            $taxIds = $get('tax') ?? [];
                                            $taxAmount = 0;

                                            if (!empty($taxIds)) {
                                                $taxes = \Webkul\Account\Models\Tax::whereIn('id', $taxIds)->get();
                                                foreach ($taxes as $tax) {
                                                    $taxValue = floatval($tax->amount);
                                                    if ($tax->include_base_amount) {
                                                        $subtotal = $subtotal / (1 + ($taxValue / 100));
                                                    } else {
                                                        $taxAmount += $subtotal * ($taxValue / 100);
                                                    }
                                                }
                                            }

                                            $set('price_subtotal', number_format($subtotal, 2, '.', ''));
                                            $set('price_total', number_format($subtotal + $taxAmount, 2, '.', ''));
                                        }
                                    })
                                    ->label('Unit Price')
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.unit-price')),
                                Forms\Components\TextInput::make('price_subtotal')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->placeholder('-')
                                    ->readOnly()
                                    ->label('Subtotal')
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.subtotal')),
                                Forms\Components\TextInput::make('price_total')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly()
                                    ->placeholder('-')
                                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.total')),
                            ]),
                    ])->columns(2)
            ]);
    }

    public static function getSectionRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('sections')
            ->relationship('salesOrderSectionLines')
            ->hiddenLabel()
            ->reorderable()
            ->collapsible()
            ->defaultItems(0)
            ->cloneable()
            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
            ->deleteAction(
                fn(Action $action) => $action->requiresConfirmation(),
            )
            ->extraItemActions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->action(function (
                        array $arguments,
                        $livewire,
                        $state,
                    ): void {
                        $redirectUrl = ProductResource::getUrl('edit', ['record' => $state[$arguments['item']]['product_id']]);

                        $livewire->redirect($redirectUrl, navigate: FilamentView::hasSpaMode());
                    }),
            ])
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->live(onBlur: true)
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.name')),
                        Forms\Components\Hidden::make('quantity')
                            ->required()
                            ->default(0),
                        Forms\Components\Hidden::make('display_type')
                            ->required()
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.display-type'))
                            ->default(OrderDisplayType::SECTION->value)
                    ]),
            ]);
    }

    public static function getNoteRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('notes')
            ->relationship('salesOrderNoteLines')
            ->hiddenLabel()
            ->reorderable()
            ->defaultItems(0)
            ->collapsible()
            ->cloneable()
            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
            ->deleteAction(
                fn(Action $action) => $action->requiresConfirmation(),
            )
            ->extraItemActions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->action(function (
                        array $arguments,
                        $livewire,
                        $state,
                    ): void {
                        $redirectUrl = ProductResource::getUrl('edit', ['record' => $state[$arguments['item']]['product_id']]);

                        $livewire->redirect($redirectUrl, navigate: FilamentView::hasSpaMode());
                    }),
            ])
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->live(onBlur: true)
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.name')),
                        Forms\Components\Hidden::make('quantity')
                            ->required()
                            ->default(0),
                        Forms\Components\Hidden::make('display_type')
                            ->required()
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.products.fields.display-type'))
                            ->default(OrderDisplayType::NOTE->value)
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view'   => Pages\ViewQuotation::route('/{record}'),
            'edit'   => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
