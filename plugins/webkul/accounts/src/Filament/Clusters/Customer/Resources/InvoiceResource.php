<?php

namespace Webkul\Account\Filament\Clusters\Customer\Resources;

use Webkul\Account\Filament\Clusters\Customer;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\Account\Models\Move as AccountMove;
use Webkul\Sale\Livewire\Summary;
use Filament\Forms\Get;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Partner\Models\Partner;
use Filament\Forms\Set;
use Webkul\Support\Models\Currency;
use Filament\Forms\Components\Actions\Action;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\AutoPost;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Tax;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\TeamResource;
use Webkul\Sale\Models\Product;
use Webkul\Security\Filament\Resources\UserResource;
use Webkul\Account\Enums\DisplayType;

class InvoiceResource extends Resource
{
    protected static ?string $model = AccountMove::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $cluster = Customer::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('accounts::filament/clusters/customers/resources/invoice.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounts::filament/clusters/customers/resources/invoice.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('accounts::filament/clusters/customers/resources/invoice.navigation.group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'invoice_partner_display_name',
            'invoice_date',
            'invoice_date_due',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('accounts::filament/clusters/customers/resources/invoice.navigation.global-search.number')           => $record?->name ?? '—',
            __('accounts::filament/clusters/customers/resources/invoice.navigation.global-search.customer')         => $record?->invoice_partner_display_name ?? '—',
            __('accounts::filament/clusters/customers/resources/invoice.navigation.global-search.invoice-date')     => $record?->invoice_date ?? '—',
            __('accounts::filament/clusters/customers/resources/invoice.navigation.global-search.invoice-date-due') => $record?->invoice_date_due ?? '—',
        ];
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
                            ->options(MoveState::class)
                            ->default(MoveState::DRAFT->value)
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
                                        Forms\Components\Tabs\Tab::make(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.title'))
                                            ->schema([
                                                static::getProductRepeater(),
                                                static::getSectionRepeater(DisplayType::LINE_SECTION->value),
                                                static::getSectionRepeater(DisplayType::LINE_NOTE->value),
                                                Forms\Components\Livewire::make(Summary::class, function (Get $get) {
                                                    return [
                                                        'products' => $get('products'),
                                                    ];
                                                })
                                                    ->live()
                                                    ->reactive()
                                            ]),
                                        Forms\Components\Tabs\Tab::make(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.title'))
                                            ->schema([
                                                Forms\Components\Fieldset::make(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.title'))
                                                    ->schema([
                                                        Forms\Components\TextInput::make('reference')
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.customer-reference')),
                                                        Forms\Components\Select::make('invoice_user_id')
                                                            ->relationship('invoiceUser', 'name')
                                                            ->searchable()
                                                            ->createOptionForm(fn(Form $form) => UserResource::form($form))
                                                            ->preload()
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.sales-person')),
                                                        Forms\Components\Select::make('team_id')
                                                            ->relationship('team', 'name')
                                                            ->createOptionForm(fn(Form $form) => TeamResource::form($form))
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.sales-team')),
                                                        Forms\Components\Select::make('partner_bank_id')
                                                            ->relationship('partnerBank', 'account_holder_name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.recipient-bank')),
                                                        Forms\Components\TextInput::make('payment_reference')
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.payment-reference')),
                                                        Forms\Components\DatePicker::make('delivery_date')
                                                            ->native(false)
                                                            ->default(now())
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.delivery-date')),
                                                    ]),
                                                Forms\Components\Fieldset::make(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.title'))
                                                    ->schema([
                                                        Forms\Components\Select::make('invoice_incoterm_id')
                                                            ->relationship('invoiceIncoterm', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.incoterm')),
                                                        Forms\Components\TextInput::make('incoterm_location')
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.incoterm-location')),
                                                        Forms\Components\Select::make('fiscal_position_id')
                                                            ->relationship('fiscalPosition', 'name')
                                                            ->preload()
                                                            ->searchable()
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.fiscal-position')),
                                                        Forms\Components\Select::make('preferred_payment_method_line_id')
                                                            ->relationship('paymentMethodLine', 'name')
                                                            ->preload()
                                                            ->searchable()
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.payment-method')),
                                                        Forms\Components\Select::make('auto_post')
                                                            ->options(AutoPost::class)
                                                            ->default(AutoPost::NO->value)
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.auto-post')),
                                                        Forms\Components\Toggle::make('checked')
                                                            ->default(false)
                                                            ->inline(false)
                                                            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.checked')),
                                                    ]),
                                            ]),
                                        Forms\Components\Tabs\Tab::make(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.term-and-conditions.title'))
                                            ->schema([
                                                Forms\Components\RichEditor::make('narration')
                                                    ->hiddenLabel()
                                                    ->placeholder(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.term-and-conditions.fields.narration'))
                                            ]),
                                    ])
                                    ->persistTabInQueryString(),
                            ])
                            ->columnSpan(['lg' => 2]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.general.title'))
                                            ->schema([
                                                Forms\Components\Select::make('partner_id')
                                                    ->relationship(
                                                        'partner',
                                                        'name',
                                                        fn($query) => $query->where('sub_type', 'company'),
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->required()
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.general.fields.customer')),
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

                                            ])->columns(1),
                                    ]),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.title'))
                                            ->schema([
                                                Forms\Components\DatePicker::make('invoice_date')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.fields.invoice-date')),
                                                Forms\Components\DatePicker::make('invoice_date_due')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->live()
                                                    ->hidden(fn(Get $get) => $get('invoice_payment_term_id') !== null)
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.fields.due-date')),
                                                Forms\Components\Select::make('invoice_payment_term_id')
                                                    ->relationship('invoicePaymentTerm', 'name')
                                                    ->required(fn(Get $get) => $get('invoice_date_due') === null)
                                                    ->live()
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.fields.payment-term')),
                                            ])->columns(1),
                                    ]),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.marketing.title'))
                                            ->schema([
                                                Forms\Components\Select::make('campaign_id')
                                                    ->relationship('campaign', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.marketing.fields.campaign')),
                                                Forms\Components\Select::make('medium_id')
                                                    ->relationship('medium', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.marketing.fields.medium')),
                                                Forms\Components\Select::make('source_id')
                                                    ->relationship('source', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.section.fieldset.marketing.fields.source')),
                                            ])->columns(1)
                                    ])
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
                    ->placeholder('-')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_partner_display_name')
                    ->label('Customer')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->placeholder('-')
                    ->label('Invoice Date')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('checked')
                    ->boolean()
                    ->placeholder('-')
                    ->label('Checked')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->placeholder('-')
                    ->label('Accounting Date')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date_due')
                    ->date()
                    ->placeholder('-')
                    ->label('Due Date')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_origin')
                    ->date()
                    ->placeholder('-')
                    ->label('Source Document')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoiceUser.name')
                    ->label('Sales Person')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Sales Team')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_untaxed_in_currency_signed')
                    ->label('Tax Excluded')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_tax_signed')
                    ->label('Tax')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_total_in_currency_signed')
                    ->label('Total')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_residual_signed')
                    ->label('Amount Due')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currency.id')
                    ->label('Invoice Currency')
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view'   => Pages\ViewInvoice::route('/{record}'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getProductRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('products')
            ->relationship(
                'moveLines',
                fn($query) => $query->where('display_type', 'product'),
            )
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.title'))
            ->addActionLabel(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.add-product-line'))
            ->collapsible()
            ->defaultItems(0)
            ->cloneable()
            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
            ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
            ->extraItemActions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->action(function (array $arguments, $livewire, $state): void {
                        $redirectUrl = ProductResource::getUrl('edit', ['record' => $state[$arguments['item']]['product_id']]);
                        $livewire->redirect($redirectUrl, navigate: FilamentView::hasSpaMode());
                    }),
            ])
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Hidden::make('currency_id')
                                    ->default(Currency::first()->id),
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->label('Product')
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.product'))
                                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            self::updateProductCalculations($state, $set, $get);
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            self::updateProductCalculations($state, $set, $get);
                                        }
                                    })
                                    ->required(),
                                Forms\Components\Hidden::make('name')
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($get('product_id')) {
                                            self::updateLineCalculations($set, $get);
                                        }
                                    })
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.quantity')),
                                Forms\Components\Select::make('tax')
                                    ->options(Tax::where('type_tax_use', TypeTaxUse::SALE->value)->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.taxes'))
                                    ->multiple()
                                    ->preload()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($get('product_id')) {
                                            $product = Product::find($get('product_id'));
                                            $product->productTaxes()->sync($state);
                                            self::updateLineCalculations($set, $get);
                                        }
                                    })
                                    ->live(),
                                Forms\Components\TextInput::make('discount')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($get('product_id')) {
                                            self::updateLineCalculations($set, $get);
                                        }
                                    })
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.discount-percentage')),
                                Forms\Components\TextInput::make('price_unit')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($get('product_id')) {
                                            self::updateLineCalculations($set, $get);
                                        }
                                    })
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.unit-price')),
                                Forms\Components\TextInput::make('price_subtotal')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly()
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.sub-total')),
                                Forms\Components\TextInput::make('price_total')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly()
                                    ->label(__('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.products.fields.total')),
                            ]),
                    ])->columns(2)
            ])
            ->saveRelationshipsUsing(function (Model $record, $state): void {
                $existingProductIds = $record->moveLines()
                    ->where('display_type', DisplayType::PRODUCT->value)
                    ->pluck('id')
                    ->toArray();

                $processedIds = [];

                foreach ($state as $data) {
                    if (! empty($data['id'])) {
                        $processedIds[] = $data['id'];
                    }

                    $data['date'] = now();

                    $journal = Journal::where('code', 'INV')->first();

                    MoveLine::createOrUpdateProductLine([
                        'id' => $data['id'] ?? null,
                        'move_id' => $record?->id,
                        'company_id' => $record?->company_id,
                        'product_id' => $data['product_id'],
                        'currency_id' => $data['currency_id'],
                        'name' => $data['name'],
                        'quantity' => $data['quantity'],
                        'price_unit' => $data['price_unit'],
                        'discount' => $data['discount'],
                        'tax' => $data['tax'],
                        'created_by' => Auth::id(),
                        'move_name' => $record?->name ?? 'INV/' . date('Y/m'),
                        'parent_state' => MoveState::DRAFT->value,
                        'date' => now(),
                        'journal_id' => $journal?->id,
                        'account_id' => $journal?->default_account_id,
                    ]);
                }

                if (! empty($existingProductIds)) {
                    $record->moveLines()
                        ->where('display_type', DisplayType::PRODUCT->value)
                        ->whereIn('id', array_diff($existingProductIds, $processedIds))
                        ->delete();
                }
            });
    }

    public static function getSectionRepeater($displayType): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($displayType)
            ->relationship(
                'moveLines',
                fn($query) => $query->where('display_type', $displayType),
            )
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->addActionLabel(function () use ($displayType) {
                return match ($displayType) {
                    DisplayType::LINE_SECTION->value => __('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.section.title'),
                    DisplayType::LINE_NOTE->value => __('accounts::filament/clusters/customers/resources/invoice.form.tabs.products.repeater.note.title'),
                    default => null,
                };
            })
            ->collapsible()
            ->defaultItems(0)
            ->cloneable()
            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
            ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
            ->schema([
                Forms\Components\Textarea::make('name')
                    ->hiddenLabel()
                    ->required(),
                Forms\Components\Hidden::make('currency_id')
                    ->default(Currency::first()->id),
            ])
            ->saveRelationshipsUsing(function (Model $record, $state) use ($displayType) {
                $existingLineIds = $record->moveLines()
                    ->where('display_type', $displayType)
                    ->pluck('id')
                    ->toArray();

                $processedIds = [];

                $journal = Journal::where('code', 'INV')->first();

                foreach ($state as $data) {
                    $data['date'] = now();

                    $moveLine = MoveLine::updateOrCreate(
                        ['id' => $data['id'] ?? null],
                        [
                            'move_id' => $record?->id,
                            'company_id' => $record?->company_id,
                            'currency_id' => $data['currency_id'],
                            'display_type' => $displayType,
                            'name' => $data['name'],
                            'created_by' => Auth::id(),
                            'move_name' => $record?->name ?? 'INV/' . date('Y/m'),
                            'parent_state' => MoveState::DRAFT->value,
                            'date' => now(),
                            'journal_id' => $journal?->id,
                            'account_id' => $journal?->default_account_id,
                        ]
                    );

                    $processedIds[] = $moveLine->id;
                }

                if (!empty($existingLineIds)) {
                    $record->moveLines()
                        ->where('display_type', $displayType)
                        ->whereIn('id', array_diff($existingLineIds, $processedIds))
                        ->delete();
                }
            });
    }

    private static function updateProductCalculations($productId, Set $set, Get $get): void
    {
        $product = Product::find($productId);
        $quantity = floatval($get('quantity') ?? 1);
        $priceUnit = floatval($product->price);

        $set('name', $product->name);
        $set('price_unit', $priceUnit);
        $set('tax', $product->productTaxes->pluck('id')->toArray());

        self::calculateTotals($quantity, $priceUnit, floatval($get('discount')), $product->productTaxes->pluck('id')->toArray(), $set);
    }

    private static function updateLineCalculations(Set $set, Get $get): void
    {
        $quantity = floatval($get('quantity') ?? 1);
        $priceUnit = floatval($get('price_unit') ?? 0);
        $discount = floatval($get('discount') ?? 0);
        $taxIds = $get('tax') ?? [];

        self::calculateTotals($quantity, $priceUnit, $discount, $taxIds, $set);
    }

    private static function calculateTotals(float $quantity, float $priceUnit, float $discount, array $taxIds, Set $set): void
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
                if (!$tax->include_base_amount) {
                    $taxAmount += $subtotalExcludingIncludedTax * ($taxValue / 100);
                }
            }
        }

        $set('price_subtotal', number_format($subtotalBeforeTax, 2, '.', ''));
        $set('price_total', number_format($subtotalBeforeTax + $taxAmount, 2, '.', ''));
    }
}
