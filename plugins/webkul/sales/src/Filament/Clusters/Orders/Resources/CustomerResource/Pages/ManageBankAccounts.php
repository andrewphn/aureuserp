<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource\Pages\ManageBankAccounts as BaseManageBankAccounts;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

/**
 * Manage Bank Accounts class
 *
 * @see \Filament\Resources\Resource
 */
class ManageBankAccounts extends BaseManageBankAccounts
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
