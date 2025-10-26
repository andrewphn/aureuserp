<?php

namespace Webkul\Project\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
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
use Webkul\Project\Services\AnnotationHierarchyService;
use Webkul\Project\Services\AnnotationSaveService;
use Webkul\Project\Services\AnnotationSyncService;
use Webkul\Project\Services\EntityDetectionService;
use Webkul\Project\Services\EntityManagementService;
use Webkul\Project\Services\ViewTypeTrackerService;
use Webkul\Project\Utils\PositionInferenceUtil;

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

    // Hierarchy path for breadcrumb display
    public string $hierarchyPath = '';

    // Entity-centric properties for new workflow
    public string $linkMode = 'create'; // 'create' or 'existing'
    public ?int $linkedEntityId = null; // ID of selected existing entity
    public ?array $entityData = []; // Entity properties for create/edit

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

                            // ============================================
                            // SECTION: Entity Selection & Linking (NEW)
                            // ============================================
                            Section::make('Entity Selection & Linking')
                                ->description('Link to an existing entity or create a new one')
                                ->icon('heroicon-o-link')
                                ->schema([
                                    // Link Mode Radio Buttons
                                    Radio::make('link_mode')
                                        ->label('How would you like to proceed?')
                                        ->options([
                                            'existing' => 'Link to existing entity',
                                            'create' => 'Create new entity',
                                        ])
                                        ->default('create')
                                        ->live()
                                        ->inline()
                                        ->required(),

                                    // Hierarchical Entity Selector (for "Link to existing")
                                    Select::make('linked_entity_id')
                                        ->label('Select Entity')
                                        ->helperText('Choose an existing entity from the project hierarchy')
                                        ->options(function () {
                                            $entityManagement = new EntityManagementService();
                                            return $entityManagement->getHierarchicalEntityOptions($this->annotationType, $this->projectId);
                                        })
                                        ->searchable()
                                        ->visible(fn (callable $get) => $get('link_mode') === 'existing')
                                        ->required(fn (callable $get) => $get('link_mode') === 'existing')
                                        ->live(),

                                    // Parent Entity Selector (for "Create new")
                                    Select::make('parent_annotation_id')
                                        ->label('Parent Entity')
                                        ->helperText(function () {
                                            return match($this->annotationType) {
                                                'room' => 'Rooms don\'t have parents',
                                                'location' => 'Select the room this location belongs to',
                                                'cabinet_run' => 'Select the location this cabinet run belongs to',
                                                'cabinet' => 'Select the cabinet run this cabinet belongs to',
                                                default => 'Select parent entity',
                                            };
                                        })
                                        ->options(function () {
                                            $options = AnnotationHierarchyService::getAvailableParents(
                                                $this->projectId,
                                                $this->annotationType,
                                                $this->originalAnnotation['pdfPageId'] ?? null,
                                                $this->originalAnnotation['id'] ?? null
                                            );

                                            // If current value is set and not in options, add it
                                            $currentValue = $this->data['parent_annotation_id'] ?? null;
                                            if ($currentValue && !isset($options[$currentValue])) {
                                                $annotation = \App\Models\PdfPageAnnotation::find($currentValue);
                                                if ($annotation) {
                                                    $pageNumber = $annotation->pdfPage->page_number ?? '?';
                                                    $options[$currentValue] = $annotation->label . ' (Page ' . $pageNumber . ')';
                                                }
                                            }

                                            return $options;
                                        })
                                        ->searchable()
                                        ->placeholder('None (top level)')
                                        ->nullable()
                                        ->visible(fn (callable $get) => $get('link_mode') === 'create' && $this->annotationType !== 'room')
                                        ->live(),
                                ])
                                ->collapsible()
                                ->collapsed(false),

                            // ============================================
                            // SECTION: Room Entity Properties
                            // ============================================
                            Section::make('Room Properties')
                                ->description('Define the room entity details')
                                ->icon('heroicon-o-home')
                                ->schema([
                                    Tabs::make('room_tabs')
                                        ->tabs([
                                            // Basic Info Tab
                                            Tab::make('Basic Information')
                                                ->icon('heroicon-o-information-circle')
                                                ->schema([
                                                    TextInput::make('entity.name')
                                                        ->label('Room Name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->live(onBlur: true),

                                                    Select::make('entity.room_type')
                                                        ->label('Room Type')
                                                        ->options([
                                                            'kitchen' => 'Kitchen',
                                                            'bathroom' => 'Bathroom',
                                                            'bedroom' => 'Bedroom',
                                                            'living' => 'Living Room',
                                                            'dining' => 'Dining Room',
                                                            'office' => 'Office',
                                                            'utility' => 'Utility',
                                                            'other' => 'Other',
                                                        ])
                                                        ->searchable(),

                                                    TextInput::make('entity.floor_number')
                                                        ->label('Floor Number')
                                                        ->maxLength(50),
                                                ]),

                                            // PDF Reference Tab
                                            Tab::make('PDF Reference')
                                                ->icon('heroicon-o-document')
                                                ->schema([
                                                    TextInput::make('entity.pdf_page_number')
                                                        ->label('PDF Page Number')
                                                        ->numeric()
                                                        ->minValue(1),

                                                    TextInput::make('entity.pdf_room_label')
                                                        ->label('PDF Room Label')
                                                        ->maxLength(100)
                                                        ->helperText('Label as it appears in the PDF'),

                                                    TextInput::make('entity.pdf_detail_number')
                                                        ->label('PDF Detail Number')
                                                        ->maxLength(50),

                                                    Textarea::make('entity.pdf_notes')
                                                        ->label('PDF Notes')
                                                        ->rows(3),
                                                ]),

                                            // Notes Tab
                                            Tab::make('Notes')
                                                ->icon('heroicon-o-pencil')
                                                ->schema([
                                                    Textarea::make('entity.notes')
                                                        ->label('General Notes')
                                                        ->rows(4),
                                                ]),
                                        ]),
                                ])
                                ->visible(fn (callable $get) => $get('link_mode') === 'create' && $this->annotationType === 'room')
                                ->collapsible()
                                ->collapsed(false),

                            // ============================================
                            // SECTION: Location Entity Properties
                            // ============================================
                            Section::make('Location Properties')
                                ->description('Define the location entity details')
                                ->icon('heroicon-o-map-pin')
                                ->schema([
                                    Tabs::make('location_tabs')
                                        ->tabs([
                                            // Basic Info Tab
                                            Tab::make('Basic Information')
                                                ->icon('heroicon-o-information-circle')
                                                ->schema([
                                                    TextInput::make('entity.name')
                                                        ->label('Location Name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->live(onBlur: true)
                                                        ->helperText('e.g., "Sink Wall", "Island", "Pantry Wall"'),

                                                    Select::make('entity.location_type')
                                                        ->label('Location Type')
                                                        ->options([
                                                            'wall' => 'Wall',
                                                            'island' => 'Island',
                                                            'peninsula' => 'Peninsula',
                                                            'corner' => 'Corner',
                                                            'alcove' => 'Alcove',
                                                            'other' => 'Other',
                                                        ])
                                                        ->searchable(),

                                                    TextInput::make('entity.sequence')
                                                        ->label('Sequence')
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->helperText('Order of this location in the room'),
                                                ]),

                                            // Details Tab
                                            Tab::make('Details')
                                                ->icon('heroicon-o-adjustments-horizontal')
                                                ->schema([
                                                    TextInput::make('entity.elevation_reference')
                                                        ->label('Elevation Reference')
                                                        ->maxLength(100)
                                                        ->helperText('Reference to elevation drawing/view'),

                                                    Textarea::make('entity.notes')
                                                        ->label('Notes')
                                                        ->rows(4),
                                                ]),
                                        ]),
                                ])
                                ->visible(fn (callable $get) => $get('link_mode') === 'create' && $this->annotationType === 'location')
                                ->collapsible()
                                ->collapsed(false),

                            // ============================================
                            // SECTION: Cabinet Run Entity Properties
                            // ============================================
                            Section::make('Cabinet Run Properties')
                                ->description('Define the cabinet run entity details')
                                ->icon('heroicon-o-squares-2x2')
                                ->schema([
                                    Tabs::make('cabinet_run_tabs')
                                        ->tabs([
                                            // Basic Info Tab
                                            Tab::make('Basic Information')
                                                ->icon('heroicon-o-information-circle')
                                                ->schema([
                                                    TextInput::make('entity.name')
                                                        ->label('Cabinet Run Name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->live(onBlur: true)
                                                        ->helperText('e.g., "Upper Cabinets", "Base Cabinets", "Tall Units"'),

                                                    Select::make('entity.run_type')
                                                        ->label('Run Type')
                                                        ->options([
                                                            'base' => 'Base Cabinets',
                                                            'wall' => 'Wall Cabinets',
                                                            'tall' => 'Tall Cabinets',
                                                            'specialty' => 'Specialty',
                                                        ])
                                                        ->searchable(),
                                                ]),

                                            // Measurements Tab
                                            Tab::make('Measurements')
                                                ->icon('heroicon-o-calculator')
                                                ->schema([
                                                    TextInput::make('entity.total_linear_feet')
                                                        ->label('Total Linear Feet')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->minValue(0)
                                                        ->suffix('ft'),

                                                    TextInput::make('entity.start_wall_measurement')
                                                        ->label('Start Wall Measurement')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('inches')
                                                        ->helperText('Starting point on wall'),

                                                    TextInput::make('entity.end_wall_measurement')
                                                        ->label('End Wall Measurement')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('inches')
                                                        ->helperText('Ending point on wall'),
                                                ]),

                                            // Notes Tab
                                            Tab::make('Notes')
                                                ->icon('heroicon-o-pencil')
                                                ->schema([
                                                    Textarea::make('entity.notes')
                                                        ->label('Notes')
                                                        ->rows(4),
                                                ]),
                                        ]),
                                ])
                                ->visible(fn (callable $get) => $get('link_mode') === 'create' && $this->annotationType === 'cabinet_run')
                                ->collapsible()
                                ->collapsed(false),

                            // ============================================
                            // SECTION: Cabinet Entity Properties
                            // ============================================
                            Section::make('Cabinet Properties')
                                ->description('Define the cabinet specification entity details')
                                ->icon('heroicon-o-cube')
                                ->schema([
                                    Tabs::make('cabinet_tabs')
                                        ->tabs([
                                            // Basic Info Tab
                                            Tab::make('Basic Information')
                                                ->icon('heroicon-o-information-circle')
                                                ->schema([
                                                    TextInput::make('entity.cabinet_number')
                                                        ->label('Cabinet Number')
                                                        ->required()
                                                        ->maxLength(50)
                                                        ->live(onBlur: true)
                                                        ->helperText('e.g., "WC-01", "BC-12"'),

                                                    TextInput::make('entity.position_in_run')
                                                        ->label('Position in Run')
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->helperText('Sequence number within the cabinet run'),
                                                ]),

                                            // Dimensions Tab
                                            Tab::make('Dimensions')
                                                ->icon('heroicon-o-arrows-pointing-out')
                                                ->schema([
                                                    TextInput::make('entity.length_inches')
                                                        ->label('Length')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('inches'),

                                                    TextInput::make('entity.width_inches')
                                                        ->label('Width')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('inches'),

                                                    TextInput::make('entity.depth_inches')
                                                        ->label('Depth')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('inches'),

                                                    TextInput::make('entity.height_inches')
                                                        ->label('Height')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('inches'),

                                                    TextInput::make('entity.linear_feet')
                                                        ->label('Linear Feet')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->suffix('ft'),
                                                ]),

                                            // Pricing Tab
                                            Tab::make('Pricing')
                                                ->icon('heroicon-o-currency-dollar')
                                                ->schema([
                                                    TextInput::make('entity.quantity')
                                                        ->label('Quantity')
                                                        ->numeric()
                                                        ->default(1)
                                                        ->minValue(1),

                                                    TextInput::make('entity.unit_price_per_lf')
                                                        ->label('Unit Price per Linear Foot')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->prefix('$'),

                                                    TextInput::make('entity.total_price')
                                                        ->label('Total Price')
                                                        ->numeric()
                                                        ->step(0.01)
                                                        ->prefix('$'),
                                                ]),

                                            // Notes Tab
                                            Tab::make('Notes')
                                                ->icon('heroicon-o-pencil')
                                                ->schema([
                                                    Textarea::make('entity.hardware_notes')
                                                        ->label('Hardware Notes')
                                                        ->rows(3),

                                                    Textarea::make('entity.custom_modifications')
                                                        ->label('Custom Modifications')
                                                        ->rows(3),

                                                    Textarea::make('entity.shop_notes')
                                                        ->label('Shop Notes')
                                                        ->rows(3),
                                                ]),
                                        ]),
                                ])
                                ->visible(fn (callable $get) => $get('link_mode') === 'create' && $this->annotationType === 'cabinet')
                                ->collapsible()
                                ->collapsed(false),

                            // ============================================
                            // SECTION: Context & Hierarchy (OLD - TO BE REMOVED)
                            // ============================================
                            Section::make('Context & Hierarchy OLD')
                                ->description('Define how this annotation fits into the project structure')
                                ->icon('heroicon-o-folder-open')
                                ->schema([
                                    // Hierarchy breadcrumb display
                                    Placeholder::make('hierarchy_path')
                                        ->label('Hierarchy')
                                        ->content(fn () => new \Illuminate\Support\HtmlString($this->hierarchyPath))
                                        ->visible(fn () => !empty($this->hierarchyPath)),

                                    // Parent annotation selector
                                    Select::make('parent_annotation_id')
                                        ->label('Parent Annotation')
                                        ->helperText('Change which annotation this belongs to')
                                        ->options(function () {
                                            $options = AnnotationHierarchyService::getAvailableParents(
                                                $this->projectId,
                                                $this->annotationType,
                                                $this->originalAnnotation['pdfPageId'] ?? null,
                                                $this->originalAnnotation['id'] ?? null
                                            );

                                            // If current value is set and not in options, add it
                                            $currentValue = $this->data['parent_annotation_id'] ?? null;
                                            if ($currentValue && !isset($options[$currentValue])) {
                                                $annotation = \App\Models\PdfPageAnnotation::find($currentValue);
                                                if ($annotation) {
                                                    $pageNumber = $annotation->pdfPage->page_number ?? '?';
                                                    $options[$currentValue] = $annotation->label . ' (Page ' . $pageNumber . ')';
                                                }
                                            }

                                            return $options;
                                        })
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

                                    TextInput::make('label')
                                        ->label('Label')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true),

                                    // Entity detection status - shows if annotation will create new or link to existing entity
                                    Placeholder::make('entity_detection_status')
                                        ->label('Entity Status')
                                        ->content(function (callable $get) {
                                            return EntityDetectionService::getEntityDetectionStatus(
                                                $this->annotationType,
                                                $get('label'),
                                                $get('parent_annotation_id'),
                                                $this->originalAnnotation
                                            );
                                        })
                                        ->visible(fn () => in_array($this->annotationType, ['location', 'cabinet_run'])),
                                ])
                                ->collapsible()
                                ->compact(),

                            // ============================================
                            // SECTION: Entity Linking
                            // ============================================
                            Section::make('Entity Linking')
                                ->description('Link this annotation to rooms, locations, or cabinet runs')
                                ->icon('heroicon-o-link')
                                ->schema([

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
                    $roomId = AnnotationHierarchyService::getRoomIdFromParent($get('parent_annotation_id'));

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
                    $roomId = AnnotationHierarchyService::getRoomIdFromParent($get('parent_annotation_id'));

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
                                ])
                                ->collapsible()
                                ->compact(),

                            // ============================================
                            // SECTION: Measurements & Notes
                            // ============================================
                            Section::make('Measurements & Notes')
                                ->description('Record physical dimensions and additional notes')
                                ->icon('heroicon-o-pencil-square')
                                ->schema([
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
                                ->collapsible()
                                ->compact(),

                            // ============================================
                            // SECTION: View-Specific Metadata (Optional)
                            // ============================================
                            Section::make('View-Specific Metadata')
                                ->description('Optional: Specify view type, orientation, and scale for this specific annotation')
                                ->icon('heroicon-o-eye')
                                ->collapsible()
                                ->collapsed(true)
                                ->compact()
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
                            $takenViews = ViewTypeTrackerService::getLocationViewTypes($locationId, $pdfPage->document_id);

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
                            $takenViews = ViewTypeTrackerService::getLocationViewTypes($locationId, $pdfPage->document_id);

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
                                ]),

                            // ============================================
                            // SECTION: Linked Entity Summary
                            // ============================================
                            Section::make('Linked Entity Summary')
                                ->description('View linked database entity information')
                                ->icon('heroicon-o-check-badge')
                                ->schema([
                                    // Linked Entity Summary - Read-only display with edit link
                                    Placeholder::make('linked_entity_summary')
                                        ->label('Linked Entity')
                                        ->content(fn () => new \Illuminate\Support\HtmlString($this->getLinkedEntitySummary()))
                                        ->visible(fn () => $this->hasLinkedEntity())
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->compact()
                                ->visible(fn () => $this->hasLinkedEntity()),

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
            $projectId = $room?->project_id;

            return sprintf(
                '<div class="space-y-1 text-sm"><div><strong>Room:</strong> %s</div><div><strong>Location:</strong> %s</div><div><strong>Cabinet Run:</strong> %s</div><div><strong>Cabinet:</strong> %s</div>%s</div>',
                e($room?->name ?? 'N/A'),
                e($location?->name ?? 'N/A'),
                e($run?->name ?? 'N/A'),
                e($cabinet->cabinet_number ?? 'N/A'),
                $projectId ? '<div class="mt-2"><a href="/admin/project/projects/' . $projectId . '/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">View in Project →</a></div>' : ''
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
            $projectId = $room?->project_id;

            return sprintf(
                '<div class="space-y-1 text-sm"><div><strong>Room:</strong> %s</div><div><strong>Location:</strong> %s</div><div><strong>Cabinet Run:</strong> %s</div>%s</div>',
                e($room?->name ?? 'N/A'),
                e($location?->name ?? 'N/A'),
                e($run->name),
                $projectId ? '<div class="mt-2"><a href="/admin/project/projects/' . $projectId . '/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">View in Project →</a></div>' : ''
            );
        }

        // Room Location
        if (!empty($this->linkedLocationId)) {
            $location = RoomLocation::find($this->linkedLocationId);
            if (!$location) {
                return '<span class="text-gray-500">Location not found</span>';
            }

            $room = $location->room;
            $projectId = $room?->project_id;

            return sprintf(
                '<div class="space-y-1 text-sm"><div><strong>Room:</strong> %s</div><div><strong>Location:</strong> %s</div>%s</div>',
                e($room?->name ?? 'N/A'),
                e($location->name),
                $projectId ? '<div class="mt-2"><a href="/admin/project/projects/' . $projectId . '/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">View in Project →</a></div>' : ''
            );
        }

        // Room
        if (!empty($this->linkedRoomId)) {
            $room = Room::find($this->linkedRoomId);
            if (!$room) {
                return '<span class="text-gray-500">Room not found</span>';
            }

            // Rooms are managed through the project's relation manager
            return sprintf(
                '<div class="text-sm"><strong>Room:</strong> %s <a href="/admin/project/projects/%d/edit" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium">View in Project →</a></div>',
                e($room->name),
                $room->project_id
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

    /**
     * Save annotation using AnnotationSaveService
     * Entity-centric workflow: delegates to service for all save logic
     */
    public function save(): void
    {
        try {
            // Get validated form state
            $data = $this->form->getState();
            $pdfPageId = $this->originalAnnotation['pdfPageId'] ?? null;

            if (!$pdfPageId) {
                throw new \Exception('PDF page ID is missing from annotation data');
            }

            // Delegate to AnnotationSaveService
            $annotationSaveService = new AnnotationSaveService();
            $annotation = $annotationSaveService->saveAnnotation(
                formData: $data,
                originalAnnotation: $this->originalAnnotation,
                projectId: $this->projectId,
                pdfPageId: $pdfPageId,
                linkMode: $this->linkMode,
                linkedEntityId: $this->linkedEntityId
            );

            // Build updated annotation for Alpine.js UI update
            $updatedAnnotation = array_merge($this->originalAnnotation, [
                'id'                => $annotation->id,
                'label'             => $annotation->label,
                'notes'             => $annotation->notes ?? '',
                'parentId'          => $annotation->parent_annotation_id,
                'roomId'            => $annotation->room_id,
                'locationId'        => $annotation->room_location_id,
                'cabinetRunId'      => $annotation->cabinet_run_id,
                'cabinetSpecId'     => $annotation->cabinet_specification_id,
            ]);

            // Dispatch event back to Alpine.js
            $this->dispatch('annotation-updated', annotation: $updatedAnnotation);

            // Show success notification
            \Filament\Notifications\Notification::make()
                ->title('Annotation Saved')
                ->body('The annotation has been saved successfully.')
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
                'annotation_id' => $this->originalAnnotation['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Save Failed')
                ->body('Error saving annotation: '.$e->getMessage())
                ->danger()
                ->send();

            \Log::error('Annotation save failed', [
                'annotation_id' => $this->originalAnnotation['id'] ?? 'unknown',
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

    /**
     * =================================================================
     * ENTITY-CENTRIC HELPER METHODS
     * =================================================================
     */

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

        // Build hierarchy breadcrumb path for display
        $annotationId = $annotation['id'] ?? null;
        if ($annotationId && (!is_string($annotationId) || !str_starts_with($annotationId, 'temp_'))) {
            $this->hierarchyPath = AnnotationHierarchyService::getHierarchyPathHtml($annotationId);
        } else {
            $this->hierarchyPath = '<span class="text-gray-500">New annotation</span>';
        }

        // CRITICAL: Set the annotation model property for FilamentPHP v4 relationship binding
        // This must happen BEFORE filling the form so relationship fields can load properly
        $annotationId = $annotation['id'];
        if (!is_string($annotationId) || !str_starts_with($annotationId, 'temp_')) {
            $this->annotationModel = \App\Models\PdfPageAnnotation::find($annotationId);
        } else {
            $this->annotationModel = null;  // New annotation, no model yet
        }

        // ============================================
        // ENTITY-CENTRIC WORKFLOW: Load entity data
        // ============================================
        $entityManagement = new EntityManagementService();
        $entityIdField = $entityManagement->getEntityIdField($this->annotationType);
        $this->linkedEntityId = $annotation[str_replace('_id', 'Id', $entityIdField)] ?? null;

        if ($this->linkedEntityId) {
            // Annotation is linked to an existing entity - load entity data
            $this->linkMode = 'existing';
            $entity = $entityManagement->loadEntity($this->annotationType, $this->linkedEntityId);
            $this->entityData = $entity ? $entity->toArray() : [];
        } else {
            // New entity will be created
            $this->linkMode = 'create';
            $this->entityData = [];
        }

        // Fill form with annotation data using Filament Forms API
        // The model is now set, so relationship fields will load correctly
        $this->form->fill([
            // Entity-centric fields
            'link_mode'              => $this->linkMode,
            'linked_entity_id'       => $this->linkedEntityId,
            'entity'                 => $this->entityData,

            // Legacy annotation fields (still needed for view metadata)
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
            $positionData = PositionInferenceUtil::inferPositionFromCoordinates($normalizedY, $normalizedHeight);

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
     * Sync metadata across all instances of the same entity on different pages
     *
     * For example, "Sink Wall" on page 2 (plan) and page 3 (elevation) should
     * have the same parent, label, notes - only view_type and coordinates differ
     */

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
