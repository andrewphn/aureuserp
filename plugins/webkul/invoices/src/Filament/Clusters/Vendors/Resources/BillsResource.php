<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource\Pages;
use Webkul\Account\Filament\Resources\InvoiceResource as BaseInvoiceResource;

class BillsResource extends BaseInvoiceResource
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Vendors::class;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/bill.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/bill.navigation.title');
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
                    Forms\Components\Fieldset::make(__('invoices::filament/clusters/vendors/resources/bill.form.biller'))
                        ->schema([
                            Forms\Components\TextInput::make('reference')
                                ->label(__('invoices::filament/clusters/vendors/resources/bill.form.reference')),
                            Forms\Components\TextInput::make('payment_reference')
                                ->label(__('invoices::filament/clusters/vendors/resources/bill.form.payment-reference')),
                            Forms\Components\TextInput::make('date')
                                ->label(__('invoices::filament/clusters/vendors/resources/bill.form.date')),
                        ])->columns(1),
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
            'index'  => Pages\ListBills::route('/'),
            'create' => Pages\CreateBills::route('/create'),
            'edit'   => Pages\EditBills::route('/{record}/edit'),
        ];
    }
}
