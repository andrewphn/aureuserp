<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;

use Webkul\Account\Filament\Resources\PaymentsResource\Pages\ViewPayments as BaseViewPayments;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource;

/**
 * View Payments class
 *
 * @see \Filament\Resources\Resource
 */
class ViewPayments extends BaseViewPayments
{
    protected static string $resource = PaymentsResource::class;
}
