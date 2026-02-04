<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\CncProgramResource;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;

/**
 * View CNC Program page
 */
class ViewCncProgram extends ViewRecord
{
    protected static string $resource = CncProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Quick action buttons for program workflow
            Action::make('startNextPart')
                ->label('Start Next Part')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->record->parts()->where('status', CncProgramPart::STATUS_PENDING)->exists())
                ->action(function () {
                    $part = $this->record->parts()
                        ->where('status', CncProgramPart::STATUS_PENDING)
                        ->orderBy('sheet_number')
                        ->first();

                    if ($part && $part->startRunning()) {
                        Notification::make()
                            ->title('Part Started')
                            ->body("Started: {$part->file_name}")
                            ->success()
                            ->send();
                    }
                }),

            Action::make('markProgramComplete')
                ->label('Mark Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== CncProgram::STATUS_COMPLETE)
                ->requiresConfirmation()
                ->modalHeading('Mark Program Complete')
                ->modalDescription('This will mark the program and all remaining parts as complete.')
                ->action(function () {
                    // Mark all pending/running parts as complete
                    $this->record->parts()
                        ->whereIn('status', [CncProgramPart::STATUS_PENDING, CncProgramPart::STATUS_RUNNING])
                        ->update([
                            'status' => CncProgramPart::STATUS_COMPLETE,
                            'completed_at' => now(),
                        ]);

                    $this->record->update(['status' => CncProgram::STATUS_COMPLETE]);

                    Notification::make()
                        ->title('Program Marked Complete')
                        ->success()
                        ->send();
                }),

            EditAction::make(),

            ChatterAction::make()
                ->setResource(static::$resource),

            DeleteAction::make(),
        ];
    }

    /**
     * Get activity plans for chatter
     */
    protected function getActivityPlans(): array
    {
        return [];
    }

    /**
     * Get header widgets showing program status/alerts
     */
    protected function getHeaderWidgets(): array
    {
        return [
            CncProgramResource\Widgets\CncProgramStatusWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
