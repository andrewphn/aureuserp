<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

use Webkul\Account\Filament\Resources\PaymentTermResource\Pages\ViewPaymentTerm as BaseViewPaymentTerm;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource;

/**
 * View Payment Term class
 *
 * @see \Filament\Resources\Resource
 */
class ViewPaymentTerm extends BaseViewPaymentTerm
{
    protected static string $resource = PaymentTermResource::class;
}
