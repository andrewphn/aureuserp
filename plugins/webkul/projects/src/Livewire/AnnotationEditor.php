<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;

class AnnotationEditor extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas;
    use InteractsWithActions;

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
                        if (!$this->projectId) {
                            return [];
                        }
                        return Room::where('project_id', $this->projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('room_type')
                            ->options([
                                'kitchen' => 'Kitchen',
                                'bathroom' => 'Bathroom',
                                'bedroom' => 'Bedroom',
                                'living_room' => 'Living Room',
                                'dining_room' => 'Dining Room',
                                'office' => 'Office',
                                'other' => 'Other',
                            ]),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $room = Room::create([
                            'project_id' => $this->projectId,
                            'name' => $data['name'],
                            'room_type' => $data['room_type'] ?? null,
                        ]);
                        return $room->id;
                    })
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('location_id', null)),

                Select::make('location_id')
                    ->label('Location')
                    ->options(function (callable $get) {
                        $roomId = $get('room_id');
                        if (!$roomId) {
                            return [];
                        }
                        return RoomLocation::where('room_id', $roomId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->visible(fn () => in_array($this->annotationType, ['location', 'cabinet_run']))
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('location_type')
                            ->options([
                                'wall' => 'Wall',
                                'island' => 'Island',
                                'peninsula' => 'Peninsula',
                                'corner' => 'Corner',
                                'other' => 'Other',
                            ]),
                    ])
                    ->createOptionUsing(function (array $data, callable $get): int {
                        $location = RoomLocation::create([
                            'room_id' => $get('room_id'),
                            'name' => $data['name'],
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
                        if (!$locationId) {
                            return [];
                        }
                        return CabinetRun::where('room_location_id', $locationId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->visible(fn () => $this->annotationType === 'cabinet_run')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('run_type')
                            ->options([
                                'base' => 'Base Cabinets',
                                'wall' => 'Wall Cabinets',
                                'tall' => 'Tall Cabinets',
                                'mixed' => 'Mixed',
                            ]),
                    ])
                    ->createOptionUsing(function (array $data, callable $get): int {
                        $run = CabinetRun::create([
                            'room_location_id' => $get('location_id'),
                            'name' => $data['name'],
                            'run_type' => $data['run_type'] ?? null,
                        ]);
                        return $run->id;
                    })
                    ->disabled(fn (callable $get) => ! $get('location_id')),

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
            ])
            ->statePath('data');
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Save Changes')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->action(function () {
                try {
                    // Get validated form state using Filament Forms API
                    $data = $this->form->getState();

                    $annotationId = $this->originalAnnotation['id'];

                    // If it's a temporary annotation (not saved yet), just update Alpine.js state
                    if (is_string($annotationId) && str_starts_with($annotationId, 'temp_')) {
                        // Update annotation with form data
                        $updatedAnnotation = array_merge($this->originalAnnotation, [
                            'label' => $data['label'],
                            'notes' => $data['notes'] ?? '',
                            'measurementWidth' => $data['measurement_width'] ?? null,
                            'measurementHeight' => $data['measurement_height'] ?? null,
                            'roomId' => $data['room_id'] ?? null,
                            'locationId' => isset($data['location_id']) ? $data['location_id'] : null,
                            'cabinetRunId' => isset($data['cabinet_run_id']) ? $data['cabinet_run_id'] : null,
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

                        // Dispatch event back to Alpine.js
                        $this->dispatch('annotation-updated', annotation: $updatedAnnotation);

                        \Filament\Notifications\Notification::make()
                            ->title('Annotation Updated')
                            ->body('Changes saved locally. Click "Save" button to persist to database.')
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
                        'label' => $data['label'],
                        'notes' => $data['notes'] ?? '',
                        'room_id' => $data['room_id'] ?? null,
                    ];

                    // Only add cabinet_run_id if location_id exists in form data
                    if (isset($data['location_id'])) {
                        $updateData['cabinet_run_id'] = $data['location_id'];
                    }

                    $annotation->update($updateData);

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
                        'label' => $data['label'],
                        'notes' => $data['notes'] ?? '',
                        'measurementWidth' => $data['measurement_width'] ?? null,
                        'measurementHeight' => $data['measurement_height'] ?? null,
                        'roomId' => $data['room_id'] ?? null,
                        'locationId' => isset($data['location_id']) ? $data['location_id'] : null,
                        'cabinetRunId' => isset($data['cabinet_run_id']) ? $data['cabinet_run_id'] : null,
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
                        'annotation_id' => $annotationId ?? 'unknown'
                    ]);
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Save Failed')
                        ->body('Error saving annotation: ' . $e->getMessage())
                        ->danger()
                        ->send();

                    \Log::error('Annotation update failed', [
                        'annotation_id' => $annotationId ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->color('gray')
            ->action(fn () => $this->close());
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
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
                        'annotation_id' => $annotationId
                    ]);
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Delete Failed')
                        ->body('Error deleting annotation: ' . $e->getMessage())
                        ->danger()
                        ->send();

                    \Log::error('Annotation deletion failed', [
                        'annotation_id' => $annotationId,
                        'error' => $e->getMessage()
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

        // Fill form with annotation data using Filament Forms API
        $this->form->fill([
            'label' => $annotation['label'] ?? '',
            'notes' => $annotation['notes'] ?? '',
            'room_id' => $annotation['roomId'] ?? null,
            'location_id' => $annotation['locationId'] ?? null,
            'cabinet_run_id' => $annotation['cabinetRunId'] ?? null,
            'measurement_width' => $annotation['measurementWidth'] ?? null,
            'measurement_height' => $annotation['measurementHeight'] ?? null,
        ]);

        $this->showModal = true;
    }

    private function close(): void
    {
        $this->showModal = false;
        $this->reset(['data', 'annotationType', 'projectId', 'originalAnnotation']);
    }
}
