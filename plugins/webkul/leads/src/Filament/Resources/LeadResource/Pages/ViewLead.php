<?php

namespace Webkul\Lead\Filament\Resources\LeadResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Lead\Filament\Resources\LeadResource;
use Webkul\Lead\Services\LeadConversionService;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('convert')
                ->label('Convert to Project')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Convert Lead to Project')
                ->modalDescription('This will create a Partner and Project from this lead. The lead will be marked as converted.')
                ->action(function () {
                    try {
                        $result = app(LeadConversionService::class)->convert($this->record);

                        Notification::make()
                            ->success()
                            ->title('Lead Converted Successfully')
                            ->body("Created Partner: {$result['partner']->name} and Project: {$result['project']->name}")
                            ->send();

                        return redirect()->to(
                            \Webkul\Project\Filament\Resources\ProjectResource::getUrl('view', ['record' => $result['project']])
                        );
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Conversion Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->canConvert()),
        ];
    }
}
