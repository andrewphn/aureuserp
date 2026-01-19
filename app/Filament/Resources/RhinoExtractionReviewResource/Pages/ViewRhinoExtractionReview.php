<?php

namespace App\Filament\Resources\RhinoExtractionReviewResource\Pages;

use App\Filament\Resources\RhinoExtractionReviewResource;
use App\Models\RhinoExtractionReview;
use App\Services\RhinoSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRhinoExtractionReview extends ViewRecord
{
    protected static string $resource = RhinoExtractionReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->isPending())
                ->form([
                    Section::make('Dimension Corrections')
                        ->description('Override extracted dimensions if needed')
                        ->schema([
                            TextInput::make('width')
                                ->label('Width (inches)')
                                ->numeric()
                                ->minValue(6)
                                ->maxValue(96)
                                ->default(fn () =>
                                    $this->record->extraction_data['width'] ?? null
                                ),
                            TextInput::make('height')
                                ->label('Height (inches)')
                                ->numeric()
                                ->minValue(12)
                                ->maxValue(108)
                                ->default(fn () =>
                                    $this->record->extraction_data['height'] ?? null
                                ),
                            TextInput::make('depth')
                                ->label('Depth (inches)')
                                ->numeric()
                                ->minValue(6)
                                ->maxValue(36)
                                ->default(fn () =>
                                    $this->record->extraction_data['depth'] ?? null
                                ),
                        ])
                        ->columns(3),
                    Textarea::make('notes')
                        ->label('Reviewer Notes')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $corrections = array_filter([
                        'width' => $data['width'] ?? null,
                        'height' => $data['height'] ?? null,
                        'depth' => $data['depth'] ?? null,
                    ], fn ($v) => $v !== null);

                    $this->record->approve(
                        auth()->id(),
                        $corrections,
                        $data['notes'] ?? null
                    );

                    Notification::make()
                        ->title('Review Approved')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'reviewer_id', 'reviewed_at']);
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->isPending())
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->reject(auth()->id(), $data['reason']);

                    Notification::make()
                        ->title('Review Rejected')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'reviewer_id', 'reviewed_at']);
                }),
            Action::make('forceErp')
                ->label('Push ERP to Rhino')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->visible(fn () =>
                    $this->record->isPending() &&
                    $this->record->review_type === RhinoExtractionReview::TYPE_SYNC_CONFLICT
                )
                ->requiresConfirmation()
                ->modalHeading('Push ERP Values to Rhino')
                ->modalDescription('This will update the Rhino drawing with the current ERP cabinet dimensions.')
                ->action(function (): void {
                    $syncService = app(RhinoSyncService::class);
                    $result = $syncService->forceSync($this->record->id, 'erp', auth()->id());

                    if ($result['success']) {
                        Notification::make()
                            ->title('ERP Values Pushed to Rhino')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($result['error'] ?? 'Unknown error')
                            ->danger()
                            ->send();
                    }

                    $this->refreshFormData(['status', 'reviewer_id', 'reviewed_at']);
                }),
            Action::make('forceRhino')
                ->label('Pull Rhino to ERP')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->visible(fn () =>
                    $this->record->isPending() &&
                    $this->record->review_type === RhinoExtractionReview::TYPE_SYNC_CONFLICT
                )
                ->requiresConfirmation()
                ->modalHeading('Pull Rhino Values to ERP')
                ->modalDescription('This will update the ERP cabinet with the Rhino drawing dimensions.')
                ->action(function (): void {
                    $syncService = app(RhinoSyncService::class);
                    $result = $syncService->forceSync($this->record->id, 'rhino', auth()->id());

                    if ($result['success']) {
                        Notification::make()
                            ->title('Rhino Values Pulled to ERP')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($result['error'] ?? 'Unknown error')
                            ->danger()
                            ->send();
                    }

                    $this->refreshFormData(['status', 'reviewer_id', 'reviewed_at']);
                }),
        ];
    }
}
