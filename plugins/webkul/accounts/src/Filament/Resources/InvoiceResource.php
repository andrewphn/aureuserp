<?php

namespace Webkul\Account\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentView;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\AutoPost;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move as AccountMove;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Tax;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Filament\Resources\ProductResource;
use Webkul\Product\Models\Product;
use Webkul\Sale\Livewire\Summary;
use Webkul\Security\Filament\Resources\UserResource;
use Webkul\Support\Models\Currency;

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
                                        Forms\Components\Tabs\Tab::make(__('accounts::filament/resources/invoice.form.tabs.products.title'))
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
                                                    ->reactive(),
                                            ]),
                                        Forms\Components\Tabs\Tab::make(__('accounts::filament/resources/invoice.form.tabs.other-information.title'))
                                            ->schema([
                                                Forms\Components\Fieldset::make(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.title'))
                                                    ->schema([
                                                        Forms\Components\TextInput::make('reference')
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.customer-reference')),
                                                        Forms\Components\Select::make('invoice_user_id')
                                                            ->relationship('invoiceUser', 'name')
                                                            ->searchable()
                                                            ->createOptionForm(fn (Form $form) => UserResource::form($form))
                                                            ->preload()
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.sales-person')),
                                                        Forms\Components\Select::make('partner_bank_id')
                                                            ->relationship('partnerBank', 'account_holder_name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.recipient-bank')),
                                                        Forms\Components\TextInput::make('payment_reference')
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.payment-reference')),
                                                        Forms\Components\DatePicker::make('delivery_date')
                                                            ->native(false)
                                                            ->default(now())
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.invoice.fields.delivery-date')),
                                                    ]),
                                                Forms\Components\Fieldset::make(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.title'))
                                                    ->schema([
                                                        Forms\Components\Select::make('invoice_incoterm_id')
                                                            ->relationship('invoiceIncoterm', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.incoterm')),
                                                        Forms\Components\TextInput::make('incoterm_location')
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.incoterm-location')),
                                                        Forms\Components\Select::make('fiscal_position_id')
                                                            ->relationship('fiscalPosition', 'name')
                                                            ->preload()
                                                            ->searchable()
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.fiscal-position')),
                                                        Forms\Components\Select::make('preferred_payment_method_line_id')
                                                            ->relationship('paymentMethodLine', 'name')
                                                            ->preload()
                                                            ->searchable()
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.payment-method')),
                                                        Forms\Components\Select::make('auto_post')
                                                            ->options(AutoPost::class)
                                                            ->default(AutoPost::NO->value)
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.auto-post')),
                                                        Forms\Components\Toggle::make('checked')
                                                            ->default(false)
                                                            ->inline(false)
                                                            ->label(__('accounts::filament/resources/invoice.form.tabs.other-information.fields.fieldset.accounting.fields.checked')),
                                                    ]),
                                            ]),
                                        Forms\Components\Tabs\Tab::make(__('accounts::filament/resources/invoice.form.tabs.term-and-conditions.title'))
                                            ->schema([
                                                Forms\Components\RichEditor::make('narration')
                                                    ->hiddenLabel()
                                                    ->placeholder(__('accounts::filament/resources/invoice.form.tabs.term-and-conditions.fields.narration')),
                                            ]),
                                    ])
                                    ->persistTabInQueryString(),
                            ])
                            ->columnSpan(['lg' => 2]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('accounts::filament/resources/invoice.form.section.fieldset.general.title'))
                                            ->schema([
                                                Forms\Components\Select::make('partner_id')
                                                    ->relationship(
                                                        'partner',
                                                        'name',
                                                        fn ($query) => $query->where('sub_type', 'company'),
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->required()
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.general.fields.customer')),
                                                Forms\Components\Placeholder::make('partner_address')
                                                    ->hiddenLabel()
                                                    ->visible(
                                                        fn (Get $get) => Partner::with('addresses')->find($get('partner_id'))?->addresses->isNotEmpty()
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
                                                            $address->street2 ? ', '.$address->street2 : '',
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
                                        Forms\Components\Fieldset::make(__('accounts::filament/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.title'))
                                            ->schema([
                                                Forms\Components\DatePicker::make('invoice_date')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.fields.invoice-date')),
                                                Forms\Components\DatePicker::make('invoice_date_due')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->live()
                                                    ->hidden(fn (Get $get) => $get('invoice_payment_term_id') !== null)
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.fields.due-date')),
                                                Forms\Components\Select::make('invoice_payment_term_id')
                                                    ->relationship('invoicePaymentTerm', 'name')
                                                    ->required(fn (Get $get) => $get('invoice_date_due') === null)
                                                    ->live()
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.invoice-date-and-payment-term.fields.payment-term')),
                                            ])->columns(1),
                                    ]),
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Fieldset::make(__('accounts::filament/resources/invoice.form.section.fieldset.marketing.title'))
                                            ->schema([
                                                Forms\Components\Select::make('campaign_id')
                                                    ->relationship('campaign', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.marketing.fields.campaign')),
                                                Forms\Components\Select::make('medium_id')
                                                    ->relationship('medium', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.marketing.fields.medium')),
                                                Forms\Components\Select::make('source_id')
                                                    ->relationship('source', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->label(__('accounts::filament/resources/invoice.form.section.fieldset.marketing.fields.source')),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view'   => Pages\ViewInvoice::route('/{record}'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
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
                                        Infolists\Components\Tabs\Tab::make(__('accounts::filament/resources/invoice.infolist.tabs.products.title'))
                                            ->schema([
                                                Infolists\Components\RepeatableEntry::make('moveLines')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('name')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.products.repeater.products.entries.product'))
                                                            ->icon('heroicon-o-shopping-bag'),
                                                        Infolists\Components\TextEntry::make('quantity')
                                                            ->numeric()
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.products.repeater.products.entries.quantity')),
                                                        Infolists\Components\TextEntry::make('unit_price')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.products.repeater.products.entries.unit-price'))
                                                            ->money('USD'),
                                                        Infolists\Components\TextEntry::make('total')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.products.repeater.products.entries.total'))
                                                            ->money('USD'),
                                                    ])
                                                    ->columns(5),
                                                Infolists\Components\Livewire::make(Summary::class, function ($record) {
                                                    return [
                                                        'products' => $record->moveLines->map(function ($item) {
                                                            return [
                                                                ...$item->toArray(),
                                                                'tax' => $item?->product?->productTaxes->pluck('id')->toArray() ?? [],
                                                            ];
                                                        })->toArray(),
                                                    ];
                                                }),
                                            ]),
                                        Infolists\Components\Tabs\Tab::make(__('accounts::filament/resources/invoice.infolist.tabs.other-information.title'))
                                            ->schema([
                                                Infolists\Components\Fieldset::make(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.invoice.title'))
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('reference')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.invoice.entries.customer-reference'))
                                                            ->icon('heroicon-o-document'),
                                                        Infolists\Components\TextEntry::make('invoiceUser.name')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.invoice.entries.sales-person'))
                                                            ->icon('heroicon-o-user'),
                                                        Infolists\Components\TextEntry::make('partnerBank.account_holder_name')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.invoice.entries.recipient-bank'))
                                                            ->icon('heroicon-o-building-library'),
                                                        Infolists\Components\TextEntry::make('payment_reference')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.invoice.entries.payment-reference'))
                                                            ->icon('heroicon-o-credit-card'),
                                                        Infolists\Components\TextEntry::make('delivery_date')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.invoice.entries.delivery-date'))
                                                            ->date()
                                                            ->icon('heroicon-o-truck'),
                                                    ]),
                                                Infolists\Components\Fieldset::make(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.title'))
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('invoiceIncoterm.name')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.entries.incoterm')),
                                                        Infolists\Components\TextEntry::make('incoterm_location')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.entries.incoterm-location')),
                                                        Infolists\Components\TextEntry::make('fiscalPosition.name')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.entries.fiscal-position'))
                                                            ->icon('heroicon-o-receipt-percent'),
                                                        Infolists\Components\TextEntry::make('paymentMethodLine.name')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.entries.payment-method')),
                                                        Infolists\Components\IconEntry::make('auto_post')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.entries.auto-post'))
                                                            ->boolean(),
                                                        Infolists\Components\IconEntry::make('checked')
                                                            ->label(__('accounts::filament/resources/invoice.infolist.tabs.other-information.entries.fieldset.accounting.entries.checked'))
                                                            ->boolean(),
                                                    ]),
                                            ]),
                                        Infolists\Components\Tabs\Tab::make(__('accounts::filament/resources/invoice.infolist.tabs.term-and-conditions.title'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('narration')
                                                    ->markdown()
                                                    ->columnSpanFull(),
                                            ]),
                                    ])->persistTabInQueryString(),
                            ])->columnSpan(2),
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('partner.name')
                                            ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.general.fields.customer'))
                                            ->icon('heroicon-o-user-circle'),
                                        Infolists\Components\TextEntry::make('partner_address')
                                            ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.general.fields.address'))
                                            ->placeholder('-')
                                            ->icon('heroicon-o-map'),
                                    ]),
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Fieldset::make(__('accounts::filament/resources/invoice.infolist.section.fieldset.invoice-date-and-payment-term.title'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('invoice_date')
                                                    ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.invoice-date-and-payment-term.fields.invoice-date'))
                                                    ->date()
                                                    ->icon('heroicon-o-calendar'),
                                                Infolists\Components\TextEntry::make('invoice_date_due')
                                                    ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.invoice-date-and-payment-term.fields.due-date'))
                                                    ->date()
                                                    ->icon('heroicon-o-clock'),
                                                Infolists\Components\TextEntry::make('invoicePaymentTerm.name')
                                                    ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.invoice-date-and-payment-term.fields.payment-term'))
                                                    ->icon('heroicon-o-credit-card'),
                                            ]),
                                    ]),
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\Fieldset::make(__('accounts::filament/resources/invoice.infolist.section.fieldset.marketing.title'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('campaign.name')
                                                    ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.marketing.fields.campaign'))
                                                    ->icon('heroicon-o-megaphone'),
                                                Infolists\Components\TextEntry::make('medium.name')
                                                    ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.marketing.fields.medium'))
                                                    ->icon('heroicon-o-signal'),
                                                Infolists\Components\TextEntry::make('source.name')
                                                    ->label(__('accounts::filament/resources/invoice.infolist.section.fieldset.marketing.fields.source'))
                                                    ->icon('heroicon-o-funnel'),
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
            ->relationship(
                'moveLines'
            )
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.title'))
            ->addActionLabel(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.add-product-line'))
            ->collapsible()
            ->defaultItems(0)
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
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
                                    ->createOptionForm(fn (Form $form) => ProductResource::form($form))
                                    ->label('Product')
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.product'))
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
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.quantity')),
                                Forms\Components\Select::make('tax')
                                    ->options(Tax::where('type_tax_use', TypeTaxUse::SALE->value)->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.taxes'))
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
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.discount-percentage')),
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
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.unit-price')),
                                Forms\Components\TextInput::make('price_subtotal')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly()
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.sub-total')),
                                Forms\Components\TextInput::make('price_total')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->readOnly()
                                    ->label(__('accounts::filament/resources/invoice.form.tabs.products.repeater.products.fields.total')),
                            ]),
                    ])->columns(2),
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
                        'id'           => $data['id'] ?? null,
                        'move_id'      => $record?->id,
                        'company_id'   => $record?->company_id,
                        'product_id'   => $data['product_id'],
                        'currency_id'  => $data['currency_id'],
                        'name'         => $data['name'],
                        'quantity'     => $data['quantity'],
                        'price_unit'   => $data['price_unit'],
                        'discount'     => $data['discount'],
                        'tax'          => $data['tax'],
                        'created_by'   => Auth::id(),
                        'move_name'    => $record?->name ?? 'INV/'.date('Y/m'),
                        'parent_state' => MoveState::DRAFT->value,
                        'date'         => now(),
                        'journal_id'   => $journal?->id,
                        'account_id'   => $journal?->default_account_id,
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
                fn ($query) => $query->where('display_type', $displayType),
            )
            ->hiddenLabel()
            ->live()
            ->reactive()
            ->addActionLabel(function () use ($displayType) {
                return match ($displayType) {
                    DisplayType::LINE_SECTION->value => __('accounts::filament/resources/invoice.form.tabs.products.repeater.section.title'),
                    DisplayType::LINE_NOTE->value    => __('accounts::filament/resources/invoice.form.tabs.products.repeater.note.title'),
                    default                          => null,
                };
            })
            ->collapsible()
            ->defaultItems(0)
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
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
                            'move_id'      => $record?->id,
                            'company_id'   => $record?->company_id,
                            'currency_id'  => $data['currency_id'],
                            'display_type' => $displayType,
                            'name'         => $data['name'],
                            'created_by'   => Auth::id(),
                            'move_name'    => $record?->name ?? 'INV/'.date('Y/m'),
                            'parent_state' => MoveState::DRAFT->value,
                            'date'         => now(),
                            'journal_id'   => $journal?->id,
                            'account_id'   => $journal?->default_account_id,
                        ]
                    );

                    $processedIds[] = $moveLine->id;
                }

                if (! empty($existingLineIds)) {
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
                if (! $tax->include_base_amount) {
                    $taxAmount += $subtotalExcludingIncludedTax * ($taxValue / 100);
                }
            }
        }

        $set('price_subtotal', number_format($subtotalBeforeTax, 2, '.', ''));
        $set('price_total', number_format($subtotalBeforeTax + $taxAmount, 2, '.', ''));
    }
}
