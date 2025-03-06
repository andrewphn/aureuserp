<?php

namespace Webkul\Website\Filament\Front\Clusters\Account\Resources\OrderResource\Pages;

use Webkul\Website\Filament\Front\Clusters\Account\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
