<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\CabinetCalculationAuditResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Clusters\Configurations\Resources\CabinetCalculationAuditResource;
use Webkul\Project\Models\CabinetCalculationAudit;

class ViewCabinetCalculationAudit extends ViewRecord
{
    protected static string $resource = CabinetCalculationAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('override')
                ->label('Override Discrepancy')
                ->icon('heroicon-o-hand-raised')
                ->color('warning')
                ->visible(fn () => $this->record->needsAttention())
                ->requiresConfirmation()
                ->modalHeading('Override Audit Discrepancy')
                ->modalDescription('This will mark the audit as overridden. Please provide a reason for the override.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('override_reason')
                        ->label('Override Reason')
                        ->required()
                        ->placeholder('Explain why this discrepancy is acceptable...'),
                ])
                ->action(function (array $data) {
                    $this->record->override(Auth::user(), $data['override_reason']);

                    Notification::make()
                        ->success()
                        ->title('Audit Overridden')
                        ->body('The discrepancy has been marked as overridden.')
                        ->send();
                }),

            Action::make('recalculate')
                ->label('Recalculate Cabinet')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->visible(fn () => $this->record->cabinet !== null)
                ->requiresConfirmation()
                ->modalHeading('Recalculate Cabinet Values')
                ->modalDescription('This will recalculate the cabinet values based on the current template and create a new audit.')
                ->action(function () {
                    $service = app(\App\Services\CabinetCalculationAuditService::class);
                    $result = $service->recalculateAndAudit($this->record->cabinet, Auth::user());

                    Notification::make()
                        ->success()
                        ->title('Cabinet Recalculated')
                        ->body(sprintf(
                            'New audit created with status: %s',
                            $result['audit']->status_label
                        ))
                        ->send();

                    return redirect()->to(
                        CabinetCalculationAuditResource::getUrl('view', ['record' => $result['audit']->id])
                    );
                }),

            Action::make('view_cabinet')
                ->label('View Cabinet')
                ->icon('heroicon-o-cube')
                ->color('gray')
                ->visible(fn () => $this->record->cabinet !== null)
                ->url(fn () => route('filament.admin.resources.cabinets.view', [
                    'record' => $this->record->cabinet_id,
                ])),
        ];
    }
}
