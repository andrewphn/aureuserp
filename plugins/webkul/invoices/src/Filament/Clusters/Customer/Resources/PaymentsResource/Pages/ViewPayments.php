<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource;
use Webkul\Account\Filament\Resources\PaymentsResource\Pages\ViewPayments as BaseViewPayments;

class ViewPayments extends BaseViewPayments
{
    protected static string $resource = PaymentsResource::class;
}
