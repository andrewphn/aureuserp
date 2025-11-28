<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

use Webkul\Account\Filament\Resources\PaymentTermResource\Pages\CreatePaymentTerm as BaseCreatePaymentTerm;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource;

/**
 * Create Payment Term class
 *
 * @see \Filament\Resources\Resource
 */
class CreatePaymentTerm extends BaseCreatePaymentTerm
{
    protected static string $resource = PaymentTermResource::class;
}
