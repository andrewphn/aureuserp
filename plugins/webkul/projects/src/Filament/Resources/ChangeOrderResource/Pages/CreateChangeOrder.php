<?php

namespace Webkul\Project\Filament\Resources\ChangeOrderResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ChangeOrderResource;
use Webkul\Project\Models\ChangeOrder;

class CreateChangeOrder extends CreateRecord
{
    protected static string $resource = ChangeOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();
        $data['requested_at'] = now();
        $data['status'] = $data['status'] ?? ChangeOrder::STATUS_DRAFT;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Change Order Created')
            ->body("Change order {$this->record->change_order_number} has been created.");
    }
}
