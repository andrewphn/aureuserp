<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ChangeOrderResource\Pages\CreateChangeOrder;
use Webkul\Project\Filament\Resources\ChangeOrderResource\Pages\EditChangeOrder;
use Webkul\Project\Filament\Resources\ChangeOrderResource\Pages\ListChangeOrders;
use Webkul\Project\Filament\Resources\ChangeOrderResource\Pages\ViewChangeOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\ChangeOrders\ChangeOrderService;

/**
 * Change Order Resource
 *
 * FilamentPHP resource for managing change orders across all projects.
 */
class ChangeOrderResource extends Resource
{
    protected static ?string $model = ChangeOrder::class;

    protected static ?string $slug = 'project/change-orders';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    // Hide from main navigation - accessible via Project details
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return 'Change Orders';
    }

    public static function getNavigationGroup(): string
    {
        return 'Projects';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', [
            ChangeOrder::STATUS_PENDING_APPROVAL,
            ChangeOrder::STATUS_APPROVED,
        ])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'change_order_number', 'description', 'project.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Project' => $record->project?->name ?? '-',
            'Status' => ucfirst(str_replace('_', ' ', $record->status)),
            'CO #' => $record->change_order_number,
        ];
    }

    /**
     * Define the form schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Change Order Details')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('project_id')
                                    ->label('Project')
                                    ->relationship('project', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null),

                                TextInput::make('change_order_number')
                                    ->label('CO Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated'),
                            ]),

                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
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
                            ]),

                        Grid::make(2)
                            ->schema([
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
                            ]),
                    ]),

                Section::make('Status & Tracking')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ChangeOrder::getStatuses())
                            ->default(ChangeOrder::STATUS_DRAFT)
                            ->required()
                            ->native(false)
                            ->disabled(fn ($record) => $record?->isComplete()),

                        Placeholder::make('requested_by_name')
                            ->label('Requested By')
                            ->content(fn ($record) => $record?->requester?->name ?? 'Current User'),

                        Placeholder::make('requested_at_display')
                            ->label('Requested At')
                            ->content(fn ($record) => $record?->requested_at?->format('M j, Y g:i A') ?? 'Now'),

                        Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->rows(3)
                            ->visible(fn ($record) => $record?->status === ChangeOrder::STATUS_APPROVED),

                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->visible(fn ($record) => $record?->status === ChangeOrder::STATUS_REJECTED),
                    ]),

                Hidden::make('requested_by')
                    ->default(fn () => Auth::id()),
            ]);
    }

    /**
     * Define the table schema
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('change_order_number')
                    ->label('CO #')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->project?->name),

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

                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ChangeOrder::getStatuses())
                    ->multiple(),

                SelectFilter::make('reason')
                    ->options(ChangeOrder::getReasons())
                    ->multiple(),

                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->canBeEdited()),

                Action::make('print')
                    ->label(__('projects::filament/resources/change-order.actions.print.label'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(function (ChangeOrder $record) {
                        $record->load(['project.partner', 'requester', 'approver', 'rejecter', 'lines']);

                        $html = view('projects::filament.pages.print-change-order', [
                            'record' => $record,
                        ])->render();

                        $pdf = Pdf::loadHTML($html)
                            ->setPaper('letter', 'portrait')
                            ->setOption('defaultFont', 'Arial')
                            ->setOption('isHtml5ParserEnabled', true)
                            ->setOption('isRemoteEnabled', true);

                        $filename = sprintf(
                            'Change-Order-%s-%s.pdf',
                            $record->change_order_number,
                            now()->format('Y-m-d')
                        );

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename);
                    }),

                Action::make('submit')
                    ->label('Submit for Approval')
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

                Action::make('approve')
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

                Action::make('reject')
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

                Action::make('apply')
                    ->label('Apply Changes')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => $record->canBeApplied())
                    ->requiresConfirmation()
                    ->modalDescription('This will apply all changes in this change order to the project. This action cannot be undone.')
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->can('delete', ChangeOrder::class)),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangeOrders::route('/'),
            'create' => CreateChangeOrder::route('/create'),
            'view' => ViewChangeOrder::route('/{record}'),
            'edit' => EditChangeOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project', 'requester', 'approver']);
    }
}
