<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages\ViewPayments as BaseViewPayments;

class ViewPayments extends BaseViewPayments
{
    protected static string $resource = PaymentsResource::class;
}
