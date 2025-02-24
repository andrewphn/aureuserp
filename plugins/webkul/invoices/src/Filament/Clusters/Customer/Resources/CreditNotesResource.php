<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;
use Webkul\Account\Filament\Resources\InvoiceResource as BaseInvoiceResource;

class CreditNotesResource extends BaseInvoiceResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Customer::class;

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/credit-note.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/credit-note.navigation.title');
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
                    Forms\Components\Fieldset::make(__('invoices::filament/clusters/customers/resources/credit-note.form.fieldset.credit-note.title'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->placeholder('RINV/2025/00001')
                                ->label(__('invoices::filament/clusters/customers/resources/credit-note.form.fieldset.credit-note.fields.customer-credit-note')),
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
            'index'  => Pages\ListCreditNotes::route('/'),
            'create' => Pages\CreateCreditNotes::route('/create'),
            'edit'   => Pages\EditCreditNotes::route('/{record}/edit'),
            'view'   => Pages\EditCreditNotes::route('/{record}'),
        ];
    }
}
