<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Resources\TaxResource as BaseTaxResource;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\TaxResource\Pages;
use Webkul\Invoice\Filament\Clusters\Configuration;

class TaxResource extends BaseTaxResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index'                           => Pages\ListTaxes::route('/'),
            'create'                          => Pages\CreateTax::route('/create'),
            'view'                            => Pages\ViewTax::route('/{record}'),
            'edit'                            => Pages\EditTax::route('/{record}/edit'),
            'manage-distribution-for-invoice' => Pages\ManageDistributionForInvoice::route('/{record}/manage-distribution-for-invoice'),
            'manage-distribution-for-refunds' => Pages\ManageDistributionForRefund::route('/{record}/manage-distribution-for-refunds'),
        ];
    }
}
