<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\CreatePartner as BaseCreateCustomer;

class CreateCustomer extends BaseCreateCustomer
{
    protected static string $resource = CustomerResource::class;
}
