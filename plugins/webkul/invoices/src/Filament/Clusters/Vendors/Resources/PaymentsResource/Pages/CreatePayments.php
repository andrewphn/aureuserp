<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;

use Webkul\Account\Filament\Resources\PaymentsResource\Pages\CreatePayments as BaseCreatePayments;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource;

/**
 * Create Payments class
 *
 * @see \Filament\Resources\Resource
 */
class CreatePayments extends BaseCreatePayments
{
    protected static string $resource = PaymentsResource::class;

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        $data['partner_type'] = 'supplier';

        return $data;
    }
}
