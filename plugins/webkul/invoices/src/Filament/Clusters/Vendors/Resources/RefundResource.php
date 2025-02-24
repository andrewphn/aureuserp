<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Webkul\Account\Filament\Resources\InvoiceResource as BaseInvoiceResource;
use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\RefundResource\Pages;

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
        return __('invoices::filament/clusters/vendors/resources/refund.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/vendors/resources/refund.navigation.title');
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
                    Forms\Components\Fieldset::make(__('invoices::filament/clusters/vendors/resources/refund.form.biller'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('invoices::filament/clusters/vendors/resources/refund.form.name')),
                            Forms\Components\TextInput::make('reference')
                                ->label(__('invoices::filament/clusters/vendors/resources/refund.form.reference')),
                            Forms\Components\TextInput::make('payment_reference')
                                ->label(__('invoices::filament/clusters/vendors/resources/refund.form.payment-reference')),
                            Forms\Components\TextInput::make('date')
                                ->label(__('invoices::filament/clusters/vendors/resources/refund.form.date')),
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
            'index'  => Pages\ListRefunds::route('/'),
            'create' => Pages\CreateRefund::route('/create'),
            'edit'   => Pages\EditRefund::route('/{record}/edit'),
            'view'   => Pages\ViewRefund::route('/{record}'),
        ];
    }
}
