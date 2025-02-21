<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages\EditPayments as BaseEditPayments;

class EditPayments extends BaseEditPayments
{
    protected static string $resource = PaymentsResource::class;
}
