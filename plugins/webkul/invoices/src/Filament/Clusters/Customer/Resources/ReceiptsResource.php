<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\ReceiptsResource\Pages;
use Webkul\Account\Filament\Resources\InvoiceResource as BaseInvoiceResource;

class ReceiptsResource extends BaseInvoiceResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Customer::class;

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/receipts.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/receipts.navigation.title');
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
                    Forms\Components\Fieldset::make(__('invoices::filament/clusters/customers/resources/receipts.form.fieldset.title'))
                        ->schema([
                            Forms\Components\TextInput::make('reference')
                                ->label(__('invoices::filament/clusters/customers/resources/receipts.form.fieldset.fields.reference')),
                            Forms\Components\TextInput::make('payment_reference')
                                ->label(__('invoices::filament/clusters/customers/resources/receipts.form.fieldset.fields.payment-reference')),
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
            'index'  => Pages\ListReceipts::route('/'),
            'create' => Pages\CreateReceipts::route('/create'),
            'edit'   => Pages\EditReceipts::route('/{record}/edit'),
            'view'   => Pages\ViewReceipts::route('/{record}'),
        ];
    }
}
