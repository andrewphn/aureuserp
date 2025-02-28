<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Illuminate\Database\Eloquent\Model;
use Webkul\Account\Filament\Resources\CreditNoteResource as BaseCreditNoteResource;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;
use Webkul\Invoice\Filament\Clusters\Customer;

class CreditNotesResource extends BaseCreditNoteResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Customer::class;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Credit Note');
    }

    public static function getNavigationLabel(): string
    {
        return __('Credit Notes');
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCreditNotes::route('/'),
            'create' => Pages\CreateCreditNotes::route('/create'),
            'edit'   => Pages\EditCreditNotes::route('/{record}/edit'),
            'view'   => Pages\ViewCreditNote::route('/{record}'),
        ];
    }
}
