<?php

namespace Webkul\Project\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
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

    // Linked entity IDs (set during edit, used for Entity Details tab)
    public ?int $linkedRoomId = null;
    public ?int $linkedLocationId = null;
    public ?int $linkedCabinetRunId = null;
    public ?int $linkedCabinetSpecId = null;

    // Annotation model for FilamentPHP v4 relationship binding
    public ?\App\Models\PdfPageAnnotation $annotationModel = null;

    public function mount(): void
    {
        // Don't fill form on mount, wait for annotation data
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('annotation_tabs')
                ->tabs([
                    Tab::make('Annotation')
                        ->icon('heroicon-o-pencil')
                        ->schema([
                            // Hierarchy breadcrumb display - TEMPORARILY DISABLED due to form state recursion
                            // TODO: Implement using Livewire property instead of closure
                            // Placeholder::make('hierarchy_path')
                            //     ->label('Hierarchy')
                            //     ->content(fn () => $this->getHierarchyPathHtml())
                            //     ->visible(fn () => !empty($this->originalAnnotation['id'])),

            // Parent annotation selector
            Select::make('parent_annotation_id')
                ->label('Parent Annotation')
                ->helperText('Change which annotation this belongs to')
                ->options(fn () => $this->getAvailableParents())
                ->searchable()
                ->placeholder('None (top level)')
                ->nullable()
                ->live()
                ->rules([
                    fn () => function (string $attribute, $value, \Closure $fail) {
                        if (!$value) {
                            return; // Allow null parent for top-level annotations
                        }

                        // Query the parent annotation to check its type
                        $parent = DB::table('pdf_page_annotations')
                            ->where('id', $value)
                            ->first();

                        if (!$parent) {
                            $fail('The selected parent annotation does not exist.');
                            return;
                        }

                        // Define valid parent types based on annotation type
                        $validTypes = match($this->annotationType) {
                            'location' => ['room'],
                            'cabinet_run' => ['location'],
                            'cabinet' => ['cabinet_run'],
                            default => [],
                        };

                        // Validate parent type matches allowed types
                        if (!empty($validTypes) && !in_array($parent->annotation_type, $validTypes)) {
                            $fail("Invalid parent type. {$this->annotationType} annotations can only have " . implode(', ', $validTypes) . " as parents. The selected annotation is a {$parent->annotation_type}.");
                        }
                    }
                ])
                ->visible(fn () => $this->annotationType !== 'room'),

            // Show inherited room (read-only, for reference)
            // HIDDEN: Redundant since parent annotation already shows room context in tree view
            // Room inheritance is automatic via parent_annotation_id chain
            // Select::make('_inherited_room_id')
            //     ->label('Room (inherited from parent)')
            //     ->helperText('This room is automatically inherited from the parent annotation')
            //     ->options(fn () => \Webkul\Project\Models\Room::where('project_id', $this->projectId)
            //         ->pluck('name', 'id')
            //         ->toArray())
            //     ->disabled()
            //     ->dehydrated(false)
            //     ->default(fn (callable $get) => $this->getRoomIdFromParent($get('parent_annotation_id')))
            //     ->visible(fn () => $this->annotationType !== 'room'),

            TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true),

            // Entity detection status - shows if annotation will create new or link to existing entity
            Placeholder::make('entity_detection_status')
                ->label('Entity Status')
                ->content(function (callable $get) {
                    return $this->getEntityDetectionStatus($get);
                })
                ->visible(fn () => in_array($this->annotationType, ['location', 'cabinet_run'])),

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
                ->afterStateUpdated(fn (callable $set) => $set('location_id', null))
                ->visible(fn () => $this->annotationType === 'room'),

            // Room Location selector for location annotations
            Select::make('room_location_id')
                ->label('Room Location')
                ->helperText('Select an existing location or create a new one')
                ->options(function (callable $get) {
                    if (! $this->projectId) {
                        return [];
                    }

                    // Get room_id from parent or form
                    $roomId = $this->getRoomIdFromParent($get('parent_annotation_id'));

                    // Get all RoomLocations in the project (not filtered by room)
                    return RoomLocation::whereHas('room', function ($query) {
                            $query->where('project_id', $this->projectId);
                        })
                        ->get()
                        ->mapWithKeys(function ($location) {
                            // Show room name in the label for context
                            $roomName = $location->room ? $location->room->name : 'Unknown Room';
                            return [$location->id => "{$location->name} ({$roomName})"];
                        })
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Location Name')
                        ->required()
                        ->maxLength(255),
                    Select::make('location_type')
                        ->label('Location Type')
                        ->options([
                            'wall'      => 'Wall',
                            'island'    => 'Island',
                            'peninsula' => 'Peninsula',
                            'corner'    => 'Corner',
                            'other'     => 'Other',
                        ]),
                ])
                ->createOptionUsing(function (array $data, callable $get): int {
                    // Get room_id from parent chain
                    $roomId = $this->getRoomIdFromParent($get('parent_annotation_id'));

                    if (!$roomId) {
                        throw new \Exception('Room ID could not be determined from parent annotation');
                    }

                    $location = RoomLocation::create([
                        'room_id'       => $roomId,
                        'name'          => $data['name'],
                        'location_type' => $data['location_type'] ?? null,
                        'creator_id'    => auth()->id(),
                    ]);

                    return $location->id;
                })
                ->visible(fn () => $this->annotationType === 'location'),

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
                ->visible(fn () => in_array($this->annotationType, ['cabinet_run', 'cabinet']))
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
                ->visible(fn () => $this->annotationType === 'cabinet')
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
                        ->orderBy('position_in_run')
                        ->get()
                        ->mapWithKeys(function ($cabinet) {
                            // Create a descriptive label with cabinet number and dimensions
                            $label = $cabinet->cabinet_number ?? 'Cabinet';
                            if ($cabinet->width_inches || $cabinet->height_inches) {
                                $label .= sprintf(' (%s"W × %s"H)',
                                    $cabinet->width_inches ?? '?',
                                    $cabinet->height_inches ?? '?'
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

            // View Type Section
            Section::make('View Type')
                ->description('Specify what type of view this annotation represents (Plan, Elevation, Section, or Detail)')
                ->collapsible()
                ->schema([
                    Select::make('view_type')
                        ->label('View Type')
                        ->options(function (callable $get) {
                            // Base view type options
                            $baseOptions = [
                                'plan' => 'Plan View (Top-Down)',
                                'elevation' => 'Elevation View (Front/Side)',
                                'section' => 'Section View (Cut-Through)',
                                'detail' => 'Detail View (Zoom/Closeup)',
                            ];

                            // Only check for taken views if this is a location annotation with a location selected
                            if ($this->annotationType !== 'location') {
                                return $baseOptions;
                            }

                            // Get location ID from form or original annotation
                            $locationId = $get('room_location_id') ?? $this->originalAnnotation['roomLocationId'] ?? null;
                            if (!$locationId) {
                                return $baseOptions;
                            }

                            // Get PDF document ID
                            $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;
                            if (!$pdfPageId) {
                                return $baseOptions;
                            }

                            $pdfPage = \App\Models\PdfPage::find($pdfPageId);
                            if (!$pdfPage || !$pdfPage->document_id) {
                                return $baseOptions;
                            }

                            // Get taken view types for this location
                            $takenViews = $this->getLocationViewTypes($locationId, $pdfPage->document_id);

                            // Only mark Plan as taken (elevation/section depend on orientation, detail allows multiple)
                            return collect($baseOptions)->map(function ($label, $key) use ($takenViews) {
                                if ($key === 'plan' && isset($takenViews['plan'])) {
                                    $pages = implode(', ', $takenViews['plan']);
                                    return $label . ' ✓ (Page ' . $pages . ')';
                                }
                                return $label;
                            })->toArray();
                        })
                        ->default('plan')
                        ->required()
                        ->live()
                        ->helperText(function (callable $get) {
                            // Only show special helper for location annotations
                            if ($this->annotationType !== 'location') {
                                return 'Select the type of view this annotation represents';
                            }

                            $locationId = $get('room_location_id') ?? $this->originalAnnotation['roomLocationId'] ?? null;
                            if (!$locationId) {
                                return 'Select the type of view this annotation represents';
                            }

                            // Get PDF document ID
                            $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;
                            if (!$pdfPageId) {
                                return 'Select the type of view this annotation represents';
                            }

                            $pdfPage = \App\Models\PdfPage::find($pdfPageId);
                            if (!$pdfPage || !$pdfPage->document_id) {
                                return 'Select the type of view this annotation represents';
                            }

                            $takenViews = $this->getLocationViewTypes($locationId, $pdfPage->document_id);

                            if (empty($takenViews)) {
                                return 'This is the first view for this location';
                            }

                            return 'Views marked with ✓ already exist on other pages. You can still select them if needed.';
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Reset orientation when view type changes
                            if ($state === 'plan' || $state === 'detail') {
                                $set('view_orientation', null);
                            } elseif ($state === 'elevation' && !$this->data['view_orientation']) {
                                $set('view_orientation', 'front');
                            } elseif ($state === 'section' && !$this->data['view_orientation']) {
                                $set('view_orientation', 'A-A');
                            }
                        }),

                    Select::make('view_orientation')
                        ->label('Orientation')
                        ->options(function (callable $get) {
                            $viewType = $get('view_type');

                            // Base orientation options
                            $baseOptions = match ($viewType) {
                                'elevation' => [
                                    'front' => 'Front',
                                    'back' => 'Back',
                                    'left' => 'Left',
                                    'right' => 'Right',
                                ],
                                'section' => [
                                    'A-A' => 'A-A',
                                    'B-B' => 'B-B',
                                    'C-C' => 'C-C',
                                    'D-D' => 'D-D',
                                ],
                                default => [],
                            };

                            // Only check for taken orientations if this is a location annotation
                            if ($this->annotationType !== 'location' || !in_array($viewType, ['elevation', 'section'])) {
                                return $baseOptions;
                            }

                            // Get location ID from form
                            $locationId = $get('room_location_id') ?? $this->originalAnnotation['roomLocationId'] ?? null;
                            if (!$locationId) {
                                return $baseOptions;
                            }

                            // Get PDF document ID
                            $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;
                            if (!$pdfPageId) {
                                return $baseOptions;
                            }

                            $pdfPage = \App\Models\PdfPage::find($pdfPageId);
                            if (!$pdfPage || !$pdfPage->document_id) {
                                return $baseOptions;
                            }

                            // Get taken view types for this location
                            $takenViews = $this->getLocationViewTypes($locationId, $pdfPage->document_id);

                            // Mark taken orientations with checkmark and page numbers
                            return collect($baseOptions)->map(function ($label, $orientation) use ($takenViews, $viewType) {
                                $key = $viewType . '-' . $orientation;
                                if (isset($takenViews[$key])) {
                                    $pages = implode(', ', $takenViews[$key]);
                                    return $label . ' ✓ (Page ' . $pages . ')';
                                }
                                return $label;
                            })->toArray();
                        })
                        ->visible(fn (callable $get) => in_array($get('view_type'), ['elevation', 'section'])) // Show only for elevation/section views
                        ->required(fn (callable $get) => in_array($get('view_type'), ['elevation', 'section']))
                        ->helperText(function (callable $get) {
                            $viewType = $get('view_type');

                            // Only show availability info for location annotations
                            if ($this->annotationType !== 'location' || !in_array($viewType, ['elevation', 'section'])) {
                                if ($viewType === 'elevation') {
                                    return 'Select which side/face this elevation shows';
                                } elseif ($viewType === 'section') {
                                    return 'Select which section cut line this represents';
                                }
                                return '';
                            }

                            // Get location ID and check for taken orientations
                            $locationId = $get('room_location_id') ?? $this->originalAnnotation['roomLocationId'] ?? null;
                            if (!$locationId) {
                                return 'Select which orientation this view shows';
                            }

                            $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;
                            if (!$pdfPageId) {
                                return 'Select which orientation this view shows';
                            }

                            $pdfPage = \App\Models\PdfPage::find($pdfPageId);
                            if (!$pdfPage || !$pdfPage->document_id) {
                                return 'Select which orientation this view shows';
                            }

                            $takenViews = $this->getLocationViewTypes($locationId, $pdfPage->document_id);

                            // Check if any orientations for this view type are taken
                            $hasTakenOrientations = false;
                            foreach ($takenViews as $key => $pages) {
                                if (str_starts_with($key, $viewType . '-')) {
                                    $hasTakenOrientations = true;
                                    break;
                                }
                            }

                            if ($hasTakenOrientations) {
                                return 'Orientations marked with ✓ already exist. You can still select them for additional views.';
                            }

                            return 'This is the first ' . $viewType . ' view for this location';
                        }),
                        // Note: ->live() removed - no dependent fields, unnecessary server requests
                ])
                ->columnSpanFull(),

                // Linked Entity Summary - Read-only display with edit link
                Placeholder::make('linked_entity_summary')
                    ->label('Linked Entity')
                    ->content(fn () => $this->getLinkedEntitySummary())
                    ->visible(fn () => $this->hasLinkedEntity())
                    ->columnSpanFull(),
                        ]),  // Close Annotation Tab schema
                ]),  // Close tabs()
        ])  // Close components()
            ->statePath('data')
            ->model($this->annotationModel);  // Bind annotation model for relationship fields
    }

    /**
     * Check if the annotation has a linked entity
     * Uses Livewire properties instead of form->getState() to avoid infinite recursion
     */
    protected function hasLinkedEntity(): bool
    {
        return !empty($this->linkedRoomId)
            || !empty($this->linkedLocationId)
            || !empty($this->linkedCabinetRunId)
            || !empty($this->linkedCabinetSpecId);
    }

    /**
     * Get linked entity summary as read-only HTML string
     * Returns a simple text summary with edit link for deeper entity management
     */
    protected function getLinkedEntitySummary(): string
    {
        // Cabinet Specification (most specific)
        if (!empty($this->linkedCabinetSpecId)) {
            $cabinet = CabinetSpecification::find($this->linkedCabinetSpecId);
            if (!$cabinet) {
                return '<span class="text-gray-500">Cabinet specification not found</span>';
            }

            $run = $cabinet->cabinetRun;
            $location = $run?->roomLocation;
            $room = $location?->room;

            return sprintf(
                '<div class="space-y-1 text-sm"><div><strong>Room:</strong> %s</div><div><strong>Location:</strong> %s</div><div><strong>Cabinet Run:</strong> %s</div><div><strong>Cabinet:</strong> %s <a href="/admin/projects/cabinet-specifications/%d/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">Edit Details →</a></div></div>',
                e($room?->name ?? 'N/A'),
                e($location?->name ?? 'N/A'),
                e($run?->name ?? 'N/A'),
                e($cabinet->cabinet_number ?? 'N/A'),
                $this->linkedCabinetSpecId
            );
        }

        // Cabinet Run
        if (!empty($this->linkedCabinetRunId)) {
            $run = CabinetRun::find($this->linkedCabinetRunId);
            if (!$run) {
                return '<span class="text-gray-500">Cabinet run not found</span>';
            }

            $location = $run->roomLocation;
            $room = $location?->room;

            return sprintf(
                '<div class="space-y-1 text-sm"><div><strong>Room:</strong> %s</div><div><strong>Location:</strong> %s</div><div><strong>Cabinet Run:</strong> %s <a href="/admin/projects/cabinet-runs/%d/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">Edit Details →</a></div></div>',
                e($room?->name ?? 'N/A'),
                e($location?->name ?? 'N/A'),
                e($run->name),
                $this->linkedCabinetRunId
            );
        }

        // Room Location
        if (!empty($this->linkedLocationId)) {
            $location = RoomLocation::find($this->linkedLocationId);
            if (!$location) {
                return '<span class="text-gray-500">Location not found</span>';
            }

            $room = $location->room;

            return sprintf(
                '<div class="space-y-1 text-sm"><div><strong>Room:</strong> %s</div><div><strong>Location:</strong> %s <a href="/admin/projects/room-locations/%d/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">Edit Details →</a></div></div>',
                e($room?->name ?? 'N/A'),
                e($location->name),
                $this->linkedLocationId
            );
        }

        // Room
        if (!empty($this->linkedRoomId)) {
            $room = Room::find($this->linkedRoomId);
            if (!$room) {
                return '<span class="text-gray-500">Room not found</span>';
            }

            return sprintf(
                '<div class="text-sm"><strong>Room:</strong> %s <a href="/admin/projects/rooms/%d/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">Edit Details →</a></div>',
                e($room->name),
                $this->linkedRoomId
            );
        }

        // No entity linked
        return '<span class="text-gray-500 text-sm">Not linked to any entity</span>';
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

    public function saveAndNextAction(): Action
    {
        return Action::make('saveAndNext')
            ->label('Save & Next')
            ->icon('heroicon-o-arrow-right')
            ->color('success')
            ->size('md')
            ->keyBindings(['mod+enter'])  // Cmd+Enter or Ctrl+Enter for fast workflow
            ->action(function () {
                // Save the current annotation first
                $this->save();

                // Dispatch event to Alpine.js to load the next annotation
                $this->dispatch('load-next-annotation');
            });
    }

    public function save(): void
    {
        try {
            // Get validated form state using proper Filament API
            $data = $this->form->getState();

            $annotationId = $this->originalAnnotation['id'];

            // Check for duplicate view types for location annotations
            if (($this->originalAnnotation['type'] ?? null) === 'location') {
                $locationId = $data['room_location_id'] ?? null;
                $viewType = $data['view_type'] ?? null;
                $viewOrientation = $data['view_orientation'] ?? null;
                $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;

                if ($locationId && $viewType && $pdfPageId) {
                    $pdfPage = \App\Models\PdfPage::find($pdfPageId);
                    if ($pdfPage && $pdfPage->document_id) {
                        $takenViews = $this->getLocationViewTypes($locationId, $pdfPage->document_id);

                        // Build the key to check
                        $checkKey = $viewType;
                        if (in_array($viewType, ['elevation', 'section']) && $viewOrientation) {
                            $checkKey = $viewType . '-' . $viewOrientation;
                        }

                        // Check if this view combination already exists
                        if (isset($takenViews[$checkKey]) && !empty($takenViews[$checkKey])) {
                            $pages = implode(', ', $takenViews[$checkKey]);
                            $viewLabel = $viewType === 'plan'
                                ? 'Plan View'
                                : ucfirst($viewType) . ' View' . ($viewOrientation ? ' - ' . $viewOrientation : '');

                            \Filament\Notifications\Notification::make()
                                ->title('Duplicate View Warning')
                                ->body("A {$viewLabel} already exists for this location on page(s) {$pages}. You are creating an additional view.")
                                ->warning()
                                ->duration(8000)
                                ->send();
                        }
                    }
                }
            }

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
                // Auto-calculate room_id from parent chain for non-room annotations
                $annotationType = $this->originalAnnotation['type'] ?? 'room';
                $parentAnnotationId = $data['parent_annotation_id'] ?? null;
                $roomId = $annotationType === 'room'
                    ? ($data['room_id'] ?? null)
                    : $this->getRoomIdFromParent($parentAnnotationId);

                // Auto-create cabinet_run if cabinet is created directly under a location
                if ($annotationType === 'cabinet' && $parentAnnotationId) {
                    $parentAnnotation = DB::table('pdf_page_annotations')
                        ->where('id', $parentAnnotationId)
                        ->first();

                    // If parent is a location (not a cabinet_run), auto-create cabinet_run
                    if ($parentAnnotation && $parentAnnotation->annotation_type === 'location') {
                        // Create a cabinet run annotation with the same name as the cabinet
                        $newCabinetRun = DB::table('pdf_page_annotations')->insertGetId([
                            'pdf_page_id' => $this->originalAnnotation['pdfPageId'],
                            'parent_annotation_id' => $parentAnnotationId, // Link to location
                            'annotation_type' => 'cabinet_run',
                            'label' => $data['label'] . ' Run', // Add " Run" suffix to differentiate
                            'notes' => 'Auto-created cabinet run for ' . $data['label'],
                            'room_id' => $roomId,
                            'room_location_id' => $data['room_location_id'] ?? null,  // Use room_location_id from form data
                            'cabinet_run_id' => $data['cabinet_run_id'] ?? null,  // Cabinet run entity reference (will be created later)
                            'x' => $this->originalAnnotation['normalizedX'] ?? 0,
                            'y' => $normalizedY ?? 0,
                            'width' => $normalizedWidth ?? 100,
                            'height' => $normalizedHeight ?? 100,
                            'color' => $this->originalAnnotation['color'] ?? '#f59e0b',  // Default amber color
                            'view_type' => $data['view_type'] ?? 'plan',
                            'view_orientation' => $data['view_orientation'] ?? null,
                            'view_scale' => $this->originalAnnotation['viewScale'] ?? null,
                            'inferred_position' => $positionData['inferred_position'] ?? null,
                            'vertical_zone' => $positionData['vertical_zone'] ?? null,
                            'creator_id' => auth()->id(),  // Set creator
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Update parent to point to the new cabinet_run instead of location
                        $parentAnnotationId = $newCabinetRun;
                        $data['parent_annotation_id'] = $newCabinetRun;

                        \Filament\Notifications\Notification::make()
                            ->title('Auto-Created Cabinet Run')
                            ->body("Created cabinet run \"{$data['label']} Run\" to maintain proper hierarchy.")
                            ->success()
                            ->duration(5000)
                            ->send();
                    }
                }

                // Auto-create entity records based on annotation type
                // For location annotations, use room_location_id from dropdown
                // For cabinet_run/cabinet annotations, use location_id
                $roomLocationId = ($annotationType === 'location')
                    ? ($data['room_location_id'] ?? null)
                    : ($data['location_id'] ?? null);

                $cabinetRunId = $data['cabinet_run_id'] ?? null;
                $cabinetSpecificationId = $data['cabinet_specification_id'] ?? null;

                // 1. Location annotation → Create RoomLocation entity ONLY if not selected from dropdown
                // Support multi-view: Check if entity already exists with same name + room_id before creating
                if ($annotationType === 'location' && $roomId && !$roomLocationId) {
                    // Check for existing entity (multi-view support)
                    $existingLocation = \Webkul\Project\Models\RoomLocation::where('room_id', $roomId)
                        ->where('name', $data['label'])
                        ->first();

                    if ($existingLocation) {
                        // Reuse existing entity for multi-view annotation
                        $roomLocationId = $existingLocation->id;
                    } else {
                        // Create new entity
                        $roomLocation = \Webkul\Project\Models\RoomLocation::create([
                            'room_id' => $roomId,
                            'name' => $data['label'],
                            'location_type' => null, // Can be set from form if needed
                            'notes' => $data['notes'] ?? '',
                            'creator_id' => auth()->id(),
                        ]);
                        $roomLocationId = $roomLocation->id;
                    }
                }

                // 2. Cabinet Run annotation → Create CabinetRun entity
                // Support multi-view: Check if entity already exists before creating
                if ($annotationType === 'cabinet_run' && !$cabinetRunId) {
                    // Get room_location_id from parent location annotation
                    $parentLocationId = $this->getRoomLocationIdFromParent($parentAnnotationId);
                    if ($parentLocationId) {
                        // Check for existing entity (multi-view support)
                        $existingCabinetRun = \Webkul\Project\Models\CabinetRun::where('room_location_id', $parentLocationId)
                            ->where('name', $data['label'])
                            ->first();

                        if ($existingCabinetRun) {
                            // Reuse existing entity for multi-view annotation
                            $cabinetRunId = $existingCabinetRun->id;
                        } else {
                            // Create new entity
                            $cabinetRun = \Webkul\Project\Models\CabinetRun::create([
                                'room_location_id' => $parentLocationId,
                                'name' => $data['label'],
                                'notes' => $data['notes'] ?? '',
                                'creator_id' => auth()->id(),
                            ]);
                            $cabinetRunId = $cabinetRun->id;
                        }
                    }
                }

                // 3. Cabinet annotation → Create CabinetSpecification entity
                if ($annotationType === 'cabinet' && !$cabinetSpecificationId) {
                    // Get project_id from PDF page
                    $pdfPage = \App\Models\PdfPage::find($this->originalAnnotation['pdfPageId']);
                    $projectId = $pdfPage?->pdfDocument?->project_id;

                    // Get cabinet_run_id from parent cabinet_run annotation
                    $parentCabinetRunId = $this->getCabinetRunIdFromParent($parentAnnotationId);

                    if ($projectId && $roomId && $parentCabinetRunId) {
                        $cabinetSpec = \Webkul\Project\Models\CabinetSpecification::create([
                            'project_id' => $projectId,
                            'room_id' => $roomId,
                            'cabinet_run_id' => $parentCabinetRunId,
                            'cabinet_number' => $data['label'],
                            'creator_id' => auth()->id(),
                        ]);
                        $cabinetSpecificationId = $cabinetSpec->id;
                    }
                }

                $annotation = \App\Models\PdfPageAnnotation::create([
                    'pdf_page_id'      => $this->originalAnnotation['pdfPageId'],
                    'annotation_type'  => $annotationType,
                    'label'            => $data['label'],
                    'notes'            => $data['notes'] ?? '',
                    'parent_annotation_id' => $parentAnnotationId,
                    'room_id'          => $roomId,
                    'room_location_id' => $roomLocationId,
                    'cabinet_run_id'   => $cabinetRunId,
                    'cabinet_specification_id' => $cabinetSpecificationId,
                    'x'                => $this->originalAnnotation['normalizedX'] ?? 0,
                    'y'                => $normalizedY,
                    'width'            => $normalizedWidth,
                    'height'           => $normalizedHeight,
                    'color'            => $this->originalAnnotation['color'] ?? '#f59e0b',
                    // View types and position detection (from FORM DATA)
                    'view_type'        => $data['view_type'] ?? 'plan',
                    'view_orientation' => $data['view_orientation'] ?? null,
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

                // Build updated annotation with real database ID
                $updatedAnnotation = array_merge($this->originalAnnotation, [
                    'id'                => $annotation->id, // Replace temp ID with real ID
                    'label'             => $data['label'],
                    'notes'             => $data['notes'] ?? '',
                    'parentId'          => $data['parent_annotation_id'] ?? null,  // Include parent ID for UI update
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

            // Auto-calculate room_id from parent chain for non-room annotations
            $parentAnnotationId = $data['parent_annotation_id'] ?? null;
            $roomId = $annotation->annotation_type === 'room'
                ? ($data['room_id'] ?? null)
                : $this->getRoomIdFromParent($parentAnnotationId);

            // Update annotation in database
            $updateData = [
                'label'            => $data['label'],
                'notes'            => $data['notes'] ?? '',
                'parent_annotation_id' => $parentAnnotationId,
                'room_id'          => $roomId,
                'room_location_id' => $data['location_id'] ?? null,
                'cabinet_run_id'   => $data['cabinet_run_id'] ?? null,
                'cabinet_specification_id' => $data['cabinet_specification_id'] ?? null,
                'view_type'        => $data['view_type'] ?? 'plan',
                'view_orientation' => $data['view_orientation'] ?? null,
            ];

            $annotation->update($updateData);

            // CRITICAL: Save related model changes using FilamentPHP v4 pattern
            // The ->relationship() method on Sections automatically loads data,
            // but we must explicitly call saveRelationships() to persist changes
            $this->form->model($annotation)->saveRelationships();

            // SYNC METADATA ACROSS ALL INSTANCES OF THIS ENTITY
            // If this is a location annotation, find all other location annotations
            // with the same room_location_id and update their metadata too
            $this->syncMetadataAcrossPages($annotation, $updateData);

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
                'parentId'          => $data['parent_annotation_id'] ?? null,  // Include parent ID for UI update
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

        // Set linked entity IDs for Entity Details tab (avoids form->getState() calls)
        $this->linkedRoomId = $annotation['roomId'] ?? null;
        $this->linkedLocationId = $annotation['locationId'] ?? null;
        $this->linkedCabinetRunId = $annotation['cabinetRunId'] ?? null;
        $this->linkedCabinetSpecId = $annotation['cabinetSpecId'] ?? null;

        // CRITICAL: Set the annotation model property for FilamentPHP v4 relationship binding
        // This must happen BEFORE filling the form so relationship fields can load properly
        $annotationId = $annotation['id'];
        if (!is_string($annotationId) || !str_starts_with($annotationId, 'temp_')) {
            $this->annotationModel = \App\Models\PdfPageAnnotation::find($annotationId);
        } else {
            $this->annotationModel = null;  // New annotation, no model yet
        }

        // Fill form with annotation data using Filament Forms API
        // The model is now set, so relationship fields will load correctly
        $this->form->fill([
            'label'                  => $annotation['label'] ?? '',
            'notes'                  => $annotation['notes'] ?? '',
            'parent_annotation_id'   => $annotation['parentId'] ?? null,
            'room_id'                => $annotation['roomId'] ?? null,
            'location_id'            => $annotation['locationId'] ?? null,
            'cabinet_run_id'         => $annotation['cabinetRunId'] ?? null,
            'measurement_width'      => $annotation['measurementWidth'] ?? null,
            'measurement_height'     => $annotation['measurementHeight'] ?? null,
            // Multi-view support fields
            'view_type'              => $annotation['viewType'] ?? 'plan',
            'view_orientation'       => $annotation['viewOrientation'] ?? null,
            'view_scale'             => $annotation['viewScale'] ?? null,
            // Cabinet-specific fields
            'inferred_position'      => $annotation['inferredPosition'] ?? null,
            'vertical_zone'          => $annotation['verticalZone'] ?? null,
        ]);

        $this->showModal = true;
    }

    #[On('update-annotation-position')]
    public function handleUpdateAnnotationPosition(
        int|string $annotationId,
        float $pdfX,
        float $pdfY,
        float $pdfWidth,
        float $pdfHeight,
        float $normalizedX,
        float $normalizedY
    ): void {
        try {
            // Skip if this is a temporary annotation (not yet saved)
            if (is_string($annotationId) && str_starts_with($annotationId, 'temp_')) {
                return;
            }

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

    /**
     * Get room_id by traversing parent annotation chain
     */
    protected function getRoomIdFromParent(?int $parentAnnotationId): ?int
    {
        if (!$parentAnnotationId) {
            return null;
        }

        $annotation = \App\Models\PdfPageAnnotation::find($parentAnnotationId);

        if (!$annotation) {
            return null;
        }

        // If this annotation is a room, return its room_id
        if ($annotation->annotation_type === 'room') {
            return $annotation->room_id;
        }

        // Otherwise, recursively check parent
        if ($annotation->parent_annotation_id) {
            return $this->getRoomIdFromParent($annotation->parent_annotation_id);
        }

        // Fallback: return the annotation's room_id if it has one
        return $annotation->room_id;
    }

    /**
     * Get room_location_id from parent location annotation
     */
    protected function getRoomLocationIdFromParent(?int $parentAnnotationId): ?int
    {
        if (!$parentAnnotationId) {
            return null;
        }

        $annotation = \App\Models\PdfPageAnnotation::find($parentAnnotationId);

        if (!$annotation) {
            return null;
        }

        // If this annotation is a location, return its room_location_id
        if ($annotation->annotation_type === 'location') {
            return $annotation->room_location_id;
        }

        // Otherwise, recursively check parent
        if ($annotation->parent_annotation_id) {
            return $this->getRoomLocationIdFromParent($annotation->parent_annotation_id);
        }

        return null;
    }

    /**
     * Get cabinet_run_id from parent cabinet_run annotation
     */
    protected function getCabinetRunIdFromParent(?int $parentAnnotationId): ?int
    {
        if (!$parentAnnotationId) {
            return null;
        }

        $annotation = \App\Models\PdfPageAnnotation::find($parentAnnotationId);

        if (!$annotation) {
            return null;
        }

        // If this annotation is a cabinet_run, return its cabinet_run_id
        if ($annotation->annotation_type === 'cabinet_run') {
            return $annotation->cabinet_run_id;
        }

        // Otherwise, recursively check parent
        if ($annotation->parent_annotation_id) {
            return $this->getCabinetRunIdFromParent($annotation->parent_annotation_id);
        }

        return null;
    }

    /**
     * Get HTML for hierarchy breadcrumb display
     */
    protected function getHierarchyPathHtml(): string
    {
        if (empty($this->originalAnnotation['id'])) {
            return '<span class="text-gray-500">New annotation</span>';
        }

        $path = $this->buildHierarchyPath($this->originalAnnotation['id']);

        if (empty($path)) {
            return '<span class="text-gray-500">Top level</span>';
        }

        $breadcrumbs = [];
        foreach ($path as $item) {
            $color = match($item['type']) {
                'room' => 'bg-blue-100 text-blue-800',
                'location' => 'bg-green-100 text-green-800',
                'cabinet_run' => 'bg-purple-100 text-purple-800',
                'cabinet' => 'bg-orange-100 text-orange-800',
                default => 'bg-gray-100 text-gray-800',
            };

            $breadcrumbs[] = sprintf(
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
                $color,
                htmlspecialchars($item['label'])
            );
        }

        return implode(' <span class="text-gray-400">→</span> ', $breadcrumbs);
    }

    /**
     * Build hierarchy path from annotation ID up to root
     */
    protected function buildHierarchyPath(int|string $annotationId, array $path = []): array
    {
        // Don't query if it's a temp ID
        if (is_string($annotationId) && str_starts_with($annotationId, 'temp_')) {
            return $path;
        }

        $annotation = \App\Models\PdfPageAnnotation::find($annotationId);

        if (!$annotation) {
            return $path;
        }

        // Add current annotation to path
        array_unshift($path, [
            'id' => $annotation->id,
            'label' => $annotation->label,
            'type' => $annotation->annotation_type,
        ]);

        // Recursively get parent
        if ($annotation->parent_annotation_id) {
            return $this->buildHierarchyPath($annotation->parent_annotation_id, $path);
        }

        return $path;
    }

    /**
     * Get available parent annotations based on annotation type
     */
    protected function getAvailableParents(): array
    {
        if (!$this->projectId || !$this->annotationType) {
            return [];
        }

        // Get PDF page ID from original annotation
        $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;
        if (!$pdfPageId) {
            return [];
        }

        // Get PDF document ID to search across all pages
        $pdfPage = \App\Models\PdfPage::find($pdfPageId);
        if (!$pdfPage || !$pdfPage->document_id) {
            return [];
        }

        // Determine valid parent types based on annotation type
        $validParentTypes = match($this->annotationType) {
            'location' => ['room'],
            'cabinet_run' => ['location'],
            'cabinet' => ['cabinet_run'],
            default => [],
        };

        if (empty($validParentTypes)) {
            return [];
        }

        // Query annotations across ALL pages in the same PDF document
        // This allows locations on page 3 to have room parents from page 2
        return \App\Models\PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfPage) {
                $query->where('document_id', $pdfPage->document_id);
            })
            ->whereIn('annotation_type', $validParentTypes)
            ->where('id', '!=', $this->originalAnnotation['id'] ?? 0) // Exclude self
            ->orderBy('label')
            ->get()
            ->mapWithKeys(function ($annotation) {
                // Include page number in label for context
                $pageNumber = $annotation->pdfPage->page_number ?? '?';
                return [$annotation->id => $annotation->label . ' (Page ' . $pageNumber . ')'];
            })
            ->toArray();
    }

    /**
     * Sync metadata across all instances of the same entity on different pages
     *
     * For example, "Sink Wall" on page 2 (plan) and page 3 (elevation) should
     * have the same parent, label, notes - only view_type and coordinates differ
     */
    protected function syncMetadataAcrossPages(\App\Models\PdfPageAnnotation $annotation, array $updateData): void
    {
        // Determine the entity ID field to match on
        $entityField = match($annotation->annotation_type) {
            'room' => 'room_id',
            'location' => 'room_location_id',
            'cabinet_run' => 'cabinet_run_id',
            'cabinet' => 'cabinet_specification_id',
            default => null,
        };

        if (!$entityField || !$annotation->$entityField) {
            return; // No entity to match, skip sync
        }

        $entityId = $annotation->$entityField;

        // Get PDF document to search across all pages
        $pdfDocumentId = $annotation->pdfPage->document_id ?? null;
        if (!$pdfDocumentId) {
            return;
        }

        // Find all other annotations of the same type with the same entity ID
        $siblingAnnotations = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                $query->where('document_id', $pdfDocumentId);
            })
            ->where('annotation_type', $annotation->annotation_type)
            ->where($entityField, $entityId)
            ->where('id', '!=', $annotation->id) // Exclude the one we just updated
            ->get();

        if ($siblingAnnotations->isEmpty()) {
            return; // No siblings to sync
        }

        // Prepare metadata to sync (exclude page-specific fields)
        $syncData = [
            'label'            => $updateData['label'],
            'notes'            => $updateData['notes'] ?? null,
            'parent_annotation_id' => $updateData['parent_annotation_id'] ?? null,
            'room_id'          => $updateData['room_id'] ?? null,
            'room_location_id' => $updateData['room_location_id'] ?? null,
            'cabinet_run_id'   => $updateData['cabinet_run_id'] ?? null,
            'cabinet_specification_id' => $updateData['cabinet_specification_id'] ?? null,
            // NOTE: We do NOT sync view_type, view_orientation, x, y, width, height
            // Those are page-specific
        ];

        // However, parent relationships need special handling across pages
        // The parent on page 2 might be annotation ID 10, but on page 3 it might be ID 25
        // We need to find the equivalent parent on each page
        if ($syncData['parent_annotation_id']) {
            $parentAnnotation = \App\Models\PdfPageAnnotation::find($syncData['parent_annotation_id']);
            if ($parentAnnotation) {
                // Get the parent's entity field
                $parentEntityField = match($parentAnnotation->annotation_type) {
                    'room' => 'room_id',
                    'location' => 'room_location_id',
                    'cabinet_run' => 'cabinet_run_id',
                    'cabinet' => 'cabinet_specification_id',
                    default => null,
                };

                if ($parentEntityField && $parentAnnotation->$parentEntityField) {
                    $parentEntityId = $parentAnnotation->$parentEntityField;

                    // For each sibling, find the equivalent parent on its page
                    foreach ($siblingAnnotations as $sibling) {
                        $syncDataForSibling = $syncData;

                        // Find parent annotation on sibling's page with same entity
                        $equivalentParent = \App\Models\PdfPageAnnotation::where('pdf_page_id', $sibling->pdf_page_id)
                            ->where('annotation_type', $parentAnnotation->annotation_type)
                            ->where($parentEntityField, $parentEntityId)
                            ->first();

                        if ($equivalentParent) {
                            $syncDataForSibling['parent_annotation_id'] = $equivalentParent->id;
                        } else {
                            // If parent doesn't exist on this page, keep it null or existing value
                            $syncDataForSibling['parent_annotation_id'] = null;
                        }

                        $sibling->update($syncDataForSibling);

                        \Log::info('Synced annotation metadata across pages', [
                            'from_annotation_id' => $annotation->id,
                            'from_page' => $annotation->pdfPage->page_number,
                            'to_annotation_id' => $sibling->id,
                            'to_page' => $sibling->pdfPage->page_number,
                            'entity_type' => $annotation->annotation_type,
                            'entity_id' => $entityId,
                        ]);
                    }

                    return; // Early return since we handled parent relationships specially
                }
            }
        }

        // If no parent or parent handling failed, just sync without parent
        $syncDataWithoutParent = array_diff_key($syncData, ['parent_annotation_id' => null]);
        foreach ($siblingAnnotations as $sibling) {
            $sibling->update($syncDataWithoutParent);
        }
    }

    /**
     * Get existing view types for a given location across all pages in the document
     *
     * @param int $locationId The room_location_id to check
     * @param int $pdfDocumentId The PDF document ID to search within
     * @return array Map of view combinations to page arrays
     *               For plan: ['plan' => [2]]
     *               For elevation/section: ['elevation-front' => [3], 'section-A-A' => [5]]
     *               For detail: ['detail' => [4, 6, 8]] (multiple allowed)
     */
    protected function getLocationViewTypes(int $locationId, int $pdfDocumentId): array
    {
        $annotations = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                $query->where('document_id', $pdfDocumentId);
            })
            ->where('room_location_id', $locationId)
            ->where('annotation_type', 'location')
            ->get();

        $result = [];
        foreach ($annotations as $ann) {
            // For elevation and section, include orientation in the key
            if (in_array($ann->view_type, ['elevation', 'section']) && $ann->view_orientation) {
                $key = $ann->view_type . '-' . $ann->view_orientation;
            } else {
                $key = $ann->view_type;
            }

            // Store as array of pages (to handle multiple detail views)
            if (!isset($result[$key])) {
                $result[$key] = [];
            }
            $result[$key][] = $ann->pdfPage->page_number;
        }

        return $result;
    }

    /**
     * Auto-link form field to existing entity
     * Called when user clicks "Auto-Link to Existing" button
     *
     * @param string $fieldName Field name to populate (room_location_id or cabinet_run_id)
     * @param int $entityId Entity ID to link to
     */
    public function linkToExistingEntity(string $fieldName, int $entityId): void
    {
        // Set the field value in the form
        $this->form->fill([
            $fieldName => $entityId,
        ]);

        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Linked to existing entity')
            ->body('The annotation has been linked to the existing entity.')
            ->success()
            ->send();
    }

    /**
     * Get entity detection status to display in form
     * Shows whether annotation will create new entity or link to existing one
     *
     * @param callable $get Form data getter
     * @return \Illuminate\Support\HtmlString
     */
    protected function getEntityDetectionStatus(callable $get): \Illuminate\Support\HtmlString
    {
        $label = $get('label');
        $parentAnnotationId = $get('parent_annotation_id');

        if (empty($label)) {
            return new \Illuminate\Support\HtmlString(
                '<span class="text-gray-500 dark:text-gray-400">Enter a label to check entity status</span>'
            );
        }

        // For locations: check if RoomLocation exists with same name + room_id
        if ($this->annotationType === 'location') {
            $roomId = $this->getRoomIdFromParent($parentAnnotationId);

            if (!$roomId) {
                return new \Illuminate\Support\HtmlString(
                    '<span class="text-gray-500 dark:text-gray-400">Select a parent room first</span>'
                );
            }

            $existingLocation = \Webkul\Project\Models\RoomLocation::where('room_id', $roomId)
                ->where('name', $label)
                ->first();

            if ($existingLocation) {
                // Find which pages have annotations using this location
                $pdfPage = \App\Models\PdfPage::find($this->originalAnnotation['pdfPageId']);
                $pdfDocumentId = $pdfPage?->document_id;

                $annotationsWithLocation = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                        $query->where('document_id', $pdfDocumentId);
                    })
                    ->where('room_location_id', $existingLocation->id)
                    ->with('pdfPage')
                    ->get();

                $pages = $annotationsWithLocation->pluck('pdfPage.page_number')->unique()->sort()->values()->toArray();
                $pagesStr = implode(', ', $pages);

                return new \Illuminate\Support\HtmlString(
                    '<div class="flex flex-col gap-2">' .
                    '<div class="flex items-center gap-2">' .
                    '<svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' .
                    '</svg>' .
                    '<span class="text-success-600 dark:text-success-400">' .
                    'Will link to existing: <strong>' . e($existingLocation->name) . '</strong>' .
                    (!empty($pages) ? ' (Pages: ' . $pagesStr . ')' : '') .
                    '</span>' .
                    '</div>' .
                    '<button ' .
                    'type="button" ' .
                    'wire:click="linkToExistingEntity(\'room_location_id\', ' . $existingLocation->id . ')" ' .
                    'class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-sm fi-btn-size-sm gap-1 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-btn-action" ' .
                    'style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">' .
                    '<svg class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">' .
                    '<path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z"/>' .
                    '<path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z"/>' .
                    '</svg>' .
                    '<span class="fi-btn-label">Auto-Link to Existing</span>' .
                    '</button>' .
                    '</div>'
                );
            } else {
                return new \Illuminate\Support\HtmlString(
                    '<div class="flex items-center gap-2">' .
                    '<svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>' .
                    '</svg>' .
                    '<span class="text-primary-600 dark:text-primary-400">' .
                    'Will create new location: <strong>' . e($label) . '</strong>' .
                    '</span>' .
                    '</div>'
                );
            }
        }

        // For cabinet_runs: check if CabinetRun exists with same name + room_location_id
        if ($this->annotationType === 'cabinet_run') {
            $roomLocationId = $this->getRoomLocationIdFromParent($parentAnnotationId);

            if (!$roomLocationId) {
                return new \Illuminate\Support\HtmlString(
                    '<span class="text-gray-500 dark:text-gray-400">Select a parent location first</span>'
                );
            }

            $existingCabinetRun = \Webkul\Project\Models\CabinetRun::where('room_location_id', $roomLocationId)
                ->where('name', $label)
                ->first();

            if ($existingCabinetRun) {
                // Find which pages have annotations using this cabinet run
                $pdfPage = \App\Models\PdfPage::find($this->originalAnnotation['pdfPageId']);
                $pdfDocumentId = $pdfPage?->document_id;

                $annotationsWithCabinetRun = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                        $query->where('document_id', $pdfDocumentId);
                    })
                    ->where('cabinet_run_id', $existingCabinetRun->id)
                    ->with('pdfPage')
                    ->get();

                $pages = $annotationsWithCabinetRun->pluck('pdfPage.page_number')->unique()->sort()->values()->toArray();
                $pagesStr = implode(', ', $pages);

                return new \Illuminate\Support\HtmlString(
                    '<div class="flex flex-col gap-2">' .
                    '<div class="flex items-center gap-2">' .
                    '<svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' .
                    '</svg>' .
                    '<span class="text-success-600 dark:text-success-400">' .
                    'Will link to existing: <strong>' . e($existingCabinetRun->name) . '</strong>' .
                    (!empty($pages) ? ' (Pages: ' . $pagesStr . ')' : '') .
                    '</span>' .
                    '</div>' .
                    '<button ' .
                    'type="button" ' .
                    'wire:click="linkToExistingEntity(\'cabinet_run_id\', ' . $existingCabinetRun->id . ')" ' .
                    'class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-sm fi-btn-size-sm gap-1 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-btn-action" ' .
                    'style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">' .
                    '<svg class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">' .
                    '<path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z"/>' .
                    '<path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z"/>' .
                    '</svg>' .
                    '<span class="fi-btn-label">Auto-Link to Existing</span>' .
                    '</button>' .
                    '</div>'
                );
            } else {
                return new \Illuminate\Support\HtmlString(
                    '<div class="flex items-center gap-2">' .
                    '<svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>' .
                    '</svg>' .
                    '<span class="text-primary-600 dark:text-primary-400">' .
                    'Will create new cabinet run: <strong>' . e($label) . '</strong>' .
                    '</span>' .
                    '</div>'
                );
            }
        }

        return new \Illuminate\Support\HtmlString('');
    }

    private function close(): void
    {
        $this->showModal = false;
        $this->reset(['data', 'annotationType', 'projectId', 'originalAnnotation']);

        // Notify Alpine component that modal is closed
        $this->dispatch('annotation-editor-closed');

        // Refresh the project tree to show updated hierarchy
        $this->dispatch('refresh-project-tree');
    }
}
