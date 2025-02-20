<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;
use Filament\Tables\Table;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource as BaseInvoiceResource;

class RefundResource extends BaseInvoiceResource
{
    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Vendors::class;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getModelLabel(): string
    {
        return __('Refunds');
    }

    public static function getNavigationLabel(): string
    {
        return __('Refunds');
    }

    public static function form(Form $form): Form
    {
        $form = BaseInvoiceResource::form($form);

        $components = $form->getComponents();

        $secondGroupComponents = $components[1]->getChildComponents();

        $nestedChildComponents = $secondGroupComponents[1]->getChildComponents();

        $secondGroupComponents[1]->schema(array_merge([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Fieldset::make('Biller')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->placeholder('RBILL/2025/02/0001')
                                ->label('Vendor Credit Note'),
                            Forms\Components\TextInput::make('reference')
                                ->label('Reference'),
                            Forms\Components\TextInput::make('payment_reference')
                                ->label('Payment Reference'),
                            Forms\Components\TextInput::make('date')
                                ->label('Accounting Date'),
                        ])->columns(1)
                ]),
        ], $nestedChildComponents));

        return $form;
    }

    public static function table(Table $table): Table
    {
        return BaseInvoiceResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefunds::route('/'),
            'create' => Pages\CreateRefund::route('/create'),
            'edit' => Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}
