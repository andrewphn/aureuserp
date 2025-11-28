<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Models\PaymentTerm;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\CreateQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ListQuotations;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageDeliveries;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ManageInvoices;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\ViewQuotation;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Schemas\ProductRepeaterSchema;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Schemas\QuotationInfolistSchema;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Schemas\QuotationTableSchema;
use Webkul\Sale\Livewire\Summary;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Sale\Models\Product;
use Webkul\Sale\Settings;
use Webkul\Sale\Settings\PriceSettings;
use Webkul\Sale\Settings\QuotationAndOrderSettings;
use Webkul\Support\Filament\Forms\Components\Repeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

/**
 * Quotation Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class QuotationResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Orders::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /**
     * Get the model label
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/quotation.title');
    }

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/quotation.navigation.title');
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
                ProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(function ($record) {
                        $options = OrderState::options();

                        if (
                            $record
                            && $record->state != OrderState::CANCEL->value
                        ) {
                            unset($options[OrderState::CANCEL->value]);
                        }

                        if ($record == null) {
                            unset($options[OrderState::CANCEL->value]);
                        }

                        return $options;
                    })
                    ->default(OrderState::DRAFT->value)
                    ->disabled()
                    ->live()
                    ->reactive(),
                static::getGeneralSection(),
                static::getFormTabs(),
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
        return QuotationTableSchema::configure($table);
    }

    /**
     * Define the infolist schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return QuotationInfolistSchema::configure($schema);
    }

    /**
     * Get the general information section
     */
    private static function getGeneralSection(): Section
    {
        return Section::make(__('sales::filament/clusters/orders/resources/quotation.form.section.general.title'))
            ->icon('heroicon-o-document-text')
            ->schema([
                Select::make('sale_order_template_id')
                    ->label(__('Quotation Template'))
                    ->relationship(
                        'quotationTemplate',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true)
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        if (! $state) {
                            return;
                        }

                        $template = \Webkul\Sale\Models\OrderTemplate::find($state);

                        if (! $template) {
                            return;
                        }

                        if ($template->number_of_days) {
                            $set('validity_date', now()->addDays($template->number_of_days));
                        }

                        if ($template->note) {
                            $set('note', $template->note);
                        }
                    })
                    ->helperText(__('Select a template to auto-fill default values'))
                    ->columnSpanFull()
                    ->hidden(fn ($record) => $record),
                Select::make('document_template_id')
                    ->label(__('Proposal Document Template'))
                    ->relationship(
                        'documentTemplate',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->where('type', 'proposal')
                            ->orWhere('is_default', true)
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->columnSpanFull()
                    ->hidden(fn ($record) => $record),

                Group::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                static::getCustomerSelect(),
                                static::getProjectSelect(),
                            ]),
                        DatePicker::make('validity_date')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.section.general.fields.expiration'))
                            ->native(false)
                            ->default(fn (QuotationAndOrderSettings $settings) => now()->addDays($settings->default_quotation_validity))
                            ->required()
                            ->hidden(fn ($record) => $record)
                            ->disabled(fn ($record): bool => $record?->locked || in_array($record?->state, [OrderState::CANCEL])),
                        DatePicker::make('date_order')
                            ->label(function ($record) {
                                return $record?->state == OrderState::SALE
                                    ? __('sales::filament/clusters/orders/resources/quotation.form.section.general.fields.order-date')
                                    : __('sales::filament/clusters/orders/resources/quotation.form.section.general.fields.quotation-date');
                            })
                            ->default(now())
                            ->native(false)
                            ->required()
                            ->disabled(fn ($record): bool => $record?->locked || in_array($record?->state, [OrderState::CANCEL])),
                        Select::make('payment_term_id')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.section.general.fields.payment-term'))
                            ->relationship('paymentTerm', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(PaymentTerm::find(10)?->id)
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    /**
     * Get customer select field with create option
     */
    private static function getCustomerSelect(): Select
    {
        return Select::make('partner_id')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.section.general.fields.customer'))
            ->relationship(
                'partner',
                'name',
                modifyQueryUsing: fn (Builder $query) => $query
                    ->withTrashed()
                    ->where('sub_type', 'customer')
                    ->orderBy('id')
            )
            ->searchable()
            ->preload()
            ->required()
            ->live()
            ->disabled(fn ($record): bool => $record?->locked || in_array($record?->state, [OrderState::CANCEL]))
            ->columnSpan(1)
            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name.($record->trashed() ? ' (Deleted)' : ''))
            ->disableOptionWhen(fn ($label) => str_contains($label, ' (Deleted)'))
            ->createOptionForm([
                Select::make('type')
                    ->label(__('Account type'))
                    ->options([
                        'individual' => __('Individual'),
                        'company' => __('Company'),
                    ])
                    ->default('company')
                    ->required(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Grid::make(2)->schema([
                    TextInput::make('phone')
                        ->label(__('Phone'))
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->maxLength(255),
                ]),
                Fieldset::make(__('Address'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('street')
                                ->label(__('Street 1'))
                                ->maxLength(255),
                            TextInput::make('street2')
                                ->label(__('Street 2'))
                                ->maxLength(255),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('city')
                                ->label(__('City'))
                                ->maxLength(255),
                            TextInput::make('zip')
                                ->label(__('ZIP'))
                                ->maxLength(20),
                            Select::make('state_id')
                                ->label(__('State'))
                                ->relationship('state', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Select::make('country_id')
                            ->label(__('Country'))
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload()
                            ->default(233),
                    ]),
            ])
            ->createOptionUsing(function (array $data): int {
                $partner = \Webkul\Partner\Models\Partner::create([
                    'account_type' => $data['type'],
                    'sub_type' => 'customer',
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'street1' => $data['street'] ?? null,
                    'street2' => $data['street2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country_id' => $data['country_id'] ?? 233,
                    'user_id' => auth()->id(),
                ]);

                return $partner->id;
            });
    }

    /**
     * Get project select field
     */
    private static function getProjectSelect(): Select
    {
        return Select::make('project_id')
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.section.general.fields.project'))
            ->relationship(
                'project',
                'name',
                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                    ->when($get('partner_id'), fn ($q, $partnerId) =>
                        $q->whereHas('partner', fn ($pq) => $pq->where('id', $partnerId))
                    )
                    ->orderBy('id', 'desc')
            )
            ->searchable()
            ->preload()
            ->live()
            ->disabled(fn ($record): bool => $record?->locked || in_array($record?->state, [OrderState::CANCEL]))
            ->columnSpan(1)
            ->helperText(__('Select a project to use its address in the order number'));
    }

    /**
     * Get form tabs
     */
    private static function getFormTabs(): Tabs
    {
        return Tabs::make()
            ->schema([
                Tab::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.title'))
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        ProductRepeaterSchema::make(),
                        Livewire::make(Summary::class, function (Get $get, PriceSettings $settings) {
                            return [
                                'currency'     => Currency::find($get('currency_id')),
                                'products'     => $get('products'),
                                'enableMargin' => $settings->enable_margin,
                            ];
                        })
                            ->live()
                            ->reactive(),
                    ]),
                Tab::make(__('Optional Products'))
                    ->hidden(fn ($record) => in_array($record?->state, [OrderState::CANCEL]))
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->schema(function (Set $set, Get $get) {
                        return [
                            static::getOptionalProductRepeater($get, $set),
                        ];
                    }),
                static::getOtherInfoTab(),
                Tab::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.term-and-conditions.title'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        RichEditor::make('note')
                            ->hiddenLabel(),
                    ]),
            ]);
    }

    /**
     * Get other information tab
     */
    private static function getOtherInfoTab(): Tab
    {
        return Tab::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.title'))
            ->icon('heroicon-o-information-circle')
            ->schema([
                Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.title'))
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.sales-person')),
                        TextInput::make('client_order_ref')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.customer-reference')),
                        Select::make('sales_order_tags')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.sales.fields.tags'))
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
                Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.shipping.title'))
                    ->schema([
                        DatePicker::make('commitment_date')
                            ->disabled(fn ($record) => in_array($record?->state, [OrderState::CANCEL]))
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.shipping.fields.commitment-date'))
                            ->native(false),
                    ]),
                Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.title'))
                    ->schema([
                        TextInput::make('origin')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.source-document'))
                            ->maxLength(255),
                        Select::make('campaign_id')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.campaign'))
                            ->relationship('campaign', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('medium_id')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.medium'))
                            ->relationship('medium', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('utm_source_id')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.tracking.fields.source'))
                            ->relationship('utmSource', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                Fieldset::make(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.additional-information.title'))
                    ->schema([
                        Select::make('company_id')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.additional-information.fields.company'))
                            ->relationship('company', 'name', modifyQueryUsing: fn (Builder $query) => $query->withTrashed())
                            ->getOptionLabelFromRecordUsing(function ($record): string {
                                return $record->name.($record->trashed() ? ' (Deleted)' : '');
                            })
                            ->disableOptionWhen(function ($label) {
                                return str_contains($label, ' (Deleted)');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $company = $get('company_id') ? \Webkul\Support\Models\Company::find($get('company_id')) : null;

                                if ($company) {
                                    $set('currency_id', $company->currency_id);
                                }
                            })
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => $set('currency_id', Company::find($state)?->currency_id))
                            ->default(Auth::user()->default_company_id),
                        Select::make('currency_id')
                            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.other-information.fieldset.additional-information.fields.currency'))
                            ->relationship('currency', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->reactive()
                            ->default(Auth::user()->defaultCompany?->currency_id),
                    ]),
            ]);
    }

    /**
     * Get optional product repeater
     */
    /**
     * Get Optional Product Repeater
     *
     * @param Get $parentGet
     * @param Set $parentSet
     * @return Repeater
     */
    public static function getOptionalProductRepeater(Get $parentGet, Set $parentSet): Repeater
    {
        return Repeater::make('optionalProducts')
            ->relationship('optionalLines')
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.title'))
            ->addActionLabel(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.add-product'))
            ->collapsible()
            ->defaultItems(0)
            ->itemLabel(function ($state) {
                if (! empty($state['name'])) {
                    return $state['name'];
                }

                $product = Product::find($state['product_id']);

                return $product->name ?? null;
            })
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
            ->table([
                TableColumn::make('product_id')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.columns.product'))
                    ->width(250)
                    ->markAsRequired()
                    ->toggleable(),
                TableColumn::make('name')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.columns.description'))
                    ->width(250)
                    ->markAsRequired()
                    ->toggleable(isToggledHiddenByDefault: true),
                TableColumn::make('quantity')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.columns.quantity'))
                    ->width(150)
                    ->markAsRequired(),
                TableColumn::make('product_uom_id')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.columns.uom'))
                    ->width(150)
                    ->markAsRequired()
                    ->toggleable(isToggledHiddenByDefault: true),
                TableColumn::make('price_unit')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.columns.unit-price'))
                    ->width(150)
                    ->markAsRequired(),
                TableColumn::make('discount')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.columns.discount-percentage'))
                    ->width(150)
                    ->toggleable(),
            ])
            ->schema([
                Select::make('product_id')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.product'))
                    ->relationship(
                        'product',
                        'name',
                        fn ($query) => $query->where('is_configurable', null),
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(true)
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $product = Product::withTrashed()->find($get('product_id'));

                        $set('name', $product->name);

                        $set('price_unit', $product->price);
                    })
                    ->required(),
                TextInput::make('name')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.description'))
                    ->required()
                    ->live()
                    ->dehydrated(),
                TextInput::make('quantity')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.quantity'))
                    ->required()
                    ->default(1)
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(99999999999)
                    ->live()
                    ->dehydrated(),
                Select::make('product_uom_id')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.uom'))
                    ->relationship(
                        'uom',
                        'name',
                        fn ($query) => $query->where('category_id', 1)->orderBy('id'),
                    )
                    ->required()
                    ->live()
                    ->default(UOM::first()?->id)
                    ->selectablePlaceholder(false)
                    ->dehydrated()
                    ->visible(fn (Settings\ProductSettings $settings) => $settings->enable_uom),
                TextInput::make('price_unit')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.unit-price'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(99999999999)
                    ->required()
                    ->live()
                    ->dehydrated(),
                TextInput::make('discount')
                    ->label(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.discount-percentage'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->live()
                    ->visible(fn (Settings\PriceSettings $settings) => $settings->enable_discount)
                    ->dehydrated(),
                Actions::make([
                    Action::make('add_order_line')
                        ->tooltip(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.actions.tooltip.add-order-line'))
                        ->hiddenLabel()
                        ->icon('heroicon-o-shopping-cart')
                        ->action(function ($state, $livewire, $record) use ($parentSet, $parentGet) {
                            $data = [
                                'product_id'      => $state['product_id'],
                                'product_qty'     => $state['quantity'],
                                'price_unit'      => $state['price_unit'],
                                'discount'        => $state['discount'],
                                'name'            => $state['name'],
                                'customer_lead'   => 0,
                                'purchase_price'  => 0,
                                'product_uom_qty' => 0,
                            ];

                            $parentSet('products', [
                                ...$parentGet('products'),
                                $data,
                            ]);

                            $user = Auth::user();

                            $data['order_id'] = $livewire->record->id;
                            $data['creator_id'] = $user->id;
                            $data['company_id'] = $user?->default_company_id;
                            $data['currency_id'] = $livewire->record->currency_id;
                            $data['product_uom_id'] = $state['product_uom_id'];
                            $orderLine = OrderLine::create($data);

                            $record->line_id = $orderLine->id;

                            $record->save();

                            $livewire->refreshFormData(['products']);

                            $products = collect($parentGet('products'))->values();

                            $orderLineEntry = $products->first(fn ($product) => $product['id'] == $orderLine->id);

                            $orderLine->update($orderLineEntry);

                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.actions.notifications.product-added.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation.form.tabs.order-line.repeater.product-optional.fields.actions.notifications.product-added.body'))
                                ->send();
                        })
                        ->extraAttributes([
                            'style' => 'margin-top: 2rem;',
                        ]),
                ])->hidden(fn ($record) => ! $record ?? false),
            ]);
    }

    /**
     * Get Record Sub Navigation
     *
     * @param Page $page Page number
     * @return array
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewQuotation::class,
            EditQuotation::class,
            ManageInvoices::class,
            ManageDeliveries::class,
        ]);
    }

    /**
     * Get the pages for this resource
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'      => ListQuotations::route('/'),
            'create'     => CreateQuotation::route('/create'),
            'view'       => ViewQuotation::route('/{record}'),
            'edit'       => EditQuotation::route('/{record}/edit'),
            'invoices'   => ManageInvoices::route('/{record}/invoices'),
            'deliveries' => ManageDeliveries::route('/{record}/deliveries'),
        ];
    }
}
