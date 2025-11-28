<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource\Pages\ManageContacts as BaseManageContacts;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

/**
 * Manage Contacts class
 *
 * @see \Filament\Resources\Resource
 */
class ManageContacts extends BaseManageContacts
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
