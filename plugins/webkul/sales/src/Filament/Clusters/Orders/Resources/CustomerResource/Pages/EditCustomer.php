<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Webkul\Contact\Filament\Resources\PartnerResource\Pages\EditPartner as BaseEditCustomer;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

class EditCustomer extends BaseEditCustomer
{
    protected static string $resource = CustomerResource::class;
}
