<?php

namespace Webkul\Project\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Attributes\On;
use Livewire\Component;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;

class AnnotationEditor extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public bool $showModal = false;

    // Form data
    public ?array $data = [];

    // Context data for display
    public ?string $annotationType = null;

    public ?int $projectId = null;

    // Store original annotation for updates
    public ?array $originalAnnotation = null;

    public function mount(): void
    {
        // Don't fill form on mount, wait for annotation data
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255),

            Select::make('room_id')
                ->label('Room')
                ->options(function () {
                    if (! $this->projectId) {
                        return [];
                    }

                    return Room::where('project_id', $this->projectId)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->required()
                ->helperText(fn () => $this->annotationType === 'room' ? 'Create a new room or select existing' : null)
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('room_type')
                        ->options([
                            'kitchen'     => 'Kitchen',
                            'bathroom'    => 'Bathroom',
                            'bedroom'     => 'Bedroom',
                            'living_room' => 'Living Room',
                            'dining_room' => 'Dining Room',
                            'office'      => 'Office',
                            'other'       => 'Other',
                        ]),
                ])
                ->createOptionUsing(function (array $data): int {
                    $room = Room::create([
                        'project_id' => $this->projectId,
                        'name'       => $data['name'],
                        'room_type'  => $data['room_type'] ?? null,
                    ]);

                    return $room->id;
                })
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('location_id', null)),

            Select::make('location_id')
                ->label('Location')
                ->options(function (callable $get) {
                    $roomId = $get('room_id');
                    if (! $roomId) {
                        return [];
                    }

                    return RoomLocation::where('room_id', $roomId)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->visible(fn () => in_array($this->annotationType, ['location', 'cabinet_run']))
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('location_type')
                        ->options([
                            'wall'      => 'Wall',
                            'island'    => 'Island',
                            'peninsula' => 'Peninsula',
                            'corner'    => 'Corner',
                            'other'     => 'Other',
                        ]),
                ])
                ->createOptionUsing(function (array $data, callable $get): int {
                    $location = RoomLocation::create([
                        'room_id'       => $get('room_id'),
                        'name'          => $data['name'],
                        'location_type' => $data['location_type'] ?? null,
                    ]);

                    return $location->id;
                })
                ->disabled(fn (callable $get) => ! $get('room_id'))
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('cabinet_run_id', null)),

            Select::make('cabinet_run_id')
                ->label('Cabinet Run')
                ->options(function (callable $get) {
                    $locationId = $get('location_id');
                    if (! $locationId) {
                        return [];
                    }

                    return CabinetRun::where('room_location_id', $locationId)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->visible(fn () => in_array($this->annotationType, ['cabinet_run', 'cabinet']))
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('run_type')
                        ->options([
                            'base'  => 'Base Cabinets',
                            'wall'  => 'Wall Cabinets',
                            'tall'  => 'Tall Cabinets',
                            'mixed' => 'Mixed',
                        ]),
                ])
                ->createOptionUsing(function (array $data, callable $get): int {
                    $run = CabinetRun::create([
                        'room_location_id' => $get('location_id'),
                        'name'             => $data['name'],
                        'run_type'         => $data['run_type'] ?? null,
                    ]);

                    return $run->id;
                })
                ->disabled(fn (callable $get) => ! $get('location_id')),

            // Cabinet Specification Selection (for cabinet annotations)
            Select::make('cabinet_specification_id')
                ->label('Cabinet Specification')
                ->options(function (callable $get) {
                    $cabinetRunId = $get('cabinet_run_id');

                    if (!$cabinetRunId) {
                        return [];
                    }

                    return \Webkul\Project\Models\CabinetSpecification::where('cabinet_run_id', $cabinetRunId)
                        ->orderBy('position')
                        ->get()
                        ->mapWithKeys(function ($cabinet) {
                            // Create a descriptive label with position and dimensions
                            $label = $cabinet->name;
                            if ($cabinet->width || $cabinet->height) {
                                $label .= sprintf(' (%s"W × %s"H)',
                                    $cabinet->width ?? '?',
                                    $cabinet->height ?? '?'
                                );
                            }
                            return [$cabinet->id => $label];
                        })
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->required(fn () => $this->annotationType === 'cabinet')
                ->disabled(fn (callable $get) => !$get('cabinet_run_id'))
                ->visible(fn () => $this->annotationType === 'cabinet')
                ->helperText('Select the specific cabinet this annotation refers to')
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    // Auto-populate dimensions from cabinet spec if available
                    if ($state) {
                        $cabinet = \Webkul\Project\Models\CabinetSpecification::find($state);
                        if ($cabinet) {
                            if ($cabinet->width) {
                                $set('measurement_width', $cabinet->width);
                            }
                            if ($cabinet->height) {
                                $set('measurement_height', $cabinet->height);
                            }
                        }
                    }
                }),

            Textarea::make('notes')
                ->label('Notes / Comments')
                ->rows(4)
                ->columnSpanFull(),

            TextInput::make('measurement_width')
                ->label('Width (in)')
                ->numeric()
                ->step(0.125)
                ->visible(fn () => in_array($this->annotationType, ['cabinet_run', 'cabinet'])),

            TextInput::make('measurement_height')
                ->label('Height (in)')
                ->numeric()
                ->step(0.125)
                ->visible(fn () => in_array($this->annotationType, ['cabinet_run', 'cabinet'])),

            // Multi-Parent Entity References Section
            Section::make('Entity References')
                ->description('Associate this annotation with multiple entities (rooms, locations, runs, cabinets)')
                ->collapsible()
                ->collapsed(fn () => empty($this->data['entity_references'] ?? []))
                ->schema([
                    Repeater::make('entity_references')
                        ->label('')
                        ->schema([
                            Select::make('entity_type')
                                ->label('Entity Type')
                                ->options([
                                    'room' => 'Room',
                                    'location' => 'Location',
                                    'cabinet_run' => 'Cabinet Run',
                                    'cabinet' => 'Cabinet',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('entity_id', null)),

                            Select::make('entity_id')
                                ->label('Entity')
                                ->options(function (callable $get) {
                                    $entityType = $get('entity_type');
                                    if (!$entityType || !$this->projectId) {
                                        return [];
                                    }

                                    return match ($entityType) {
                                        'room' => Room::where('project_id', $this->projectId)
                                            ->pluck('name', 'id')
                                            ->toArray(),
                                        'location' => RoomLocation::whereHas('room', fn ($q) => $q->where('project_id', $this->projectId))
                                            ->pluck('name', 'id')
                                            ->toArray(),
                                        'cabinet_run' => CabinetRun::whereHas('location.room', fn ($q) => $q->where('project_id', $this->projectId))
                                            ->pluck('name', 'id')
                                            ->toArray(),
                                        'cabinet' => CabinetSpecification::whereHas('project', fn ($q) => $q->where('id', $this->projectId))
                                            ->pluck('name', 'id')
                                            ->toArray(),
                                        default => [],
                                    };
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (callable $get) => !$get('entity_type')),

                            Select::make('reference_type')
                                ->label('Reference Type')
                                ->options([
                                    'primary' => 'Primary Entity',
                                    'secondary' => 'Related Entity',
                                    'context' => 'Context Information',
                                ])
                                ->default('primary')
                                ->required()
                                ->helperText(fn (callable $get) => match ($get('reference_type')) {
                                    'primary' => 'Main entity this annotation belongs to',
                                    'secondary' => 'Related entities providing additional context',
                                    'context' => 'Background information for reference only',
                                    default => null,
                                }),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add Entity Reference')
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->visible(fn () => in_array($this->annotationType, ['elevation', 'section', 'detail']) ||
                    $this->annotationType === 'cabinet'), // Show for views and cabinet annotations
        ])
            ->statePath('data');
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Save Changes')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->size('md')
            ->action(fn () => $this->save()); // Directly call the save method
    }

    public function save(): void
    {
        try {
            // Get validated form state using proper Filament API
            $data = $this->form->getState();

            $annotationId = $this->originalAnnotation['id'];

            // If it's a temporary annotation (not saved yet), CREATE it in database
            if (is_string($annotationId) && str_starts_with($annotationId, 'temp_')) {
                // Get PDF page to calculate normalized dimensions
                $pdfPage = \App\Models\PdfPage::find($this->originalAnnotation['pdfPageId']);
                $pageWidth = $pdfPage?->page_width ?? 2592;  // Default PDF page width
                $pageHeight = $pdfPage?->page_height ?? 1728; // Default PDF page height

                // Calculate normalized width and height from PDF dimensions
                $normalizedWidth = ($this->originalAnnotation['pdfWidth'] ?? 0) / $pageWidth;
                $normalizedHeight = ($this->originalAnnotation['pdfHeight'] ?? 0) / $pageHeight;

                // Auto-detect position from Y coordinate
                $normalizedY = $this->originalAnnotation['normalizedY'] ?? 0;
                $positionData = $this->inferPositionFromCoordinates($normalizedY, $normalizedHeight);

                // Create new annotation in database
                $annotation = \App\Models\PdfPageAnnotation::create([
                    'pdf_page_id'      => $this->originalAnnotation['pdfPageId'],
                    'annotation_type'  => $this->originalAnnotation['type'] ?? 'room',
                    'label'            => $data['label'],
                    'notes'            => $data['notes'] ?? '',
                    'room_id'          => $data['room_id'] ?? null,
                    'room_location_id' => $data['location_id'] ?? null,
                    'cabinet_run_id'   => $data['cabinet_run_id'] ?? null,
                    'x'                => $this->originalAnnotation['normalizedX'] ?? 0,
                    'y'                => $normalizedY,
                    'width'            => $normalizedWidth,
                    'height'           => $normalizedHeight,
                    'color'            => $this->originalAnnotation['color'] ?? '#f59e0b',
                    // View types and position detection
                    'view_type'        => $this->originalAnnotation['viewType'] ?? 'plan',
                    'view_orientation' => $this->originalAnnotation['viewOrientation'] ?? null,
                    'view_scale'       => $this->originalAnnotation['viewScale'] ?? null,
                    'inferred_position' => $positionData['inferred_position'],
                    'vertical_zone'    => $positionData['vertical_zone'],
                ]);

                // Log creation
                \App\Models\PdfAnnotationHistory::logAction(
                    pdfPageId: $annotation->pdf_page_id,
                    action: 'created',
                    beforeData: null,
                    afterData: $annotation->toArray(),
                    annotationId: $annotation->id
                );

                // Handle entity references from form data (takes precedence) or frontend
                $entityReferences = $data['entity_references'] ?? $this->originalAnnotation['entityReferences'] ?? [];
                if (!empty($entityReferences) && is_array($entityReferences)) {
                    $annotation->syncEntityReferences($entityReferences);
                }

                // Build updated annotation with real database ID
                $updatedAnnotation = array_merge($this->originalAnnotation, [
                    'id'                => $annotation->id, // Replace temp ID with real ID
                    'label'             => $data['label'],
                    'notes'             => $data['notes'] ?? '',
                    'measurementWidth'  => $data['measurement_width'] ?? null,
                    'measurementHeight' => $data['measurement_height'] ?? null,
                    'roomId'            => $data['room_id'] ?? null,
                    'locationId'        => $data['location_id'] ?? null,
                    'cabinetRunId'      => isset($data['cabinet_run_id']) ? $data['cabinet_run_id'] : null,
                ]);

                // Update display names
                if (isset($data['room_id']) && $data['room_id']) {
                    $room = Room::find($data['room_id']);
                    $updatedAnnotation['roomName'] = $room?->name;
                }

                if (isset($data['location_id']) && $data['location_id']) {
                    $location = RoomLocation::find($data['location_id']);
                    $updatedAnnotation['locationName'] = $location?->name;
                }

                // Dispatch event back to Alpine.js with new database ID
                $this->dispatch('annotation-updated', annotation: $updatedAnnotation);

                \Filament\Notifications\Notification::make()
                    ->title('Annotation Saved')
                    ->body('The annotation has been saved to the database.')
                    ->success()
                    ->send();

                $this->close();

                return;
            }

            // Otherwise, update in database
            $annotation = \App\Models\PdfPageAnnotation::findOrFail($annotationId);
            $pdfPageId = $annotation->pdf_page_id;

            // Log before update
            $beforeData = $annotation->toArray();

            // Update annotation in database
            $updateData = [
                'label'            => $data['label'],
                'notes'            => $data['notes'] ?? '',
                'room_id'          => $data['room_id'] ?? null,
                'room_location_id' => $data['location_id'] ?? null,
                'cabinet_run_id'   => $data['cabinet_run_id'] ?? null,
            ];

            $annotation->update($updateData);

            // Handle entity references from form data
            if (isset($data['entity_references']) && is_array($data['entity_references'])) {
                $annotation->syncEntityReferences($data['entity_references']);
            }

            // Log after update
            \App\Models\PdfAnnotationHistory::logAction(
                pdfPageId: $pdfPageId,
                action: 'updated',
                beforeData: $beforeData,
                afterData: $annotation->fresh()->toArray(),
                annotationId: $annotation->id
            );

            // Build updated annotation for Alpine.js
            $updatedAnnotation = array_merge($this->originalAnnotation, [
                'label'             => $data['label'],
                'notes'             => $data['notes'] ?? '',
                'measurementWidth'  => $data['measurement_width'] ?? null,
                'measurementHeight' => $data['measurement_height'] ?? null,
                'roomId'            => $data['room_id'] ?? null,
                'locationId'        => isset($data['location_id']) ? $data['location_id'] : null,
                'cabinetRunId'      => isset($data['cabinet_run_id']) ? $data['cabinet_run_id'] : null,
            ]);

            // Update display names
            if (isset($data['room_id']) && $data['room_id']) {
                $room = Room::find($data['room_id']);
                $updatedAnnotation['roomName'] = $room?->name;
            }

            if (isset($data['location_id']) && $data['location_id']) {
                $location = RoomLocation::find($data['location_id']);
                $updatedAnnotation['locationName'] = $location?->name;
            }

            // Dispatch event back to Alpine.js to update UI
            $this->dispatch('annotation-updated', annotation: $updatedAnnotation);

            // Show success notification
            \Filament\Notifications\Notification::make()
                ->title('Annotation Updated')
                ->body('The annotation has been saved to the database.')
                ->success()
                ->send();

            // Close modal
            $this->close();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Filament\Notifications\Notification::make()
                ->title('Save Failed')
                ->body('Annotation not found in database.')
                ->danger()
                ->send();

            \Log::error('Annotation not found for update', [
                'annotation_id' => $annotationId ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Save Failed')
                ->body('Error saving annotation: '.$e->getMessage())
                ->danger()
                ->send();

            \Log::error('Annotation update failed', [
                'annotation_id' => $annotationId ?? 'unknown',
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->color('gray')
            ->size('md')
            ->action(fn () => $this->close());
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->size('md')
            ->requiresConfirmation()
            ->modalHeading('Delete Annotation')
            ->modalDescription('Are you sure you want to delete this annotation? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete it')
            ->action(function () {
                $annotationId = $this->originalAnnotation['id'];

                // If it's a temporary annotation (not saved yet), just dispatch the event
                if (is_string($annotationId) && str_starts_with($annotationId, 'temp_')) {
                    $this->dispatch('annotation-deleted', annotationId: $annotationId);

                    // Show success notification
                    \Filament\Notifications\Notification::make()
                        ->title('Annotation Removed')
                        ->body('The unsaved annotation has been removed.')
                        ->success()
                        ->send();

                    $this->close();

                    return;
                }

                // Otherwise, delete from database directly
                try {
                    // Find the annotation
                    $annotation = \App\Models\PdfPageAnnotation::findOrFail($annotationId);
                    $pdfPageId = $annotation->pdf_page_id;

                    // Log deletion before deleting
                    \App\Models\PdfAnnotationHistory::logAction(
                        pdfPageId: $pdfPageId,
                        action: 'deleted',
                        beforeData: $annotation->toArray(),
                        afterData: null,
                        annotationId: null  // Set to null since annotation will be deleted
                    );

                    // Delete the annotation
                    $annotation->delete();

                    // Dispatch event to Alpine.js to remove from UI
                    $this->dispatch('annotation-deleted', annotationId: $annotationId);

                    // Show success notification
                    \Filament\Notifications\Notification::make()
                        ->title('Annotation Deleted')
                        ->body('The annotation has been permanently removed.')
                        ->success()
                        ->send();

                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Delete Failed')
                        ->body('Annotation not found in database.')
                        ->danger()
                        ->send();

                    \Log::error('Annotation not found for deletion', [
                        'annotation_id' => $annotationId,
                    ]);
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Delete Failed')
                        ->body('Error deleting annotation: '.$e->getMessage())
                        ->danger()
                        ->send();

                    \Log::error('Annotation deletion failed', [
                        'annotation_id' => $annotationId,
                        'error'         => $e->getMessage(),
                    ]);
                }

                // Close modal
                $this->close();
            });
    }

    public function render()
    {
        return view('webkul-project::livewire.annotation-editor');
    }

    #[On('edit-annotation')]
    public function handleEditAnnotation(array $annotation): void
    {
        $this->originalAnnotation = $annotation;

        // Extract context FIRST (needed for form schema)
        $this->annotationType = $annotation['type'] ?? null;
        $this->projectId = $annotation['projectId'] ?? null;

        // Load entity references from database if this is an existing annotation
        $entityReferences = [];
        $annotationId = $annotation['id'] ?? null;
        if ($annotationId && is_numeric($annotationId)) {
            $dbAnnotation = \App\Models\PdfPageAnnotation::find($annotationId);
            if ($dbAnnotation) {
                $entityReferences = $dbAnnotation->entityReferences()
                    ->get()
                    ->map(fn ($ref) => [
                        'entity_type' => $ref->entity_type,
                        'entity_id' => $ref->entity_id,
                        'reference_type' => $ref->reference_type,
                    ])
                    ->toArray();
            }
        }

        // Fill form with annotation data using Filament Forms API
        $this->form->fill([
            'label'              => $annotation['label'] ?? '',
            'notes'              => $annotation['notes'] ?? '',
            'room_id'            => $annotation['roomId'] ?? null,
            'location_id'        => $annotation['locationId'] ?? null,
            'cabinet_run_id'     => $annotation['cabinetRunId'] ?? null,
            'measurement_width'  => $annotation['measurementWidth'] ?? null,
            'measurement_height' => $annotation['measurementHeight'] ?? null,
            'entity_references'  => $entityReferences,
        ]);

        $this->showModal = true;
    }

    #[On('update-annotation-position')]
    public function handleUpdateAnnotationPosition(
        int $annotationId,
        float $pdfX,
        float $pdfY,
        float $pdfWidth,
        float $pdfHeight,
        float $normalizedX,
        float $normalizedY
    ): void {
        try {
            // Find the annotation
            $annotation = \App\Models\PdfPageAnnotation::findOrFail($annotationId);
            $pdfPageId = $annotation->pdf_page_id;

            // Get PDF page dimensions for normalized width/height calculation
            $pdfPage = \App\Models\PdfPage::find($pdfPageId);
            $pageWidth = $pdfPage?->page_width ?? 2592;  // Default PDF page width
            $pageHeight = $pdfPage?->page_height ?? 1728; // Default PDF page height

            // Calculate normalized dimensions
            $normalizedWidth = $pdfWidth / $pageWidth;
            $normalizedHeight = $pdfHeight / $pageHeight;

            // Auto-detect position from new Y coordinate
            $positionData = $this->inferPositionFromCoordinates($normalizedY, $normalizedHeight);

            // Log before update
            $beforeData = $annotation->toArray();

            // Update position and dimensions in database
            $annotation->update([
                'x'                 => $normalizedX,
                'y'                 => $normalizedY,
                'width'             => $normalizedWidth,
                'height'            => $normalizedHeight,
                'inferred_position' => $positionData['inferred_position'],
                'vertical_zone'     => $positionData['vertical_zone'],
            ]);

            // Log after update
            \App\Models\PdfAnnotationHistory::logAction(
                pdfPageId: $pdfPageId,
                action: 'position_updated',
                beforeData: $beforeData,
                afterData: $annotation->fresh()->toArray(),
                annotationId: $annotation->id
            );

            \Log::info('Annotation position updated', [
                'annotation_id' => $annotationId,
                'x'             => $normalizedX,
                'y'             => $normalizedY,
                'width'         => $normalizedWidth,
                'height'        => $normalizedHeight,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Annotation not found for position update', [
                'annotation_id' => $annotationId,
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Update Failed')
                ->body('Annotation not found in database.')
                ->danger()
                ->send();
        } catch (\Exception $e) {
            \Log::error('Annotation position update failed', [
                'annotation_id' => $annotationId,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Update Failed')
                ->body('Error updating annotation position: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancel(): void
    {
        $this->close();
    }

    /**
     * Auto-detect cabinet position from Y coordinate on page
     *
     * @param float $normalizedY Y coordinate (normalized 0-1)
     * @param float $normalizedHeight Height (normalized 0-1)
     * @return array ['inferred_position' => string, 'vertical_zone' => string]
     */
    private function inferPositionFromCoordinates(float $normalizedY, float $normalizedHeight): array
    {
        // Convert normalized Y to percentage (flip Y axis for typical drawing orientation)
        $yPercent = (1 - $normalizedY) * 100;

        // Determine vertical zone based on Y position
        // Note: In PDF coordinates, Y=0 is at bottom, so we flip to get standard top=0 orientation
        if ($yPercent < 30) {
            $zone = 'upper';
            $position = 'wall_cabinet';
        } elseif ($yPercent > 70) {
            $zone = 'lower';
            $position = 'base_cabinet';
        } else {
            $zone = 'middle';

            // Check height to determine if it's a tall cabinet or standard base
            $heightPercent = $normalizedHeight * 100;

            if ($heightPercent > 40) {
                $position = 'tall_cabinet';
            } else {
                $position = 'base_cabinet';
            }
        }

        return [
            'inferred_position' => $position,
            'vertical_zone'     => $zone,
        ];
    }

    private function close(): void
    {
        $this->showModal = false;
        $this->reset(['data', 'annotationType', 'projectId', 'originalAnnotation']);

        // Notify Alpine component that modal is closed
        $this->dispatch('annotation-editor-closed');
    }
}
