<?php

namespace Webkul\Account\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\AutoPost;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages;
use Webkul\Account\Models\Move as AccountMove;
use Webkul\Account\Models\Tax;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Invoice\Models\Product;
use Webkul\Account\Livewire\InvoiceSummary;
use Webkul\Invoice\Settings;

class InvoiceResource extends Resource
{
    protected static ?string $model = AccountMove::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static bool $shouldRegisterNavigation = false;

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
            __('accounts::filament/resources/invoice.navigation.global-search.number')           => $record?->name ?? '—',
            __('accounts::filament/resources/invoice.navigation.global-search.customer')         => $record?->invoice_partner_display_name ?? '—',
            __('accounts::filament/resources/invoice.navigation.global-search.invoice-date')     => $record?->invoice_date ?? '—',
            __('accounts::filament/resources/invoice.navigation.global-search.invoice-date-due') => $record?->invoice_date_due ?? '—',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(MoveState::class)
                    ->default(MoveState::DRAFT->value)
                    ->columnSpan('full')
                    ->disabled()
                    ->live()
                    ->reactive(),
                Forms\Components\Section::make(__('purchases::filament/clusters/orders/resources/order.form.sections.general.title'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Customer Invoice'))
                                    ->required()
                                    ->maxLength(255)
                                    ->extraInputAttributes(['style' => 'font-size: 1.5rem;height: 3rem;'])
                                    ->placeholder('INV/2025/00001')
                                    ->default(fn() => AccountMove::generateNextInvoiceNumber())
                                    ->unique(
                                        table: 'accounts_account_moves',
                                        column: 'name',
                                        ignoreRecord: true,
                                    )
                                    ->columnSpan(1),
                            ])->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('partner_id')
                                    ->label(__('Customer'))
                                    ->relationship(
                                        'partner',
                                        'name',
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\DatePicker::make('invoice_date')
                                    ->label(__('Invoice Date'))
                                    ->default(now())
                                    ->native(false),
                                Forms\Components\DatePicker::make('invoice_date_due')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->live()
                                    ->hidden(fn(Get $get) => $get('invoice_payment_term_id') !== null)
                                    ->label(__('Due Date')),
                                Forms\Components\Select::make('invoice_payment_term_id')
                                    ->relationship('invoicePaymentTerm', 'name')
                                    ->required(fn(Get $get) => $get('invoice_date_due') === null)
                                    ->live()
                                    ->searchable()
                                    ->preload()
                                    ->label(__('Payment Term')),
                            ])->columns(2),
                    ]),
                Forms\Components\Tabs::make()
                    ->schema([
                        Forms\Components\Tabs\Tab::make(__('Invoice Lines'))
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                static::getProductRepeater(),
                                Forms\Components\Livewire::make(InvoiceSummary::class, function (Forms\Get $get) {
                                    return [
                                        'products' => $get('products'),
                                    ];
                                })
                                    ->live()
                                    ->reactive(),
                            ]),
                        Forms\Components\Tabs\Tab::make(__('Other Information'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Fieldset::make('Invoice')
                                    ->schema([
                                        Forms\Components\TextInput::make('reference')
                                            ->label(__('Customer Reference'))
                                            ->maxLength(255),
                                        Forms\Components\Select::make('invoice_user_id')
                                            ->relationship('invoiceUser', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('Sales Person')),
                                        Forms\Components\Select::make('partner_bank_id')
                                            ->relationship('partnerBank', 'account_number')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('Recipient Bank')),
                                        Forms\Components\TextInput::make('payment_reference')
                                            ->label(__('Payment Reference')),
                                        Forms\Components\DatePicker::make('delivery_date')
                                            ->native(false)
                                            ->label(__('Delivery Date')),
                                    ]),
                                Forms\Components\Fieldset::make('Accounting')
                                    ->schema([
                                        Forms\Components\Select::make('invoice_incoterm_id')
                                            ->relationship('invoiceIncoterm', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('Incoterm')),
                                        Forms\Components\TextInput::make('incoterm_location')
                                            ->label(__('Incoterm Address')),
                                        Forms\Components\Select::make('preferred_payment_method_line_id')
                                            ->relationship('paymentMethodLine', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->label(__('Payment Method')),
                                        Forms\Components\Select::make('auto_post')
                                            ->options(AutoPost::class)
                                            ->default(AutoPost::NO->value)
                                            ->label(__('Auto Post')),
                                        Forms\Components\Toggle::make('checked')
                                            ->inline(false)
                                            ->label(__('Checked')),
                                    ]),
                                Forms\Components\Fieldset::make('Additional Information')
                                    ->schema([
                                        Forms\Components\Select::make('company_id')
                                            ->label(__('Company'))
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(Auth::user()->default_company_id),
                                        Forms\Components\Select::make('currency_id')
                                            ->label(__('Currency'))
                                            ->relationship('currency', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->default(Auth::user()->defaultCompany?->currency_id),
                                    ]),
                                Forms\Components\Fieldset::make('Marketing')
                                    ->schema([
                                        Forms\Components\Select::make('campaign_id')
                                            ->relationship('campaign', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('Campaign')),
                                        Forms\Components\Select::make('medium_id')
                                            ->relationship('medium', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('Medium')),
                                        Forms\Components\Select::make('source_id')
                                            ->relationship('source', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label(__('Source')),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->columns('full');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->placeholder('-')
                    ->label(__('accounts::filament/resources/invoice.table.columns.number'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_partner_display_name')
                    ->label(__('accounts::filament/resources/invoice.table.columns.customer'))
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->placeholder('-')
                    ->label(__('accounts::filament/resources/invoice.table.columns.invoice-date'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('checked')
                    ->boolean()
                    ->placeholder('-')
                    ->label(__('accounts::filament/resources/invoice.table.columns.checked'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->placeholder('-')
                    ->label(__('accounts::filament/resources/invoice.table.columns.accounting-date'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date_due')
                    ->date()
                    ->placeholder('-')
                    ->label(__('accounts::filament/resources/invoice.table.columns.due-date'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_origin')
                    ->date()
                    ->placeholder('-')
                    ->label(__('accounts::filament/resources/invoice.table.columns.source-document'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('accounts::filament/resources/invoice.table.columns.reference'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoiceUser.name')
                    ->label(__('accounts::filament/resources/invoice.table.columns.sales-person'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_untaxed_in_currency_signed')
                    ->label(__('accounts::filament/resources/invoice.table.columns.tax-excluded'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_tax_signed')
                    ->label(__('accounts::filament/resources/invoice.table.columns.tax'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_total_in_currency_signed')
                    ->label(__('accounts::filament/resources/invoice.table.columns.total'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount_residual_signed')
                    ->label(__('accounts::filament/resources/invoice.table.columns.amount-due'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currency.id')
                    ->label(__('accounts::filament/resources/invoice.table.columns.invoice-currency'))
                    ->searchable()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label(__('accounts::filament/resources/invoice.table.groups.name'))
                    ->collapsible(),
                Tables\Grouping\Group::make('invoice_partner_display_name')
                    ->label(__('accounts::filament/resources/invoice.table.groups.invoice-partner-display-name'))
                    ->collapsible(),
                Tables\Grouping\Group::make('invoice_date')
                    ->label(__('accounts::filament/resources/invoice.table.groups.invoice-date'))
                    ->collapsible(),
                Tables\Grouping\Group::make('checked')
                    ->label(__('accounts::filament/resources/invoice.table.groups.checked'))
                    ->collapsible(),
                Tables\Grouping\Group::make('date')
                    ->date()
                    ->label(__('accounts::filament/resources/invoice.table.groups.date'))
                    ->collapsible(),
                Tables\Grouping\Group::make('invoice_date_due')
                    ->date()
                    ->label(__('accounts::filament/resources/invoice.table.groups.invoice-due-date'))
                    ->collapsible(),
                Tables\Grouping\Group::make('invoice_origin')
                    ->date()
                    ->label(__('accounts::filament/resources/invoice.table.groups.invoice-origin'))
                    ->collapsible(),
                Tables\Grouping\Group::make('invoiceUser.name')
                    ->date()
                    ->label(__('accounts::filament/resources/invoice.table.groups.sales-person'))
                    ->collapsible(),
                Tables\Grouping\Group::make('currency.name')
                    ->date()
                    ->label(__('accounts::filament/resources/invoice.table.groups.currency'))
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('accounts::filament/resources/invoice.table.groups.created-at'))
                    ->date()
                    ->collapsible(),
                Tables\Grouping\Group::make('updated_at')
                    ->label(__('accounts::filament/resources/invoice.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->filtersFormColumns(2)
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraintPickerColumns(2)
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('name')
                            ->label(__('accounts::filament/resources/invoice.table.filters.number')),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('invoice_origin')
                            ->label(__('accounts::filament/resources/invoice.table.filters.invoice-origin')),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('reference')
                            ->label(__('accounts::filament/resources/invoice.table.filters.reference')),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('invoice_partner_display_name')
                            ->label(__('accounts::filament/resources/invoice.table.filters.invoice-partner-display-name')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('invoice_date')
                            ->label(__('accounts::filament/resources/invoice.table.filters.invoice-date')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('invoice_due_date')
                            ->label(__('accounts::filament/resources/invoice.table.filters.invoice-due-date')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at')
                            ->label(__('accounts::filament/resources/invoice.table.filters.created-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('updated_at')
                            ->label(__('accounts::filament/resources/invoice.table.filters.updated-at')),
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('accounts::filament/resources/invoice.table.actions.delete.notification.title'))
                                ->body(__('accounts::filament/resources/invoice.table.actions.delete.notification.body'))
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('accounts::filament/resources/invoice.table.bulk-actions.delete.notification.title'))
                                ->body(__('accounts::filament/resources/invoice.table.bulk-actions.delete.notification.body'))
                        ),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('purchases::filament/clusters/orders/resources/order.form.sections.general.title'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\Grid::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->placeholder('-')
                                    ->label(__('Customer Invoice'))
                                    ->icon('heroicon-o-document')
                                    ->weight('bold')
                                    ->size(TextEntrySize::Large),
                            ])->columns(2),
                        Infolists\Components\Grid::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('partner.name')
                                    ->placeholder('-')
                                    ->label(__('Customer'))
                                    ->visible(fn($record) => $record->partner_id !== null)
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('invoice_partner_display_name')
                                    ->placeholder('-')
                                    ->label(__('Customer'))
                                    ->visible(fn($record) => $record->partner_id === null)
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('invoice_date')
                                    ->placeholder('-')
                                    ->label(__('Invoice Date'))
                                    ->icon('heroicon-o-calendar')
                                    ->date(),
                                Infolists\Components\TextEntry::make('invoice_date_due')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-clock')
                                    ->date(),
                                Infolists\Components\TextEntry::make('invoicePaymentTerm.name')
                                    ->placeholder('-')
                                    ->label(__('Payment Term'))
                                    ->icon('heroicon-o-calendar-days'),
                            ])->columns(2),
                    ]),
                Infolists\Components\Tabs::make()
                    ->columnSpan('full')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make(__('Invoice Lines'))
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('products')
                                            ->schema([
                                                Infolists\Components\Grid::make()
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('product.name')
                                                            ->placeholder('-')
                                                            ->label(__('Product'))
                                                            ->icon('heroicon-o-cube'),
                                                        Infolists\Components\TextEntry::make('description')
                                                            ->placeholder('-')
                                                            ->label(__('Description'))
                                                            ->icon('heroicon-o-document-text')
                                                            ->columnSpan(2),
                                                        Infolists\Components\TextEntry::make('quantity')
                                                            ->placeholder('-')
                                                            ->label(__('Quantity'))
                                                            ->icon('heroicon-o-hashtag'),
                                                        Infolists\Components\TextEntry::make('product_uom.name')
                                                            ->placeholder('-')
                                                            ->label(__('Unit of Measure'))
                                                            ->icon('heroicon-o-scale'),
                                                        Infolists\Components\TextEntry::make('price_unit')
                                                            ->placeholder('-')
                                                            ->label(__('Unit Price'))
                                                            ->icon('heroicon-o-currency-dollar')
                                                            ->money('USD'),
                                                        Infolists\Components\TextEntry::make('discount')
                                                            ->placeholder('-')
                                                            ->label(__('Discount'))
                                                            ->icon('heroicon-o-tag')
                                                            ->suffix('%'),
                                                        Infolists\Components\TextEntry::make('tax.name')
                                                            ->placeholder('-')
                                                            ->label(__('Tax'))
                                                            ->icon('heroicon-o-receipt-percent'),
                                                        Infolists\Components\TextEntry::make('price_subtotal')
                                                            ->placeholder('-')
                                                            ->label(__('Subtotal'))
                                                            ->icon('heroicon-o-calculator')
                                                            ->money('USD'),
                                                        Infolists\Components\TextEntry::make('price_total')
                                                            ->placeholder('-')
                                                            ->label(__('Total'))
                                                            ->icon('heroicon-o-banknotes')
                                                            ->money('USD')
                                                            ->weight('bold'),
                                                    ])->columns(3),
                                            ]),
                                    ]),
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('amount_untaxed')
                                            ->placeholder('-')
                                            ->label(__('Untaxed Amount'))
                                            ->icon('heroicon-o-calculator')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('amount_tax')
                                            ->placeholder('-')
                                            ->label(__('Tax'))
                                            ->icon('heroicon-o-receipt-percent')
                                            ->money('USD'),
                                        Infolists\Components\TextEntry::make('amount_total')
                                            ->placeholder('-')
                                            ->label(__('Total'))
                                            ->icon('heroicon-o-banknotes')
                                            ->money('USD')
                                            ->weight('bold')
                                            ->size(TextEntrySize::Large),
                                    ])
                                    ->columns(3)
                                    ->grow(false),
                            ]),
                        Infolists\Components\Tabs\Tab::make(__('Other Information'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Infolists\Components\Section::make('Invoice')
                                    ->icon('heroicon-o-document')
                                    ->schema([
                                        Infolists\Components\Grid::make()
                                            ->schema([
                                                Infolists\Components\TextEntry::make('reference')
                                                    ->placeholder('-')
                                                    ->label(__('Customer Reference'))
                                                    ->icon('heroicon-o-hashtag'),
                                                Infolists\Components\TextEntry::make('invoiceUser.name')
                                                    ->placeholder('-')
                                                    ->label(__('Sales Person'))
                                                    ->icon('heroicon-o-user'),
                                                Infolists\Components\TextEntry::make('partnerBank.account_number')
                                                    ->placeholder('-')
                                                    ->label(__('Recipient Bank'))
                                                    ->icon('heroicon-o-building-library'),
                                                Infolists\Components\TextEntry::make('payment_reference')
                                                    ->placeholder('-')
                                                    ->label(__('Payment Reference'))
                                                    ->icon('heroicon-o-identification'),
                                                Infolists\Components\TextEntry::make('delivery_date')
                                                    ->placeholder('-')
                                                    ->label(__('Delivery Date'))
                                                    ->icon('heroicon-o-truck')
                                                    ->date(),
                                            ])->columns(2),
                                    ]),
                                Infolists\Components\Section::make('Accounting')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Infolists\Components\Grid::make()
                                            ->schema([
                                                Infolists\Components\TextEntry::make('invoiceIncoterm.name')
                                                    ->placeholder('-')
                                                    ->label(__('Incoterm'))
                                                    ->icon('heroicon-o-globe-alt'),
                                                Infolists\Components\TextEntry::make('incoterm_location')
                                                    ->placeholder('-')
                                                    ->label(__('Incoterm Address'))
                                                    ->icon('heroicon-o-map-pin'),
                                                Infolists\Components\TextEntry::make('paymentMethodLine.name')
                                                    ->placeholder('-')
                                                    ->label(__('Payment Method'))
                                                    ->icon('heroicon-o-credit-card'),
                                                Infolists\Components\TextEntry::make('auto_post')
                                                    ->placeholder('-')
                                                    ->label(__('Auto Post'))
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->formatStateUsing(fn(string $state): string => AutoPost::from($state)->getLabel()),
                                                Infolists\Components\IconEntry::make('checked')
                                                    ->label(__('Checked'))
                                                    ->icon('heroicon-o-check-circle')
                                                    ->boolean(),
                                            ])->columns(2),
                                    ]),
                                Infolists\Components\Section::make('Marketing')
                                    ->icon('heroicon-o-megaphone')
                                    ->schema([
                                        Infolists\Components\Grid::make()
                                            ->schema([
                                                Infolists\Components\TextEntry::make('campaign.name')
                                                    ->placeholder('-')
                                                    ->label(__('Campaign'))
                                                    ->icon('heroicon-o-presentation-chart-line'),
                                                Infolists\Components\TextEntry::make('medium.name')
                                                    ->placeholder('-')
                                                    ->label(__('Medium'))
                                                    ->icon('heroicon-o-device-phone-mobile'),
                                                Infolists\Components\TextEntry::make('source.name')
                                                    ->placeholder('-')
                                                    ->label(__('Source'))
                                                    ->icon('heroicon-o-link'),
                                            ])->columns(2),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
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
            ->relationship('lines')
            ->hiddenLabel()
            ->reorderable()
            ->live()
            ->reactive()
            ->label(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.title'))
            ->addActionLabel(__('purchases::filament/clusters/orders/resources/order.form.tabs.products.repeater.products.add-product-line'))
            ->collapsible()
            ->defaultItems(0)
            ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
            ->deleteAction(fn(Forms\Components\Actions\Action $action) => $action->requiresConfirmation())
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label(__('Product'))
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($get('product_id')) {
                                            $product = Product::find($get('product_id'));

                                            $set('taxes', $product->productTaxes->pluck('id')->toArray());
                                        }

                                        self::calculateLineTotals($set, $get);
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        self::calculateLineTotals($set, $get);
                                    }),
                                Forms\Components\Select::make('uom_id')
                                    ->label(__('Unit'))
                                    ->relationship(
                                        'uom',
                                        'name',
                                        fn($query) => $query->where('category_id', 1),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->visible(fn(Settings\ProductSettings $settings) => $settings->enable_uom),
                                Forms\Components\Select::make('taxes')
                                    ->label(__('Taxes'))
                                    ->relationship(
                                        'taxes',
                                        'name',
                                        function (Builder $query) {
                                            return $query->where('type_tax_use', TypeTaxUse::PURCHASE->value);
                                        },
                                    )
                                    ->searchable()
                                    ->multiple()
                                    ->preload()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        self::calculateLineTotals($set, $get);
                                    })
                                    ->live(),
                                Forms\Components\TextInput::make('discount')
                                    ->label(__('Discount'))
                                    ->numeric()
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        self::calculateLineTotals($set, $get);
                                    }),
                                Forms\Components\TextInput::make('price_unit')
                                    ->label(__('Price Unit'))
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        self::calculateLineTotals($set, $get);
                                    }),
                                Forms\Components\TextInput::make('price_subtotal')
                                    ->label(__('Price'))
                                    ->default(0)
                                    ->readOnly(),
                                Forms\Components\Hidden::make('price_tax')
                                    ->default(0),
                                Forms\Components\Hidden::make('price_total')
                                    ->default(0),
                            ]),
                    ])
                    ->columns(2),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record) {
                $product = Product::find($data['product_id']);

                $data = array_merge($data, [
                    'name'                  => $product->name,
                    'quantity'              => $data['quantity'],
                    'uom_id'                => $data['uom_id'] ?? $product->uom_id,
                    'currency_id'           => $record->currency_id,
                    'partner_id'            => $record->partner_id,
                    'creator_id'            => Auth::id(),
                    'partner_id'            => $record->partner_id,
                    'company_id'            => Auth::user()->default_company_id,
                    'company_currency_id'   => Auth::user()->defaultCompany->currency_id ?? $record->currency_id,
                    'commercial_partner_id' => $record->partner_id,
                    'display_type'          => 'product',
                ]);

                return $data;
            });
    }

    private static function calculateLineTotals(Forms\Set $set, Forms\Get $get): void
    {
        if (! $get('product_id')) {
            $set('price_unit', 0);

            $set('discount', 0);

            $set('price_tax', 0);

            $set('price_subtotal', 0);

            $set('price_total', 0);

            return;
        }

        $product = Product::find($get('product_id'));

        $priceUnit = floatval($product->cost ?? $product->price);

        $set('price_unit', $priceUnit);

        $quantity = floatval($get('quantity') ?? 1);

        $taxIds = $get('taxes') ?? [];

        $taxAmount = 0;

        $subTotal = ($priceUnit * $quantity) - ($get('discount') ?? 0);

        if (! empty($taxIds)) {
            $taxes = Tax::whereIn('id', $taxIds)
                ->orderBy('sort')
                ->get();

            $baseAmount = $subTotal;

            $taxesComputed = [];

            foreach ($taxes as $tax) {
                $amount = floatval($tax->amount);

                $currentTaxBase = $baseAmount;

                $tax->price_include_override ??= 'tax_excluded';

                if ($tax->is_base_affected) {
                    foreach ($taxesComputed as $prevTax) {
                        if ($prevTax['include_base_amount']) {
                            $currentTaxBase += $prevTax['tax_amount'];
                        }
                    }
                }

                $currentTaxAmount = 0;

                if ($tax->price_include_override == 'tax_included') {
                    $taxFactor = ($tax->amount_type == 'percent') ? $amount / 100 : $amount;

                    $currentTaxAmount = $currentTaxBase - ($currentTaxBase / (1 + $taxFactor));

                    if (empty($taxesComputed)) {
                        $priceUnit = $priceUnit - ($currentTaxAmount / $quantity);

                        $subTotal = $priceUnit * $quantity;

                        $baseAmount = $subTotal;
                    }
                } elseif ($tax->price_include_override == 'tax_excluded') {
                    if ($tax->amount_type == 'percent') {
                        $currentTaxAmount = $currentTaxBase * $amount / 100;
                    } else {
                        $currentTaxAmount = $amount * $quantity;
                    }
                }

                $taxesComputed[] = [
                    'tax_id'              => $tax->id,
                    'tax_amount'          => $currentTaxAmount,
                    'include_base_amount' => $tax->include_base_amount,
                ];

                $taxAmount += $currentTaxAmount;
            }
        }

        $set('price_subtotal', round($subTotal, 4));

        $set('price_tax', $taxAmount);

        $set('price_total', $subTotal + $taxAmount);
    }
}
