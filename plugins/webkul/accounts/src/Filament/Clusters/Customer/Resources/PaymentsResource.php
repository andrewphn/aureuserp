<?php

namespace Webkul\Account\Filament\Clusters\Customer\Resources;

use Webkul\Account\Filament\Clusters\Customer;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\Account\Enums\PaymentStatus;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Payment;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;

class PaymentsResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = Customer::class;

    public static function getModelLabel(): string
    {
        return __('Payments');
    }

    public static function getNavigationLabel(): string
    {
        return __('Payments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Invoices');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'state',
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('Payment') => $record?->name ?? '—',
            __('State')   => $record?->state ?? '—',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        ProgressStepper::make('state')
                            ->hiddenLabel()
                            ->inline()
                            ->options(PaymentStatus::class)
                            ->default(PaymentStatus::DRAFT->value)
                            ->columnSpan('full')
                            ->disabled()
                            ->live()
                            ->reactive(),
                    ])->columns(2),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Radio::make('payment_type')
                                    ->label('Payment Type')
                                    ->options([
                                        'outbound' => __('Send'),
                                        'inbound'  => __('Receive'),
                                    ])
                                    ->default('outbound'),
                                Forms\Components\Select::make('journal_id')
                                    ->relationship(
                                        'journal',
                                        'name',
                                        fn($query) => $query->whereIn('type', ['bank', 'cash'])
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->live()
                                    ->required(),
                                Forms\Components\Select::make('partner_bank_id')
                                    ->label('Customer (Bank Account)')
                                    ->relationship(
                                        'partnerBank',
                                        'account_number',
                                    )
                                    ->searchable()
                                    ->visible(function (Get $get, Set $set) {
                                        if ($get('journal_id')) {
                                            $journal = Journal::find($get('journal_id'));

                                            return $journal->type == 'bank';
                                        }

                                        return false;
                                    })
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('partner_id')
                                    ->label('Customer')
                                    ->relationship(
                                        'partner',
                                        'name',
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('payment_method_line_id')
                                    ->label('Payment Method')
                                    ->relationship(
                                        'paymentMethodLine',
                                        'name',
                                    )
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('amount')
                                    ->default(0)
                                    ->required(),
                                Forms\Components\DatePicker::make('date')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('memo')
                                    ->label('Memo')
                            ])->columns(2)
                    ])
            ])
            ->columns('full');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partnerBank.account_holder_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pairedInternalTransferPayment.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethodLine.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('outstandingAccount.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('destinationAccount.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paymentTransaction.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
                                Infolists\Components\Section::make('Payment Information')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('state')
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                PaymentStatus::DRAFT->value => 'gray',
                                                PaymentStatus::IN_PROCESS->value => 'warning',
                                                PaymentStatus::PAID->value => 'success',
                                                PaymentStatus::CANCELED->value => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn(string $state): string => PaymentStatus::options()[$state])
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('payment_type')
                                            ->label('Payment Type')
                                            ->icon('heroicon-o-banknotes')
                                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                                'outbound' => __('Send'),
                                                'inbound' => __('Receive'),
                                                default => $state,
                                            }),

                                        Infolists\Components\TextEntry::make('journal.name')
                                            ->label('Journal')
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—'),

                                        Infolists\Components\TextEntry::make('partnerBank.account_number')
                                            ->label('Customer (Bank Account)')
                                            ->icon('heroicon-o-building-library')
                                            ->placeholder('—')
                                            ->visible(fn($record) => $record->journal?->type === 'bank'),

                                        Infolists\Components\TextEntry::make('partner.name')
                                            ->label('Customer')
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Payment Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('amount')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('date')
                                            ->icon('heroicon-o-calendar')
                                            ->placeholder('—')
                                            ->date(),

                                        Infolists\Components\TextEntry::make('memo')
                                            ->label('Memo')
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ])->columnSpan(2),

                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Payment Method')
                                ->schema([
                                    Infolists\Components\TextEntry::make('paymentMethodLine.name')
                                        ->label('Payment Method')
                                        ->icon('heroicon-o-credit-card')
                                        ->placeholder('—'),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayments::route('/create'),
            'view' => Pages\ViewPayments::route('/{record}'),
            'edit' => Pages\EditPayments::route('/{record}/edit'),
        ];
    }
}
