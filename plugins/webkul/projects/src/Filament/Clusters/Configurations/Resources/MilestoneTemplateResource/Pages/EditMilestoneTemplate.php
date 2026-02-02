<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\Actions\GenerateTasksWithAiAction;

class EditMilestoneTemplate extends EditRecord
{
    protected static string $resource = MilestoneTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateTasksWithAiAction::make()
                ->record($this->getRecord()),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Template deleted')
                        ->body('The milestone template has been deleted.'),
                ),
        ];
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title('Template updated')
            ->body('The milestone template has been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
