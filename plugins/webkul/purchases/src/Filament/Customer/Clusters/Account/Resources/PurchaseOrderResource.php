<?php

namespace Webkul\Purchase\Filament\Customer\Clusters\Account\Resources;

use Webkul\Website\Filament\Customer\Clusters\Account;
use Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\PurchaseOrderResource\Pages;
use Webkul\Purchase\Models\CustomerPurchaseOrder as PurchaseOrder;
use Webkul\Purchase\Models\Order;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Concerns\CanFormatState;

class PurchaseOrderResource extends Resource
{
    use CanFormatState;

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = Account::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/customer/clusters/account/resources/purchase-order.navigation.title');
    }

    public static function getModelLabel(): string
    {
        return __('purchases::filament/customer/clusters/account/resources/purchase-order.navigation.title');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('purchases::filament/customer/clusters/account/resources/purchase-order.table.columns.reference'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label(__('purchases::filament/customer/clusters/account/resources/purchase-order.table.columns.confirmation-date'))
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('invoice_status')
                    ->label(__('purchases::filament/customer/clusters/account/resources/purchase-order.table.columns.status'))
                    ->sortable()
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('purchases::filament/customer/clusters/account/resources/purchase-order.table.columns.total-amount'))
                    ->sortable()
                    ->money(fn (PurchaseOrder $record) => $record->currency->code),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('partner_id', Auth::guard('customer')->id());
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('total_amount')
                                    ->hiddenLabel()
                                    ->size('text-3xl')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->money(fn (PurchaseOrder $record) => $record->currency->code),

                                Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('accept')
                                            ->label('Accept')
                                            ->color('success')
                                            ->icon('heroicon-o-check-circle')
                                            ->action(function (PurchaseOrder $order) {
                                            }),
                                        Infolists\Components\Actions\Action::make('decline')
                                            ->label('Decline')
                                            ->color('danger')
                                            ->icon('heroicon-o-x-circle')
                                            ->action(function (PurchaseOrder $order) {
                                            }),
                                    ])
                                    ->fullWidth(),

                                Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('print')
                                            ->label('Download/Print')
                                            ->icon('heroicon-o-printer')
                                            ->action(function (PurchaseOrder $order) {
                                            }),
                                    ])
                                    ->fullWidth(),

                                Infolists\Components\ViewEntry::make('user')
                                    ->label('Buyer')
                                    ->view('purchases::filament.customer.clusters.account.order.pages.view-record.buyer-card')
                                    ->visible(fn (Order $record): bool => (bool) $record->user_id),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make()
                            ->schema([
                                Infolists\Components\Group::make()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->hiddenLabel()
                                            ->size('text-3xl')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                            ->formatStateUsing(function (PurchaseOrder $record) {
                                                return 'Purchase Order '.$record->name;
                                            }),
                                        Infolists\Components\TextEntry::make('ordered_at')
                                            ->label('Order Date'),
                                        Infolists\Components\ViewEntry::make('company')
                                            ->label('From')
                                            ->view('purchases::filament.customer.clusters.account.order.pages.view-record.from'),
                                        Infolists\Components\TextEntry::make('approved_at')
                                            ->label('Confirmation Date'),
                                        Infolists\Components\TextEntry::make('ordered_at')
                                            ->label('Receipt Date'),
                                    ]),
                                
                                Infolists\Components\Group::make()
                                    ->extraAttributes(['class' => 'mt-8'])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->hiddenLabel()
                                            ->size('text-2xl')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                            ->formatStateUsing(function (PurchaseOrder $record) {
                                                return 'Products';
                                            }),

                                        Infolists\Components\Livewire::make('list-products', function(Order $record) {
                                            return [
                                                'record' => $record,
                                            ];
                                        }),
                                    ]),

                                Infolists\Components\Group::make()
                                    ->extraAttributes(['class' => 'flex justify-end'])
                                    ->schema([
                                        Infolists\Components\Group::make()
                                            ->extraAttributes(['class' => 'custom-test'])
                                            ->schema([
                                                Infolists\Components\TextEntry::make('untaxed_amount')
                                                    ->label('Untaxed Amount')
                                                    ->extraAttributes(['class' => 'flex justify-end'])
                                                    ->inlineLabel()
                                                    ->money(fn ($record) => $record->currency->code),

                                                Infolists\Components\TextEntry::make('tax_amount')
                                                    ->label('Tax Amount')
                                                    ->extraAttributes(['class' => 'flex justify-end'])
                                                    ->inlineLabel()
                                                    ->money(fn ($record) => $record->currency->code),

                                                Infolists\Components\Group::make()
                                                    ->extraAttributes(['class' => 'border-t pt-4 font-bold'])
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('total_amount')
                                                            ->label('Total')
                                                            ->extraAttributes(['class' => 'flex justify-end'])
                                                            ->inlineLabel()
                                                            ->money(fn ($record) => $record->currency->code),
                                                    ]),
                                            ]),
                                    ]),
                                
                                Infolists\Components\Group::make()
                                    ->extraAttributes(['class' => 'mt-8'])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->hiddenLabel()
                                            ->size('text-2xl')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                            ->formatStateUsing(function (PurchaseOrder $record) {
                                                return 'Communication History';
                                            }),

                                        Infolists\Components\Livewire::make('chatter-panel', function(Order $record) {
                                            $record = Order::findOrFail($record->id);

                                            return [
                                                'record' => $record,
                                            ];
                                        }),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
            ])
            ->columns(3);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
        ];
    }
}
