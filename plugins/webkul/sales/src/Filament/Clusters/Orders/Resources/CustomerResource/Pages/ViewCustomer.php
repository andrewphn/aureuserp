<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ViewPartner as BaseViewCustomer;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

/**
 * View Customer class
 *
 * @see \Filament\Resources\Resource
 */
class ViewCustomer extends BaseViewCustomer
{
    protected static string $resource = CustomerResource::class;

    /**
     * Get the sub-navigation position
     *
     * @return \Filament\Pages\Enums\SubNavigationPosition
     */
    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
