<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RhinoExtractionReviewResource\Pages;
use App\Models\RhinoExtractionReview;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rhino Extraction Review Resource
 *
 * FilamentPHP admin UI for reviewing and approving cabinet extractions from Rhino.
 * Features:
 * - List pending reviews with confidence badges
 * - Edit form for dimension corrections
 * - Approve/reject actions
 * - Side-by-side comparison for sync conflicts
 */
class RhinoExtractionReviewResource extends Resource
{
    protected static ?string $model = RhinoExtractionReview::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Rhino Reviews';

    protected static string|\UnitEnum|null $navigationGroup = 'Rhino Integration';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'rhino-reviews';

    /**
     * Navigation badge showing pending review count
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', RhinoExtractionReview::STATUS_PENDING)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cabinet_number')
                    ->label('Cabinet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rhino_group_name')
                    ->label('Rhino Group')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->badge()
                    ->color(fn (RhinoExtractionReview $record): string => $record->getConfidenceColor())
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                TextColumn::make('review_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RhinoExtractionReview::TYPE_LOW_CONFIDENCE => 'danger',
                        RhinoExtractionReview::TYPE_DIMENSION_MISMATCH => 'warning',
                        RhinoExtractionReview::TYPE_SYNC_CONFLICT => 'info',
                        RhinoExtractionReview::TYPE_MISSING_DATA => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        RhinoExtractionReview::TYPE_LOW_CONFIDENCE => 'Low Confidence',
                        RhinoExtractionReview::TYPE_DIMENSION_MISMATCH => 'Dimension Mismatch',
                        RhinoExtractionReview::TYPE_SYNC_CONFLICT => 'Sync Conflict',
                        RhinoExtractionReview::TYPE_MISSING_DATA => 'Missing Data',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RhinoExtractionReview::STATUS_PENDING => 'warning',
                        RhinoExtractionReview::STATUS_APPROVED => 'success',
                        RhinoExtractionReview::STATUS_REJECTED => 'danger',
                        RhinoExtractionReview::STATUS_AUTO_APPROVED => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        RhinoExtractionReview::STATUS_PENDING => 'Pending',
                        RhinoExtractionReview::STATUS_APPROVED => 'Approved',
                        RhinoExtractionReview::STATUS_REJECTED => 'Rejected',
                        RhinoExtractionReview::STATUS_AUTO_APPROVED => 'Auto Approved',
                    ])
                    ->default(RhinoExtractionReview::STATUS_PENDING),
                SelectFilter::make('review_type')
                    ->options([
                        RhinoExtractionReview::TYPE_LOW_CONFIDENCE => 'Low Confidence',
                        RhinoExtractionReview::TYPE_DIMENSION_MISMATCH => 'Dimension Mismatch',
                        RhinoExtractionReview::TYPE_SYNC_CONFLICT => 'Sync Conflict',
                        RhinoExtractionReview::TYPE_MISSING_DATA => 'Missing Data',
                    ]),
                SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RhinoExtractionReview $record) => $record->isPending())
                    ->form([
                        Section::make('Dimension Corrections')
                            ->description('Override extracted dimensions if needed')
                            ->schema([
                                TextInput::make('width')
                                    ->label('Width (inches)')
                                    ->numeric()
                                    ->minValue(6)
                                    ->maxValue(96)
                                    ->default(fn (RhinoExtractionReview $record) =>
                                        $record->extraction_data['width'] ?? null
                                    ),
                                TextInput::make('height')
                                    ->label('Height (inches)')
                                    ->numeric()
                                    ->minValue(12)
                                    ->maxValue(108)
                                    ->default(fn (RhinoExtractionReview $record) =>
                                        $record->extraction_data['height'] ?? null
                                    ),
                                TextInput::make('depth')
                                    ->label('Depth (inches)')
                                    ->numeric()
                                    ->minValue(6)
                                    ->maxValue(36)
                                    ->default(fn (RhinoExtractionReview $record) =>
                                        $record->extraction_data['depth'] ?? null
                                    ),
                            ])
                            ->columns(3),
                        Textarea::make('notes')
                            ->label('Reviewer Notes')
                            ->rows(2),
                    ])
                    ->action(function (RhinoExtractionReview $record, array $data): void {
                        $corrections = array_filter([
                            'width' => $data['width'] ?? null,
                            'height' => $data['height'] ?? null,
                            'depth' => $data['depth'] ?? null,
                        ], fn ($v) => $v !== null);

                        $record->approve(
                            auth()->id(),
                            $corrections,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title('Review Approved')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RhinoExtractionReview $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (RhinoExtractionReview $record, array $data): void {
                        $record->reject(auth()->id(), $data['reason']);

                        Notification::make()
                            ->title('Review Rejected')
                            ->warning()
                            ->send();
                    }),
                Action::make('forceErp')
                    ->label('Use ERP Values')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->visible(fn (RhinoExtractionReview $record) =>
                        $record->isPending() &&
                        $record->review_type === RhinoExtractionReview::TYPE_SYNC_CONFLICT
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Push ERP Values to Rhino')
                    ->modalDescription('This will update the Rhino drawing with the current ERP cabinet dimensions.')
                    ->action(function (RhinoExtractionReview $record): void {
                        $syncService = app(\App\Services\RhinoSyncService::class);
                        $result = $syncService->forceSync($record->id, 'erp', auth()->id());

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
                    }),
                Action::make('forceRhino')
                    ->label('Use Rhino Values')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->visible(fn (RhinoExtractionReview $record) =>
                        $record->isPending() &&
                        $record->review_type === RhinoExtractionReview::TYPE_SYNC_CONFLICT
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Pull Rhino Values to ERP')
                    ->modalDescription('This will update the ERP cabinet with the Rhino drawing dimensions.')
                    ->action(function (RhinoExtractionReview $record): void {
                        $syncService = app(\App\Services\RhinoSyncService::class);
                        $result = $syncService->forceSync($record->id, 'rhino', auth()->id());

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
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfoSection::make('Review Status')
                    ->schema([
                        TextEntry::make('cabinet_number')->label('Cabinet'),
                        TextEntry::make('rhino_group_name')->label('Rhino Group'),
                        TextEntry::make('project.name')->label('Project'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                RhinoExtractionReview::STATUS_PENDING => 'warning',
                                RhinoExtractionReview::STATUS_APPROVED => 'success',
                                RhinoExtractionReview::STATUS_REJECTED => 'danger',
                                RhinoExtractionReview::STATUS_AUTO_APPROVED => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('confidence_score')
                            ->label('Confidence')
                            ->formatStateUsing(fn ($state) => number_format($state, 1) . '%'),
                        TextEntry::make('review_type')->label('Review Type'),
                    ])
                    ->columns(3),
                InfoSection::make('Extracted Dimensions')
                    ->schema([
                        TextEntry::make('extraction_data.width')
                            ->label('Width (inches)')
                            ->placeholder('Not extracted'),
                        TextEntry::make('extraction_data.height')
                            ->label('Height (inches)')
                            ->placeholder('Not extracted'),
                        TextEntry::make('extraction_data.depth')
                            ->label('Depth (inches)')
                            ->placeholder('Not extracted'),
                    ])
                    ->columns(3),
                InfoSection::make('Sync Conflict Comparison')
                    ->schema([
                        TextEntry::make('erp_data')
                            ->label('ERP Values')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'N/A'),
                        TextEntry::make('rhino_data')
                            ->label('Rhino Values')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'N/A'),
                    ])
                    ->columns(2)
                    ->visible(fn (RhinoExtractionReview $record) =>
                        $record->review_type === RhinoExtractionReview::TYPE_SYNC_CONFLICT
                    ),
                InfoSection::make('AI Interpretation')
                    ->schema([
                        TextEntry::make('ai_interpretation')
                            ->label('AI Analysis')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'No AI interpretation available'),
                    ])
                    ->collapsible()
                    ->visible(fn (RhinoExtractionReview $record) => !empty($record->ai_interpretation)),
                InfoSection::make('Review Details')
                    ->schema([
                        TextEntry::make('reviewer.name')->label('Reviewed By'),
                        TextEntry::make('reviewed_at')->label('Reviewed At')->dateTime(),
                        TextEntry::make('reviewer_notes')->label('Notes'),
                        TextEntry::make('reviewer_corrections')
                            ->label('Corrections Applied')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'None'),
                    ])
                    ->columns(2)
                    ->visible(fn (RhinoExtractionReview $record) => !$record->isPending()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRhinoExtractionReviews::route('/'),
            'view' => Pages\ViewRhinoExtractionReview::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['project:id,name', 'reviewer:id,name', 'extractionJob:id,uuid,status']);
    }
}
