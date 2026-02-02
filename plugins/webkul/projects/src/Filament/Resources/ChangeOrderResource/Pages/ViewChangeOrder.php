<?php

namespace Webkul\Project\Filament\Resources\ChangeOrderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ChangeOrderResource;
use Webkul\Project\Filament\Resources\ChangeOrderResource\Actions\PrintChangeOrderAction;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Services\ChangeOrders\ChangeOrderService;

class ViewChangeOrder extends ViewRecord
{
    protected static string $resource = ChangeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrintChangeOrderAction::make()
                ->record($this->record),

            EditAction::make()
                ->visible(fn () => $this->record->canBeEdited()),

            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->status === ChangeOrder::STATUS_DRAFT)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => ChangeOrder::STATUS_PENDING_APPROVAL]);
                    Notification::make()
                        ->success()
                        ->title('Change Order Submitted')
                        ->body('Change order has been submitted for approval.')
                        ->send();
                }),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canBeApproved())
                ->form([
                    Textarea::make('approval_notes')
                        ->label('Approval Notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
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

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canBeApproved())
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
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

            Action::make('apply')
                ->label('Apply Changes')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn () => $this->record->canBeApplied())
                ->requiresConfirmation()
                ->modalDescription('This will apply all changes in this change order to the project. This action cannot be undone.')
                ->action(function () {
                    try {
                        $service = app(ChangeOrderService::class);
                        $service->apply($this->record);

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

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn () => !$this->record->isComplete())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => ChangeOrder::STATUS_CANCELLED]);
                    Notification::make()
                        ->info()
                        ->title('Change Order Cancelled')
                        ->body('Change order has been cancelled.')
                        ->send();
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === ChangeOrder::STATUS_DRAFT),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Section::make('Change Order Details')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('change_order_number')
                                    ->label('CO Number')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color('primary'),

                                TextEntry::make('project.name')
                                    ->label('Project')
                                    ->url(fn ($record) => $record->project_id
                                        ? route('filament.admin.resources.projects.projects.view', $record->project_id)
                                        : null)
                                    ->color('primary'),

                                TextEntry::make('status')
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
                            ]),

                        TextEntry::make('title')
                            ->label('Title')
                            ->weight(FontWeight::SemiBold)
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description provided'),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reason')
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

                                TextEntry::make('affected_stage')
                                    ->label('Affected Stage')
                                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Not specified')
                                    ->placeholder('Not specified'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('price_delta')
                                    ->label('Price Impact')
                                    ->money('USD')
                                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),

                                TextEntry::make('labor_hours_delta')
                                    ->label('Labor Hours Impact')
                                    ->suffix(' hours')
                                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                            ]),
                    ]),

                Section::make('Status & Tracking')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('requester.name')
                            ->label('Requested By')
                            ->icon('heroicon-o-user'),

                        TextEntry::make('requested_at')
                            ->label('Requested At')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('approver.name')
                            ->label('Approved By')
                            ->icon('heroicon-o-check')
                            ->visible(fn ($record) => $record->approved_by !== null),

                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime('M j, Y g:i A')
                            ->visible(fn ($record) => $record->approved_at !== null),

                        TextEntry::make('approval_notes')
                            ->label('Approval Notes')
                            ->visible(fn ($record) => !empty($record->approval_notes)),

                        TextEntry::make('rejecter.name')
                            ->label('Rejected By')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')
                            ->visible(fn ($record) => $record->rejected_by !== null),

                        TextEntry::make('rejected_at')
                            ->label('Rejected At')
                            ->dateTime('M j, Y g:i A')
                            ->color('danger')
                            ->visible(fn ($record) => $record->rejected_at !== null),

                        TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->color('danger')
                            ->visible(fn ($record) => !empty($record->rejection_reason)),

                        TextEntry::make('applier.name')
                            ->label('Applied By')
                            ->icon('heroicon-o-play')
                            ->visible(fn ($record) => $record->applied_by !== null),

                        TextEntry::make('applied_at')
                            ->label('Applied At')
                            ->dateTime('M j, Y g:i A')
                            ->visible(fn ($record) => $record->applied_at !== null),
                    ]),

                Section::make('Change Lines')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextEntry::make('entity_type')
                                            ->label('Entity Type'),

                                        TextEntry::make('entity_id')
                                            ->label('Entity ID'),

                                        TextEntry::make('field_name')
                                            ->label('Field'),

                                        TextEntry::make('old_value')
                                            ->label('Old Value')
                                            ->placeholder('(empty)'),

                                        TextEntry::make('new_value')
                                            ->label('New Value')
                                            ->weight(FontWeight::Bold),
                                    ]),
                            ])
                            ->columns(1)
                            ->visible(fn ($record) => $record->lines->count() > 0),

                        TextEntry::make('no_lines')
                            ->label('')
                            ->state('No change lines have been added yet.')
                            ->visible(fn ($record) => $record->lines->count() === 0),
                    ]),
            ]);
    }
}
