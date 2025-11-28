<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

use Webkul\Account\Filament\Resources\PaymentTermResource\Pages\ManagePaymentDueTerm as BaseManagePaymentDueTerm;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource;

/**
 * Manage Payment Due Term class
 *
 * @see \Filament\Resources\Resource
 */
class ManagePaymentDueTerm extends BaseManagePaymentDueTerm
{
    protected static string $resource = PaymentTermResource::class;
}
