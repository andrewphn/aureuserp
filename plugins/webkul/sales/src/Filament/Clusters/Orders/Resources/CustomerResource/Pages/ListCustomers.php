<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Pages\SubNavigationPosition;
use Webkul\Contact\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListCustomers;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

class ListCustomers extends BaseListCustomers
{
    protected static string $resource = CustomerResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }
}
