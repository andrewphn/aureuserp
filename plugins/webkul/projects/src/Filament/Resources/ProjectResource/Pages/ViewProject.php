<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Support\Models\ActivityPlan;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Set active context to notify the footer which project is being viewed
        // NO form data is passed to prevent EntityStore sync
        $this->dispatch('set-active-context', [
            'entityType' => 'project',
            'entityId' => $this->record->id,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource)
                ->setActivityPlans($this->getActivityPlans()),
            Action::make('uploadPdf')
                ->label('Upload PDFs')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('file_path')
                        ->label('PDF Document')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(51200)
                        ->disk('public')
                        ->directory('pdf-documents')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $filename = is_string($state) ? basename($state) : (is_object($state) ? $state->getClientOriginalName() : null);
                                if ($filename) {
                                    $set('file_name', $filename);
                                }
                            }
                        }),

                    TextInput::make('file_name')
                        ->label('File Name')
                        ->placeholder('Auto-filled from uploaded file')
                        ->disabled()
                        ->dehydrated(),

                    Select::make('document_type')
                        ->label('Document Type')
                        ->options([
                            'drawing' => 'Architectural Drawing',
                            'blueprint' => 'Blueprint',
                            'specification' => 'Specification',
                            'contract' => 'Contract',
                            'permit' => 'Permit',
                            'photo' => 'Photo/Image',
                            'other' => 'Other',
                        ])
                        ->default('drawing')
                        ->required()
                        ->helperText('Select the type of document you are uploading'),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->helperText('Optional notes about this document'),

                    Checkbox::make('is_primary_reference')
                        ->label('Set as Primary Reference')
                        ->helperText('Mark this document as the primary reference for the project (will be displayed in project overview)'),
                ])
                ->action(function (array $data) {
                    // Get file size
                    if (empty($data['file_size']) && !empty($data['file_path'])) {
                        $data['file_size'] = Storage::disk('public')->size($data['file_path']);
                    }

                    // Set mime type
                    if (empty($data['mime_type'])) {
                        $data['mime_type'] = 'application/pdf';
                    }

                    // If this document is being set as primary reference, unmark any existing primary references
                    if (!empty($data['is_primary_reference'])) {
                        $this->record->pdfDocuments()
                            ->where('is_primary_reference', true)
                            ->update(['is_primary_reference' => false]);
                    }

                    // Create PDF document
                    $this->record->pdfDocuments()->create([
                        'file_path' => $data['file_path'],
                        'file_name' => $data['file_name'] ?? basename($data['file_path']),
                        'file_size' => $data['file_size'],
                        'mime_type' => $data['mime_type'],
                        'document_type' => $data['document_type'],
                        'notes' => $data['notes'] ?? null,
                        'is_primary_reference' => $data['is_primary_reference'] ?? false,
                        'uploaded_by' => Auth::id(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('PDF uploaded successfully')
                        ->send();
                }),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('webkul-project::filament/resources/project/pages/view-project.header-actions.delete.notification.title'))
                        ->body(__('webkul-project::filament/resources/project/pages/view-project.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load relationships for hierarchical display
        $this->record->load([
            'rooms.locations.cabinetRuns.cabinets',
            'pdfDocuments.pages'
        ]);

        return $data;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // Set 3 columns for 2/3 + 1/3 split
            ->schema([
                // Left Column: Project Overview with Gallery (2/3 width)
                Section::make('Project Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Project Name')
                                    ->icon('heroicon-o-folder')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'on_hold' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('partner.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user'),
                            ]),

                        // Primary Reference Gallery
                        ViewEntry::make('primary_reference_gallery')
                            ->label('Primary Reference')
                            ->view('filament.infolists.components.primary-reference-gallery')
                            ->state(fn ($record) => $record->pdfDocuments()->where('is_primary_reference', true)->first())
                            ->visible(fn ($record) => $record->pdfDocuments()->where('is_primary_reference', true)->exists())
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpan(2), // Takes 2/3 of the width

                // Right Column: Project Breakdown (1/3 width)
                Section::make('Project Breakdown')
                    ->schema([
                        RepeatableEntry::make('rooms')
                            ->label('')
                            ->schema([
                                // Room Header
                                Section::make(fn ($record) => "Room: {$record->name}")
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('room_type')
                                                    ->label('Type')
                                                    ->badge(),
                                                TextEntry::make('estimated_cabinet_value')
                                                    ->label('Est. Value')
                                                    ->money('USD')
                                                    ->weight(FontWeight::Bold)
                                                    ->color('success'),
                                            ]),

                                        // TCS Pricing Metadata
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('material_category')
                                                    ->label('Material')
                                                    ->badge()
                                                    ->color('info')
                                                    ->default('—')
                                                    ->formatStateUsing(fn ($state) => match($state) {
                                                        'paint_grade' => 'Paint Grade',
                                                        'stain_grade' => 'Stain Grade',
                                                        'premium' => 'Premium',
                                                        'custom_exotic' => 'Custom/Exotic',
                                                        default => $state ?? '—',
                                                    }),
                                                TextEntry::make('cabinet_level')
                                                    ->label('Level')
                                                    ->badge()
                                                    ->default('—'),
                                                TextEntry::make('finish_option')
                                                    ->label('Finish')
                                                    ->badge()
                                                    ->color('warning')
                                                    ->default('—')
                                                    ->formatStateUsing(fn ($state) => match($state) {
                                                        'unfinished' => 'Unfinished',
                                                        'natural_stain' => 'Natural Stain',
                                                        'custom_stain' => 'Custom Stain',
                                                        'paint_finish' => 'Paint Finish',
                                                        'clear_coat' => 'Clear Coat',
                                                        default => $state ?? '—',
                                                    }),
                                            ])
                                            ->visible(fn ($record) => $record->material_category || $record->cabinet_level || $record->finish_option),

                                        // Compact Linear Feet Display
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('total_linear_feet_tier_1')
                                                    ->label('Tier 1')
                                                    ->suffix(' LF')
                                                    ->default('—'),
                                                TextEntry::make('total_linear_feet_tier_2')
                                                    ->label('Tier 2')
                                                    ->suffix(' LF')
                                                    ->default('—'),
                                            ]),

                                        // Locations within this Room
                                        RepeatableEntry::make('locations')
                                            ->label('Locations')
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('')
                                                    ->icon('heroicon-o-map-pin')
                                                    ->weight(FontWeight::Bold)
                                                    ->columnSpanFull(),
                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('material_category')
                                                            ->label('Material')
                                                            ->badge()
                                                            ->color('info')
                                                            ->size('xs')
                                                            ->formatStateUsing(fn ($state) => match($state) {
                                                                'paint_grade' => 'Paint',
                                                                'stain_grade' => 'Stain',
                                                                'premium' => 'Premium',
                                                                'custom_exotic' => 'Custom',
                                                                default => null,
                                                            }),
                                                        TextEntry::make('cabinet_level')
                                                            ->label('Level')
                                                            ->badge()
                                                            ->size('xs'),
                                                        TextEntry::make('finish_option')
                                                            ->label('Finish')
                                                            ->badge()
                                                            ->color('warning')
                                                            ->size('xs')
                                                            ->formatStateUsing(fn ($state) => match($state) {
                                                                'unfinished' => 'Unfinished',
                                                                'natural_stain' => 'Natural',
                                                                'custom_stain' => 'Custom',
                                                                'paint_finish' => 'Paint',
                                                                'clear_coat' => 'Clear',
                                                                default => null,
                                                            }),
                                                    ])
                                                    ->visible(fn ($record) => $record->material_category || $record->cabinet_level || $record->finish_option)
                                                    ->columnSpanFull(),

                                                // Cabinet Runs within this Location
                                                RepeatableEntry::make('cabinetRuns')
                                                    ->label('Cabinet Runs')
                                                    ->schema([
                                                        TextEntry::make('name')
                                                            ->label('')
                                                            ->icon('heroicon-o-rectangle-stack')
                                                            ->weight(FontWeight::Bold)
                                                            ->columnSpanFull(),

                                                        // TCS Metadata for Cabinet Run
                                                        Grid::make(3)
                                                            ->schema([
                                                                TextEntry::make('material_category')
                                                                    ->label('Material')
                                                                    ->badge()
                                                                    ->color('info')
                                                                    ->size('xs')
                                                                    ->formatStateUsing(fn ($state) => match($state) {
                                                                        'paint_grade' => 'Paint',
                                                                        'stain_grade' => 'Stain',
                                                                        'premium' => 'Premium',
                                                                        'custom_exotic' => 'Custom',
                                                                        default => null,
                                                                    }),
                                                                TextEntry::make('cabinet_level')
                                                                    ->label('Level')
                                                                    ->badge()
                                                                    ->size('xs'),
                                                                TextEntry::make('finish_option')
                                                                    ->label('Finish')
                                                                    ->badge()
                                                                    ->color('warning')
                                                                    ->size('xs')
                                                                    ->formatStateUsing(fn ($state) => match($state) {
                                                                        'unfinished' => 'Unfinished',
                                                                        'natural_stain' => 'Natural',
                                                                        'custom_stain' => 'Custom',
                                                                        'paint_finish' => 'Paint',
                                                                        'clear_coat' => 'Clear',
                                                                        default => null,
                                                                    }),
                                                            ])
                                                            ->visible(fn ($record) => $record->material_category || $record->cabinet_level || $record->finish_option)
                                                            ->columnSpanFull(),

                                                        // Cabinets within this Cabinet Run
                                                        RepeatableEntry::make('cabinets')
                                                            ->label('Cabinets')
                                                            ->schema([
                                                                Grid::make(3)
                                                                    ->schema([
                                                                        TextEntry::make('cabinet_number')
                                                                            ->label('Cabinet #')
                                                                            ->badge()
                                                                            ->color('gray')
                                                                            ->weight(FontWeight::Bold),
                                                                        TextEntry::make('linear_feet')
                                                                            ->label('LF')
                                                                            ->suffix(' LF')
                                                                            ->weight(FontWeight::Bold)
                                                                            ->color('success'),
                                                                        TextEntry::make('quantity')
                                                                            ->label('Qty')
                                                                            ->default(1)
                                                                            ->badge()
                                                                            ->size('xs'),
                                                                    ]),
                                                                Grid::make(3)
                                                                    ->schema([
                                                                        TextEntry::make('length_inches')
                                                                            ->label('W')
                                                                            ->suffix('"'),
                                                                        TextEntry::make('depth_inches')
                                                                            ->label('D')
                                                                            ->suffix('"'),
                                                                        TextEntry::make('height_inches')
                                                                            ->label('H')
                                                                            ->suffix('"'),
                                                                    ]),
                                                                Grid::make(3)
                                                                    ->schema([
                                                                        TextEntry::make('material_category')
                                                                            ->label('Material')
                                                                            ->badge()
                                                                            ->color('info')
                                                                            ->size('xs')
                                                                            ->formatStateUsing(fn ($state) => match($state) {
                                                                                'paint_grade' => 'Paint',
                                                                                'stain_grade' => 'Stain',
                                                                                'premium' => 'Premium',
                                                                                'custom_exotic' => 'Custom',
                                                                                default => null,
                                                                            }),
                                                                        TextEntry::make('cabinet_level')
                                                                            ->label('Level')
                                                                            ->badge()
                                                                            ->size('xs'),
                                                                        TextEntry::make('finish_option')
                                                                            ->label('Finish')
                                                                            ->badge()
                                                                            ->color('warning')
                                                                            ->size('xs')
                                                                            ->formatStateUsing(fn ($state) => match($state) {
                                                                                'unfinished' => 'Unfinished',
                                                                                'natural_stain' => 'Natural',
                                                                                'custom_stain' => 'Custom',
                                                                                'paint_finish' => 'Paint',
                                                                                'clear_coat' => 'Clear',
                                                                                default => null,
                                                                            }),
                                                                    ])
                                                                    ->visible(fn ($record) => $record->material_category || $record->cabinet_level || $record->finish_option),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextEntry::make('unit_price_per_lf')
                                                                            ->label('Unit Price')
                                                                            ->money('USD')
                                                                            ->suffix('/LF'),
                                                                        TextEntry::make('total_price')
                                                                            ->label('Total')
                                                                            ->money('USD')
                                                                            ->weight(FontWeight::Bold)
                                                                            ->color('success')
                                                                            ->state(fn ($record) => $record->unit_price_per_lf * $record->linear_feet * ($record->quantity ?? 1)),
                                                                    ])
                                                                    ->visible(fn ($record) => $record->unit_price_per_lf > 0),
                                                            ])
                                                            ->contained(false)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->contained(false)
                                                    ->columnSpanFull(),
                                            ])
                                            ->contained(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(true)
                                    ->compact()
                                    ->icon('heroicon-o-home')
                                    ->description(fn ($record) => $record->notes ?? null),
                            ])
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpan(1), // Takes 1/3 of the width
            ]);
    }

    private function getActivityPlans(): mixed
    {
        return ActivityPlan::where('plugin', 'projects')->pluck('name', 'id');
    }
}
