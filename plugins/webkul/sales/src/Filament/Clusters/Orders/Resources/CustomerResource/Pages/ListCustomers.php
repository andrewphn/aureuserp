<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ListPartners as BaseListCustomers;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource;

/**
 * List Customers class
 *
 * @see \Filament\Resources\Resource
 */
class ListCustomers extends BaseListCustomers
{
    protected static string $resource = CustomerResource::class;

    /**
     * Get the page title
     *
     * @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return __('sales::filament/clusters/orders/resources/customer/pages/list-customers.title');
    }

    /**
     * Get the header actions for the list page
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('sales::filament/clusters/orders/resources/customer/pages/list-customers.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
