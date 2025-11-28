<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\EditPartner as BaseEditCustomer;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

/**
 * Edit Customer class
 *
 * @see \Filament\Resources\Resource
 */
class EditCustomer extends BaseEditCustomer
{
    protected static string $resource = CustomerResource::class;

    /**
     * Get the sub-navigation position for this page
     *
     * @return SubNavigationPosition
     */
    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * Get the page title
     *
     * @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return __('sales::filament/clusters/orders/resources/customer/pages/edit-customer.title');
    }
}
