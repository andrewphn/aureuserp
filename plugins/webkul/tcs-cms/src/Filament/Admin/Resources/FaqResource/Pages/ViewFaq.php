<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\FaqResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\TcsCms\Filament\Admin\Resources\FaqResource;

class ViewFaq extends ViewRecord
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
