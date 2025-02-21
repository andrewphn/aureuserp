<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\PartnerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
