<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner as BaseCreateCustomer;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

/**
 * Create Customer class
 *
 * @see \Filament\Resources\Resource
 */
class CreateCustomer extends BaseCreateCustomer
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
        return __('sales::filament/clusters/orders/resources/customer/pages/create-customer.title');
    }

    /**
     * Mutate form data before creating the record
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        $data['sub_type'] = 'customer';

        return $data;
    }
}
