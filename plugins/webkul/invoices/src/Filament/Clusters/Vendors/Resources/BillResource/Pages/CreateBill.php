<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages;

use Webkul\Account\Filament\Resources\BillResource\Pages\CreateBill as BaseCreateBill;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;

/**
 * Create Bill class
 *
 * @see \Filament\Resources\Resource
 */
class CreateBill extends BaseCreateBill
{
    protected static string $resource = BillResource::class;
}
