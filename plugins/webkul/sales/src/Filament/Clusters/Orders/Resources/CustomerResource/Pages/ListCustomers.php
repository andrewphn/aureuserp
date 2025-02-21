<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListCustomers;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

class ListCustomers extends BaseListCustomers
{
    protected static string $resource = CustomerResource::class;
}
