<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;

use Webkul\Account\Filament\Resources\PaymentsResource\Pages\EditPayments as BaseEditPayments;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource;

/**
 * Edit Payments class
 *
 * @see \Filament\Resources\Resource
 */
class EditPayments extends BaseEditPayments
{
    protected static string $resource = PaymentsResource::class;
}
