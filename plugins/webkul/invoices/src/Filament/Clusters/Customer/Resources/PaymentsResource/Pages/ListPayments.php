<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages\ListPayments as BaseListPayments;

class ListPayments extends BaseListPayments
{
    protected static string $resource = PaymentsResource::class;
}
