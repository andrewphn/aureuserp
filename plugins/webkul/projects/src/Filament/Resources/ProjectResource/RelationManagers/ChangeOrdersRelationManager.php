<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ChangeOrderResource;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Services\ChangeOrders\ChangeOrderService;

/**
 * Change Orders Relation Manager
 *
 * Displays and manages change orders for a specific project.
 */
class ChangeOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'changeOrders';

    protected static ?string $title = 'Change Orders';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('reason')
                    ->label('Reason')
                    ->options(ChangeOrder::getReasons())
                    ->required()
                    ->native(false),

                Select::make('affected_stage')
                    ->label('Affected Stage')
                    ->options([
                        'design' => 'Design',
                        'ordering' => 'Ordering',
                        'production' => 'Production',
                        'installation' => 'Installation',
                    ])
                    ->native(false),

                TextInput::make('price_delta')
                    ->label('Price Impact ($)')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->step('0.01'),

                TextInput::make('labor_hours_delta')
                    ->label('Labor Hours Impact')
                    ->numeric()
                    ->suffix('hours')
                    ->default(0)
                    ->step('0.5'),

                Select::make('status')
                    ->label('Status')
                    ->options(ChangeOrder::getStatuses())
                    ->default(ChangeOrder::STATUS_DRAFT)
                    ->required()
                    ->native(false)
                    ->disabled(fn ($record) => $record?->isComplete()),

                Hidden::make('requested_by')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('change_order_number')
                    ->label('CO #')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->title),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ChangeOrder::getReasons()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'client_request' => 'info',
                        'field_condition' => 'warning',
                        'design_error' => 'danger',
                        'material_substitution' => 'gray',
                        'scope_addition' => 'success',
                        'scope_removal' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ChangeOrder::getStatuses()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'applied' => 'info',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('price_delta')
                    ->label('Price Impact')
                    ->money('USD')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->sortable(),

                TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('requested_at')
                    ->label('Requested')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ChangeOrder::getStatuses())
                    ->multiple(),

                SelectFilter::make('reason')
                    ->options(ChangeOrder::getReasons())
                    ->multiple(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('New Change Order')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['requested_by'] = Auth::id();
                        $data['requested_at'] = now();
                        $data['status'] = $data['status'] ?? ChangeOrder::STATUS_DRAFT;

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Change Order Created')
                            ->body('Change order has been created successfully.'),
                    ),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn ($record) => ChangeOrderResource::getUrl('view', ['record' => $record])),

                \Filament\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->canBeEdited()),

                \Filament\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === ChangeOrder::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => ChangeOrder::STATUS_PENDING_APPROVAL]);
                        Notification::make()
                            ->success()
                            ->title('Change Order Submitted')
                            ->body('Change order has been submitted for approval.')
                            ->send();
                    }),

                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canBeApproved())
                    ->form([
                        Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => ChangeOrder::STATUS_APPROVED,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);
                        Notification::make()
                            ->success()
                            ->title('Change Order Approved')
                            ->body('Change order has been approved.')
                            ->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canBeApproved())
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => ChangeOrder::STATUS_REJECTED,
                            'rejected_by' => Auth::id(),
                            'rejected_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()
                            ->warning()
                            ->title('Change Order Rejected')
                            ->body('Change order has been rejected.')
                            ->send();
                    }),

                \Filament\Actions\Action::make('apply')
                    ->label('Apply')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => $record->canBeApplied())
                    ->requiresConfirmation()
                    ->modalDescription('This will apply all changes in this change order to the project.')
                    ->action(function ($record) {
                        try {
                            $service = app(ChangeOrderService::class);
                            $service->apply($record);

                            Notification::make()
                                ->success()
                                ->title('Changes Applied')
                                ->body('All changes have been applied to the project.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error Applying Changes')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                \Filament\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->status === ChangeOrder::STATUS_DRAFT),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
