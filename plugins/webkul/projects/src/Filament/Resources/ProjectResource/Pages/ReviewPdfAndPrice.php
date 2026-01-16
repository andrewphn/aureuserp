<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use App\Models\PdfDocument;
use App\Services\PdfParsingService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Webkul\Employee\Models\Employee;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\ProjectDraft;
use Webkul\Project\Services\PdfPageEntityService;
use Webkul\Support\Models\Company;

/**
 * Review Pdf And Price class
 *
 * @see \Filament\Resources\Resource
 */
class ReviewPdfAndPrice extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'webkul-project::filament.pages.review-pdf-and-price';

    protected static ?string $title = 'Review PDF & Create Pricing';

    public ?array $data = [];

    public $record;

    public $pdfDocument;

    #[Url]
    public $currentPage = 1;

    protected $pdfPageIdCache = [];

    #[Url]
    public $pdf;

    /**
     * Draft for auto-saving wizard state
     */
    public ?ProjectDraft $draft = null;

    /**
     * Cached pricing levels from database
     */
    protected ?array $pricingLevelsCache = null;

    /**
     * Last saved at timestamp for wizard submit button display
     */
    public ?string $lastSavedAt = null;

    /**
     * Pending cover page data for conflict resolution
     * Stores extracted data when conflicts are detected
     */
    public ?array $pendingCoverPageData = null;

    /**
     * Detected conflicts between existing and extracted data
     * Format: ['field_key' => ['current' => ..., 'extracted' => ..., 'selected' => bool]]
     */
    public array $dataConflicts = [];

    /**
     * Fields selected for update in conflict resolution
     */
    public array $selectedFields = [];

    /**
     * Page number currently being edited in slide-over modal
     */
    public ?int $editingPageNumber = null;

    /**
     * Data for the slide-over edit form
     */
    public array $editDetailsData = [];

    /**
     * Whether the edit details modal is open
     */
    public bool $showEditDetailsModal = false;

    // =====================================================
    // ENTITY CRUD MODAL PROPERTIES
    // =====================================================

    /**
     * Entity type being edited (room, room_location, cabinet_run, cabinet)
     */
    public string $entityType = '';

    /**
     * CRUD mode: 'create' or 'edit'
     */
    public string $entityMode = 'create';

    /**
     * Entity ID when editing (can be temp ID like 'temp_123' for unsaved)
     */
    public string|int|null $entityId = null;

    /**
     * Parent entity ID when creating child entities
     */
    public string|int|null $entityParentId = null;

    /**
     * Entity form data
     */
    public array $entityFormData = [];

    // =====================================================
    // STATEFUL ENTITY STORE
    // All changes are held in memory until explicitly saved
    // =====================================================

    /**
     * Pending entities to create (not yet in database)
     * Format: ['temp_id' => ['type' => 'room', 'data' => [...], 'parent_id' => null]]
     */
    public array $pendingEntities = [];

    /**
     * Counter for generating temp IDs
     */
    public int $tempIdCounter = 0;

    /**
     * Track which entities have unsaved changes
     */
    public bool $hasUnsavedEntityChanges = false;

    // =====================================================
    // HIERARCHICAL NAVIGATION PROPERTIES
    // Room → Location → CabinetRun → Cabinet → Section → Component
    // =====================================================

    /**
     * Full project hierarchy cache (loaded once on mount)
     * Structure: ['rooms' => [...], 'locations' => [...], 'runs' => [...], 'cabinets' => [...], 'sections' => [...]]
     * Each keyed by ID for O(1) lookup
     */
    public array $hierarchyCache = [];

    /**
     * Flag to indicate if hierarchy has been loaded
     */
    public bool $hierarchyLoaded = false;

    /**
     * Current navigation level in the hierarchy
     * Levels: 'rooms', 'locations', 'runs', 'cabinets', 'sections', 'components'
     */
    public string $hierarchyLevel = 'rooms';

    /**
     * Breadcrumb trail for navigation
     * Each item: ['level' => string, 'id' => int|null, 'name' => string]
     */
    public array $breadcrumbs = [];

    /**
     * Currently selected room for drill-down
     */
    public ?int $selectedRoomId = null;
    public ?string $selectedRoomName = null;

    /**
     * Currently selected location for drill-down
     */
    public ?int $selectedLocationId = null;
    public ?string $selectedLocationName = null;

    /**
     * Currently selected cabinet run for drill-down
     */
    public ?int $selectedRunId = null;
    public ?string $selectedRunName = null;

    /**
     * Currently selected cabinet for drill-down
     */
    public ?int $selectedCabinetId = null;
    public ?string $selectedCabinetName = null;

    /**
     * Currently selected section for drill-down
     */
    public ?int $selectedSectionId = null;
    public ?string $selectedSectionName = null;

    // =====================================================
    // ENTITY DETAILS PANEL SELECTION PROPERTIES
    // For the third column in Edit Details modal
    // =====================================================

    /**
     * Currently highlighted entity type for details panel
     * Values: 'room', 'location', 'run', 'cabinet', 'section', 'component'
     */
    public ?string $highlightedEntityType = null;

    /**
     * Currently highlighted entity ID for details panel
     */
    public ?int $highlightedEntityId = null;

    /**
     * Whether the details panel is in edit mode
     */
    public bool $isEditingInline = false;

    /**
     * Inline edit form data
     */
    public array $inlineEditData = [];

    /**
     * Mount
     *
     * @param int|string $record The model record
     * @return void
     */
    public function mount(int|string $record): void
    {
        // Resolve the Project model using InteractsWithRecord trait
        $this->record = $this->resolveRecord($record);

        if (! $this->pdf) {
            Notification::make()
                ->title('PDF Not Specified')
                ->body('Please select a PDF document to review.')
                ->danger()
                ->send();
            $this->redirect(ProjectResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        $this->pdfDocument = PdfDocument::findOrFail($this->pdf);

        // Check if PDF file actually exists
        if (! Storage::disk('public')->exists($this->pdfDocument->file_path)) {
            Notification::make()
                ->title('PDF File Not Found')
                ->body('The PDF file "'.$this->pdfDocument->file_name.'" is missing from storage. Please re-upload it.')
                ->danger()
                ->persistent()
                ->send();
        }

        // Check for existing draft for this PDF review
        $this->draft = ProjectDraft::where('user_id', auth()->id())
            ->where('session_id', 'pdf-review-' . $this->record->id . '-' . $this->pdf)
            ->active()
            ->latest()
            ->first();

        // Check if we have a draft to restore
        if ($this->draft && ! empty($this->draft->form_data)) {
            $formData = $this->draft->form_data;

            // Ensure page_number is set for each page in restored draft
            // (older drafts may not have saved this field)
            if (isset($formData['page_metadata']) && is_array($formData['page_metadata'])) {
                $totalPages = $this->getTotalPages();
                foreach ($formData['page_metadata'] as $index => &$pageData) {
                    // Set page_number based on index if missing
                    if (!isset($pageData['page_number']) || empty($pageData['page_number'])) {
                        $pageData['page_number'] = $index + 1;
                    }
                }
                unset($pageData); // Break reference

                // Ensure we have the correct number of pages
                $currentPageCount = count($formData['page_metadata']);
                if ($currentPageCount < $totalPages) {
                    // Add missing pages
                    $coverPageData = $this->buildCoverPageData();
                    for ($i = $currentPageCount + 1; $i <= $totalPages; $i++) {
                        $formData['page_metadata'][] = array_merge([
                            'page_number' => $i,
                            'primary_purpose' => null,
                            'page_label' => null,
                        ], $coverPageData);
                    }
                } elseif ($currentPageCount > $totalPages) {
                    // Trim excess pages
                    $formData['page_metadata'] = array_slice($formData['page_metadata'], 0, $totalPages);
                }
            }

            $this->form->fill($formData);
        } else {
            // Fresh start - build initial page metadata with enhanced fields
            $coverPageData = $this->buildCoverPageData();

            $pageMetadata = [];
            for ($i = 1; $i <= $this->getTotalPages(); $i++) {
                // Load existing page data from database if available
                $existingPage = \App\Models\PdfPage::where('document_id', $this->pdfDocument->id)
                    ->where('page_number', $i)
                    ->first();

                $pageData = [
                    'page_number' => $i,
                    'primary_purpose' => $existingPage?->primary_purpose,
                    'page_label' => $existingPage?->page_label,
                    'drawing_number' => $existingPage?->drawing_number,
                    'view_types' => $existingPage?->view_types ?? [],
                    'section_labels' => $existingPage?->section_labels ?? [],
                    'has_hardware_schedule' => $existingPage?->has_hardware_schedule ?? false,
                    'has_material_spec' => $existingPage?->has_material_spec ?? false,
                    'face_frame_material' => $existingPage?->face_frame_material,
                    'interior_material' => $existingPage?->interior_material,
                    'locations_documented' => $existingPage?->locations_documented ?? [],
                    'page_notes' => $existingPage?->page_notes ?? '',
                    'is_location_detail' => $existingPage?->is_location_detail ?? false,
                ];

                // Add cover page data to all pages (so any can be set as cover)
                $pageData = array_merge($pageData, $coverPageData);
                $pageMetadata[] = $pageData;
            }

            // Load existing rooms from project if available
            $existingRooms = $this->buildExistingRoomsData();

            $this->form->fill([
                'page_metadata' => $pageMetadata,
                'rooms' => $existingRooms,
            ]);
        }

        // Pre-load the full project hierarchy into cache for instant tree navigation
        $this->loadFullProjectHierarchy();
    }

    /**
     * Build rooms data from existing project rooms with full hierarchy
     * Project → Room → Room Location → Cabinet Run → Cabinet → Section → Components
     *
     * @return array
     */
    protected function buildExistingRoomsData(): array
    {
        $rooms = Room::where('project_id', $this->record->id)
            ->with([
                'locations.cabinetRuns.cabinets.sections' => function ($query) {
                    $query->orderBy('sort_order');
                },
            ])
            ->ordered()
            ->get();

        if ($rooms->isEmpty()) {
            return [];
        }

        $roomsData = [];
        foreach ($rooms as $room) {
            $locationsData = [];

            foreach ($room->locations as $location) {
                $cabinetRunsData = [];

                foreach ($location->cabinetRuns as $run) {
                    $cabinetsData = [];

                    foreach ($run->cabinets as $cabinet) {
                        $sectionsData = [];

                        foreach ($cabinet->sections as $section) {
                            // Load components for this section
                            $doorsData = $this->loadDoorsForSection($section->id);
                            $drawersData = $this->loadDrawersForSection($section->id);
                            $pulloutsData = $this->loadPulloutsForSection($section->id);
                            $shelvesData = $this->loadShelvesForSection($section->id);

                            $sectionsData[] = [
                                'section_id' => $section->id,
                                'section_name' => $section->name ?? '',
                                'section_type' => $section->section_type ?? 'door',
                                'section_width' => $section->width_inches,
                                'section_height' => $section->height_inches,
                                'doors' => $doorsData,
                                'drawers' => $drawersData,
                                'pullouts' => $pulloutsData,
                                'shelves' => $shelvesData,
                            ];
                        }

                        $cabinetsData[] = [
                            'cabinet_id' => $cabinet->id,
                            'cabinet_number' => $cabinet->cabinet_number ?? '',
                            'width_inches' => $cabinet->width_inches,
                            'height_inches' => $cabinet->height_inches,
                            'depth_inches' => $cabinet->depth_inches,
                            'cabinet_notes' => $cabinet->shop_notes ?? '',
                            'sections' => $sectionsData,
                        ];
                    }

                    $cabinetRunsData[] = [
                        'cabinet_run_id' => $run->id,
                        'run_name' => $run->name ?? '',
                        'run_type' => $run->run_type ?? 'base',
                        'cabinet_level' => $run->cabinet_level ?? '2',
                        'linear_feet' => $run->total_linear_feet ?? 0,
                        'run_notes' => $run->notes ?? '',
                        'cabinets' => $cabinetsData,
                    ];
                }

                // If location has no cabinet runs yet, add one empty slot
                if (empty($cabinetRunsData)) {
                    $cabinetRunsData[] = [
                        'cabinet_run_id' => null,
                        'run_name' => '',
                        'run_type' => 'base',
                        'cabinet_level' => '2',
                        'linear_feet' => '',
                        'run_notes' => '',
                        'cabinets' => [],
                    ];
                }

                $locationsData[] = [
                    'location_id' => $location->id,
                    'location_name' => $location->name ?? 'Main',
                    'location_type' => $location->location_type ?? 'wall',
                    'cabinet_runs' => $cabinetRunsData,
                ];
            }

            // If room has no locations yet, add one empty slot
            if (empty($locationsData)) {
                $locationsData[] = [
                    'location_id' => null,
                    'location_name' => 'Main',
                    'location_type' => 'wall',
                    'cabinet_runs' => [[
                        'cabinet_run_id' => null,
                        'run_name' => '',
                        'run_type' => 'base',
                        'cabinet_level' => '2',
                        'linear_feet' => '',
                        'run_notes' => '',
                        'cabinets' => [],
                    ]],
                ];
            }

            $roomsData[] = [
                'room_id' => $room->id,
                'room_name' => $room->name ?? ucwords(str_replace('_', ' ', $room->room_type)),
                'room_type' => $room->room_type,
                'locations' => $locationsData,
            ];
        }

        return $roomsData;
    }

    /**
     * Load doors for a cabinet section
     */
    protected function loadDoorsForSection(int $sectionId): array
    {
        $doors = \DB::table('projects_doors')
            ->where('section_id', $sectionId)
            ->orderBy('sort_order')
            ->get();

        return $doors->map(fn ($door) => [
            'door_id' => $door->id,
            'door_name' => $door->door_name ?? '',
            'door_width' => $door->width_inches,
            'door_height' => $door->height_inches,
            'hinge_side' => $door->hinge_side,
            'has_glass' => (bool) $door->has_glass,
            'products' => $this->loadComponentProducts('door_id', $door->id),
        ])->toArray();
    }

    /**
     * Load drawers for a cabinet section
     */
    protected function loadDrawersForSection(int $sectionId): array
    {
        $drawers = \DB::table('projects_drawers')
            ->where('section_id', $sectionId)
            ->orderBy('sort_order')
            ->get();

        return $drawers->map(fn ($drawer) => [
            'drawer_id' => $drawer->id,
            'drawer_name' => $drawer->drawer_name ?? '',
            'front_width' => $drawer->front_width_inches,
            'front_height' => $drawer->front_height_inches,
            'box_depth' => $drawer->box_depth_inches,
            'slide_type' => $drawer->slide_type,
            'products' => $this->loadComponentProducts('drawer_id', $drawer->id),
        ])->toArray();
    }

    /**
     * Load pullouts for a cabinet section
     */
    protected function loadPulloutsForSection(int $sectionId): array
    {
        $pullouts = \DB::table('projects_pullouts')
            ->where('section_id', $sectionId)
            ->orderBy('sort_order')
            ->get();

        return $pullouts->map(fn ($pullout) => [
            'pullout_id' => $pullout->id,
            'pullout_name' => $pullout->pullout_name ?? '',
            'pullout_type' => $pullout->pullout_type,
            'pullout_width' => $pullout->width_inches ?? null,
            'pullout_depth' => $pullout->depth_inches ?? null,
            'products' => $this->loadComponentProducts('pullout_id', $pullout->id),
        ])->toArray();
    }

    /**
     * Load shelves for a cabinet section
     */
    protected function loadShelvesForSection(int $sectionId): array
    {
        $shelves = \DB::table('projects_shelves')
            ->where('section_id', $sectionId)
            ->orderBy('sort_order')
            ->get();

        return $shelves->map(fn ($shelf) => [
            'shelf_id' => $shelf->id,
            'shelf_name' => $shelf->shelf_name ?? '',
            'shelf_type' => $shelf->shelf_type,
            'shelf_width' => $shelf->width_inches,
            'shelf_depth' => $shelf->depth_inches,
            'shelf_quantity' => $shelf->quantity ?? 1,
            'products' => $this->loadComponentProducts('shelf_id', $shelf->id),
        ])->toArray();
    }

    /**
     * Load products/hardware for a component
     *
     * @param string $foreignKeyColumn The column name (door_id, drawer_id, shelf_id, pullout_id)
     * @param int $componentId The ID of the component
     * @return array
     */
    protected function loadComponentProducts(string $foreignKeyColumn, int $componentId): array
    {
        return \DB::table('hardware_requirements')
            ->where($foreignKeyColumn, $componentId)
            ->get()
            ->map(fn ($hw) => [
                'product_id' => $hw->product_id,
                'quantity' => $hw->quantity_required ?? 1,
            ])
            ->toArray();
    }

    public function getTotalPages(): int
    {
        return $this->pdfDocument->page_count ?? 1;
    }

    /**
     * Define the form schema - Wizard with steps like CreateProject
     *
     * @param Schema $form
     * @return Schema
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    // Step 1: Classify PDF Pages
                    Step::make('1. Classify Pages')
                        ->description('Identify page types in the PDF')
                        ->icon('heroicon-o-document-text')
                        ->schema($this->getStep1Schema())
                        ->afterValidation(fn () => $this->saveDraft()),

                    // Step 2: Define Rooms & Cabinet Runs
                    Step::make('2. Rooms & Runs')
                        ->description('Add rooms and cabinet configurations')
                        ->icon('heroicon-o-home')
                        ->schema($this->getStep2Schema())
                        ->afterValidation(fn () => $this->saveDraft()),

                    // Step 3: Additional Items & Review
                    Step::make('3. Review & Quote')
                        ->description('Add extras and create quote')
                        ->icon('heroicon-o-calculator')
                        ->schema($this->getStep3Schema()),
                ])
                    ->submitAction(view('webkul-project::filament.components.wizard-submit-button'))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * Step 1: Classify PDF Pages - with visual PDF browser
     *
     * Enhanced to capture:
     * - Primary purpose (cover, plan_view, location_detail, etc.)
     * - Page label from drawing (e.g., "Sink Wall")
     * - View types present (elevation, plan views, sections)
     * - Hardware schedule presence
     * - Material specifications
     * - Locations documented on the page
     */
    protected function getStep1Schema(): array
    {
        return [
            \Filament\Schemas\Components\Grid::make(2)
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('pdf_info')
                        ->label('Document Information')
                        ->content(function () {
                            $info = "**{$this->pdfDocument->file_name}** — Total Pages: {$this->getTotalPages()}";

                            // Add version information
                            if ($this->pdfDocument->version_number > 1 || ! $this->pdfDocument->is_latest_version) {
                                $versionBadge = $this->pdfDocument->is_latest_version
                                    ? "Version {$this->pdfDocument->version_number} (Latest)"
                                    : "Version {$this->pdfDocument->version_number}";
                                $info .= " — **{$versionBadge}**";

                                if ($this->pdfDocument->version_metadata && isset($this->pdfDocument->version_metadata['version_notes'])) {
                                    $info .= "\n\n📝 **Version Notes:** {$this->pdfDocument->version_metadata['version_notes']}";
                                }
                            }

                            return $info;
                        }),

                    \Filament\Schemas\Components\Actions::make([
                        \Filament\Actions\Action::make('ai_classify')
                            ->label('AI Auto-Classify Pages')
                            ->icon('heroicon-o-sparkles')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->modalHeading('Auto-Classify Pages with AI')
                            ->modalDescription('This will use AI to analyze each page and suggest classifications based on the extracted text. You can review and modify the results.')
                            ->action(function () {
                                $this->aiClassifyPages();
                            }),
                    ])
                    ->alignEnd(),
                ])
                ->columnSpanFull(),

            Repeater::make('page_metadata')
                ->label('')
                ->schema([
                    // Two-column layout: thumbnail left, classification right
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            // PDF Thumbnail - left side
                            ViewField::make('pdf_thumbnail')
                                ->view('webkul-project::filament.components.pdf-page-thumbnail-pdfjs')
                                ->viewData(fn ($get) => [
                                    'pdfId'       => $this->pdfDocument->id,
                                    'pdfDocument' => $this->pdfDocument,
                                    'pdfUrl'      => Storage::disk('public')->url($this->pdfDocument->file_path),
                                    'pageNumber'  => $get('page_number') ?? 1,
                                    'pdfPageId'   => $this->getPdfPageId($get('page_number') ?? 1),
                                    'pdfPage'     => $this->getPdfPageId($get('page_number') ?? 1) ? \App\Models\PdfPage::find($this->getPdfPageId($get('page_number') ?? 1)) : null,
                                    'itemKey'     => 'page-'.($get('page_number') ?? 1),
                                    'record'      => $this->record,
                                    'project'     => $this->record,
                                ])
                                ->columnSpan(1),

                            // Simplified classification - right side with slide-over modals
                            \Filament\Schemas\Components\Grid::make(1)
                                ->schema([
                                    // Hidden page number (needed for data)
                                    TextInput::make('page_number')
                                        ->hidden()
                                        ->dehydrated(),

                                    // ONE question: What type of page is this?
                                    \Filament\Forms\Components\ToggleButtons::make('primary_purpose')
                                        ->label('What is this page?')
                                        ->options(\App\Models\PdfPage::PRIMARY_PURPOSES)
                                        ->icons([
                                            'cover' => 'heroicon-o-document-text',
                                            'floor_plan' => 'heroicon-o-map',
                                            'elevations' => 'heroicon-o-view-columns',
                                            'countertops' => 'heroicon-o-square-3-stack-3d',
                                            'reference' => 'heroicon-o-photo',
                                            'other' => 'heroicon-o-ellipsis-horizontal',
                                        ])
                                        ->colors([
                                            'cover' => 'gray',
                                            'floor_plan' => 'info',
                                            'elevations' => 'success',
                                            'countertops' => 'warning',
                                            'reference' => 'gray',
                                            'other' => 'gray',
                                        ])
                                        ->inline()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $get) {
                                            // Persist to database immediately when user classifies a page
                                            $pageNumber = $get('page_number');
                                            if ($pageNumber) {
                                                $this->persistPageClassification(
                                                    (int) $pageNumber,
                                                    $state,
                                                    ['page_label' => $get('page_label')]
                                                );
                                            }
                                        }),

                                    // ============ COMPACT SUMMARY + EDIT ACTION ============
                                    // Shows summary badge when data exists, with "Edit Details" slide-over action
                                    ViewField::make('page_summary')
                                        ->view('webkul-project::filament.components.page-type-summary-card')
                                        ->viewData(fn ($get) => [
                                            'primaryPurpose' => $get('primary_purpose'),
                                            'pageNumber' => $get('page_number'),
                                            'pageLabel' => $get('page_label'),
                                            // Cover page data
                                            'coverAddress' => trim(implode(', ', array_filter([
                                                $get('cover_address_street'),
                                                $get('cover_address_city'),
                                                $get('cover_address_state'),
                                            ]))),
                                            'coverDesigner' => $get('cover_designer_company'),
                                            'scopeEstimate' => $get('scope_estimate') ?? [],
                                            'roomsMentioned' => $get('rooms_mentioned') ?? [],
                                            // Floor plan data
                                            'roomsOnPage' => $get('rooms_on_page') ?? [],
                                            // Elevation data
                                            'linearFeet' => $get('linear_feet'),
                                            'pricingTier' => $get('pricing_tier'),
                                            'roomName' => $get('room_name'),
                                            'hasHardware' => $get('has_hardware_schedule'),
                                            'hasMaterial' => $get('has_material_spec'),
                                            // Countertop data
                                            'countertopFeatures' => $get('countertop_features') ?? [],
                                            // Notes
                                            'pageNotes' => $get('page_notes'),
                                        ])
                                        ->visible(fn ($get) => $get('primary_purpose') !== null),

                                    // Edit Details slide-over action
                                    // Note: We use a separate Livewire action instead of inline action
                                    // because repeater item actions don't have access to $get/$set context
                                    \Filament\Forms\Components\Placeholder::make('edit_details_trigger')
                                        ->label('')
                                        ->content(function ($get) {
                                            $pageNumber = $get('page_number');
                                            $primaryPurpose = $get('primary_purpose');
                                            if (!$primaryPurpose) {
                                                return '';
                                            }
                                            // Generate a clickable button that triggers Livewire action
                                            return new \Illuminate\Support\HtmlString(
                                                '<button type="button"
                                                    wire:click="openEditDetailsModal(' . $pageNumber . ')"
                                                    class="fi-btn fi-btn-size-sm fi-btn-color-gray gap-1.5 inline-flex items-center justify-center px-3 py-2 text-sm font-semibold rounded-lg shadow-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                    Edit Details
                                                </button>'
                                            );
                                        })
                                        ->visible(fn ($get) => $get('primary_purpose') !== null),

                                    // Hidden fields to store all the detail data (for form state persistence)
                                    // Cover page hidden fields
                                    TextInput::make('cover_address_street')->hidden()->dehydrated(),
                                    TextInput::make('cover_address_city')->hidden()->dehydrated(),
                                    TextInput::make('cover_address_state')->hidden()->dehydrated(),
                                    TextInput::make('cover_address_zip')->hidden()->dehydrated(),
                                    TextInput::make('cover_designer_company')->hidden()->dehydrated(),
                                    TextInput::make('cover_designer_drawn_by')->hidden()->dehydrated(),
                                    TextInput::make('cover_designer_approved_by')->hidden()->dehydrated(),
                                    TextInput::make('cover_designer_phone')->hidden()->dehydrated(),
                                    TextInput::make('cover_designer_email')->hidden()->dehydrated(),
                                    TextInput::make('drawing_number')->hidden()->dehydrated(),
                                    TextInput::make('cover_revision_date')->hidden()->dehydrated(),
                                    TextInput::make('page_label')->hidden()->dehydrated(),
                                    \Filament\Forms\Components\Hidden::make('scope_estimate')->dehydrated(),
                                    \Filament\Forms\Components\Hidden::make('rooms_mentioned')->dehydrated(),
                                    // Floor plan hidden fields
                                    \Filament\Forms\Components\Hidden::make('rooms_on_page')->dehydrated(),
                                    // Elevation hidden fields
                                    TextInput::make('linear_feet')->hidden()->dehydrated(),
                                    TextInput::make('pricing_tier')->hidden()->dehydrated(),
                                    TextInput::make('room_name')->hidden()->dehydrated(),
                                    \Filament\Forms\Components\Hidden::make('has_hardware_schedule')->dehydrated(),
                                    \Filament\Forms\Components\Hidden::make('has_material_spec')->dehydrated(),
                                    // Countertop hidden fields
                                    \Filament\Forms\Components\Hidden::make('countertop_features')->dehydrated(),
                                    // Notes
                                    TextInput::make('page_notes')->hidden()->dehydrated(),
                                ])
                                ->columnSpan(1),
                        ]),
                ])
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->itemLabel(fn ($state, $key) => $this->getPageItemLabelByKey($key))
                ->collapsed(fn ($state) => !empty($state['primary_purpose']))  // Collapse already-classified pages
                ->columnSpanFull(),
        ];
    }

    /**
     * Get room options for the elevation page "In Room" dropdown
     * Pulls from rooms already entered in floor plan pages OR existing project rooms
     */
    protected function getRoomOptions(): array
    {
        $rooms = [];

        // Get rooms from project
        $projectRooms = Room::where('project_id', $this->record->id)
            ->pluck('name', 'name')
            ->toArray();
        $rooms = array_merge($rooms, $projectRooms);

        // Get rooms from floor plan pages in current form data
        if (!empty($this->data['page_metadata'])) {
            foreach ($this->data['page_metadata'] as $page) {
                if (($page['primary_purpose'] ?? '') === 'floor_plan' && !empty($page['rooms_on_page'])) {
                    foreach ($page['rooms_on_page'] as $room) {
                        $rooms[$room] = $room;
                    }
                }
            }
        }

        // Add common suggestions if no rooms yet
        if (empty($rooms)) {
            return [
                'Kitchen' => 'Kitchen',
                'Pantry' => 'Pantry',
                'Primary Bath' => 'Primary Bath',
                'Laundry' => 'Laundry',
            ];
        }

        return $rooms;
    }

    /**
     * Get slide-over modal heading based on page type
     */
    protected function getSlideOverHeading(?string $primaryPurpose): string
    {
        return match ($primaryPurpose) {
            'cover' => 'Cover Page Details',
            'floor_plan' => 'Floor Plan Details',
            'elevations' => 'Elevation Details',
            'countertops' => 'Countertop Details',
            'reference' => 'Reference Page Details',
            'other' => 'Page Details',
            default => 'Page Details',
        };
    }

    /**
     * Get form data for slide-over modal pre-fill
     */
    protected function getSlideOverFormData(callable $get): array
    {
        return [
            // Cover page data
            'cover_address_street' => $get('cover_address_street'),
            'cover_address_city' => $get('cover_address_city'),
            'cover_address_state' => $get('cover_address_state'),
            'cover_address_zip' => $get('cover_address_zip'),
            'cover_designer_company' => $get('cover_designer_company'),
            'cover_designer_drawn_by' => $get('cover_designer_drawn_by'),
            'cover_designer_approved_by' => $get('cover_designer_approved_by'),
            'cover_designer_phone' => $get('cover_designer_phone'),
            'cover_designer_email' => $get('cover_designer_email'),
            'drawing_number' => $get('drawing_number'),
            'cover_revision_date' => $get('cover_revision_date'),
            'page_label' => $get('page_label'),
            'scope_estimate' => $get('scope_estimate') ?? [],
            'rooms_mentioned' => $get('rooms_mentioned') ?? [],
            // Floor plan data
            'rooms_on_page' => $get('rooms_on_page') ?? [],
            // Elevation data
            'linear_feet' => $get('linear_feet'),
            'pricing_tier' => $get('pricing_tier'),
            'room_name' => $get('room_name'),
            'has_hardware_schedule' => $get('has_hardware_schedule'),
            'has_material_spec' => $get('has_material_spec'),
            // Countertop data
            'countertop_features' => $get('countertop_features') ?? [],
            // Notes
            'page_notes' => $get('page_notes'),
        ];
    }

    /**
     * Get slide-over form data from array (for use when $get is not available)
     */
    protected function getSlideOverFormDataFromArray(array $pageData): array
    {
        return [
            // Cover page data
            'cover_address_street' => $pageData['cover_address_street'] ?? null,
            'cover_address_city' => $pageData['cover_address_city'] ?? null,
            'cover_address_country' => $pageData['cover_address_country'] ?? 'US',
            'cover_address_state' => $pageData['cover_address_state'] ?? null,
            'cover_address_zip' => $pageData['cover_address_zip'] ?? null,
            'cover_designer_company' => $pageData['cover_designer_company'] ?? null,
            'cover_designer_drawn_by' => $pageData['cover_designer_drawn_by'] ?? null,
            'cover_designer_approved_by' => $pageData['cover_designer_approved_by'] ?? null,
            'cover_designer_phone' => $pageData['cover_designer_phone'] ?? null,
            'cover_designer_email' => $pageData['cover_designer_email'] ?? null,
            'drawing_number' => $pageData['drawing_number'] ?? null,
            'cover_revision_date' => $pageData['cover_revision_date'] ?? null,
            'page_label' => $pageData['page_label'] ?? null,
            'scope_estimate' => $pageData['scope_estimate'] ?? [],
            'rooms_mentioned' => $pageData['rooms_mentioned'] ?? [],
            // Floor plan data
            'rooms_on_page' => $pageData['rooms_on_page'] ?? [],
            // Elevation data
            'linear_feet' => $pageData['linear_feet'] ?? null,
            'pricing_tier' => $pageData['pricing_tier'] ?? null,
            'room_name' => $pageData['room_name'] ?? null,
            'has_hardware_schedule' => $pageData['has_hardware_schedule'] ?? null,
            'has_material_spec' => $pageData['has_material_spec'] ?? null,
            // Countertop data
            'countertop_features' => $pageData['countertop_features'] ?? [],
            // Notes
            'page_notes' => $pageData['page_notes'] ?? null,
        ];
    }

    /**
     * Apply slide-over data to array (updates $this->data directly)
     */
    protected function applySlideOverDataToArray(array $data, string $itemKey, ?string $primaryPurpose): void
    {
        // Build update array based on page type
        $updates = [];

        switch ($primaryPurpose) {
            case 'cover':
                $updates = [
                    'cover_address_street' => $data['cover_address_street'] ?? null,
                    'cover_address_city' => $data['cover_address_city'] ?? null,
                    'cover_address_country' => $data['cover_address_country'] ?? 'US',
                    'cover_address_state' => $data['cover_address_state'] ?? null,
                    'cover_address_zip' => $data['cover_address_zip'] ?? null,
                    'cover_designer_company' => $data['cover_designer_company'] ?? null,
                    'cover_designer_drawn_by' => $data['cover_designer_drawn_by'] ?? null,
                    'cover_designer_approved_by' => $data['cover_designer_approved_by'] ?? null,
                    'cover_designer_phone' => $data['cover_designer_phone'] ?? null,
                    'cover_designer_email' => $data['cover_designer_email'] ?? null,
                    'drawing_number' => $data['drawing_number'] ?? null,
                    'cover_revision_date' => $data['cover_revision_date'] ?? null,
                    'page_label' => $data['page_label'] ?? null,
                    'scope_estimate' => $data['scope_estimate'] ?? [],
                    'rooms_mentioned' => $data['rooms_mentioned'] ?? [],
                    'page_notes' => $data['page_notes'] ?? null,
                ];
                break;

            case 'floor_plan':
                $updates = [
                    'page_label' => $data['page_label'] ?? null,
                    'rooms_on_page' => $data['rooms_on_page'] ?? [],
                    'page_notes' => $data['page_notes'] ?? null,
                ];
                break;

            case 'elevations':
                $updates = [
                    'page_label' => $data['page_label'] ?? null,
                    'linear_feet' => $data['linear_feet'] ?? null,
                    'pricing_tier' => $data['pricing_tier'] ?? null,
                    'room_name' => $data['room_name'] ?? null,
                    'has_hardware_schedule' => $data['has_hardware_schedule'] ?? null,
                    'has_material_spec' => $data['has_material_spec'] ?? null,
                    'page_notes' => $data['page_notes'] ?? null,
                ];
                break;

            case 'countertops':
                $updates = [
                    'page_label' => $data['page_label'] ?? null,
                    'countertop_features' => $data['countertop_features'] ?? [],
                    'page_notes' => $data['page_notes'] ?? null,
                ];
                break;

            default:
                $updates = [
                    'page_label' => $data['page_label'] ?? null,
                    'page_notes' => $data['page_notes'] ?? null,
                ];
        }

        // Apply updates to $this->data
        if (isset($this->data['page_metadata'][$itemKey])) {
            foreach ($updates as $field => $value) {
                $this->data['page_metadata'][$itemKey][$field] = $value;
            }
        }

        // Also persist to database immediately
        $pageNumber = $this->data['page_metadata'][$itemKey]['page_number'] ?? null;
        if ($pageNumber) {
            $this->persistPageClassification(
                (int) $pageNumber,
                $primaryPurpose,
                [
                    'page_label' => $updates['page_label'] ?? null,
                    'page_notes' => $updates['page_notes'] ?? null,
                    'has_hardware_schedule' => $updates['has_hardware_schedule'] ?? false,
                    'has_material_spec' => $updates['has_material_spec'] ?? false,
                    'drawing_number' => $updates['drawing_number'] ?? null,
                ]
            );
        }

        // Trigger draft save
        $this->saveDraft();

        // Show notification
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Details saved')
            ->body('Page details have been updated.')
            ->send();
    }

    /**
     * Apply slide-over data back to repeater item using $set closure
     */
    protected function applySlideOverDataToSet(array $data, callable $set, ?string $primaryPurpose): void
    {
        // Map fields based on page type
        switch ($primaryPurpose) {
            case 'cover':
                $set('cover_address_street', $data['cover_address_street'] ?? null);
                $set('cover_address_city', $data['cover_address_city'] ?? null);
                $set('cover_address_country', $data['cover_address_country'] ?? 'US');
                $set('cover_address_state', $data['cover_address_state'] ?? null);
                $set('cover_address_zip', $data['cover_address_zip'] ?? null);
                $set('cover_designer_company', $data['cover_designer_company'] ?? null);
                $set('cover_designer_drawn_by', $data['cover_designer_drawn_by'] ?? null);
                $set('cover_designer_approved_by', $data['cover_designer_approved_by'] ?? null);
                $set('cover_designer_phone', $data['cover_designer_phone'] ?? null);
                $set('cover_designer_email', $data['cover_designer_email'] ?? null);
                $set('drawing_number', $data['drawing_number'] ?? null);
                $set('cover_revision_date', $data['cover_revision_date'] ?? null);
                $set('page_label', $data['page_label'] ?? null);
                $set('scope_estimate', $data['scope_estimate'] ?? []);
                $set('rooms_mentioned', $data['rooms_mentioned'] ?? []);
                $set('page_notes', $data['page_notes'] ?? null);
                break;

            case 'floor_plan':
                $set('page_label', $data['page_label'] ?? null);
                $set('rooms_on_page', $data['rooms_on_page'] ?? []);
                $set('page_notes', $data['page_notes'] ?? null);
                break;

            case 'elevations':
                $set('page_label', $data['page_label'] ?? null);
                $set('linear_feet', $data['linear_feet'] ?? null);
                $set('pricing_tier', $data['pricing_tier'] ?? null);
                $set('room_name', $data['room_name'] ?? null);
                $set('has_hardware_schedule', $data['has_hardware_schedule'] ?? null);
                $set('has_material_spec', $data['has_material_spec'] ?? null);
                $set('page_notes', $data['page_notes'] ?? null);
                break;

            case 'countertops':
                $set('page_label', $data['page_label'] ?? null);
                $set('countertop_features', $data['countertop_features'] ?? []);
                $set('page_notes', $data['page_notes'] ?? null);
                break;

            default:
                $set('page_label', $data['page_label'] ?? null);
                $set('page_notes', $data['page_notes'] ?? null);
        }
        // Note: saveDraft() is called from updatePageMetadataDirectly() which is called after this
    }

    /**
     * Update page_metadata directly in $this->data for proper draft persistence
     * This ensures data is saved even when $set updates are batched by Livewire
     */
    protected function updatePageMetadataDirectly(int $pageNumber, array $data, ?string $primaryPurpose): void
    {
        // Find the page in page_metadata by page_number
        $pageMetadata = $this->data['page_metadata'] ?? [];

        foreach ($pageMetadata as $index => $page) {
            if (($page['page_number'] ?? null) == $pageNumber) {
                // Update all the relevant fields based on page type
                switch ($primaryPurpose) {
                    case 'cover':
                        $this->data['page_metadata'][$index]['cover_address_street'] = $data['cover_address_street'] ?? null;
                        $this->data['page_metadata'][$index]['cover_address_city'] = $data['cover_address_city'] ?? null;
                        $this->data['page_metadata'][$index]['cover_address_country'] = $data['cover_address_country'] ?? 'US';
                        $this->data['page_metadata'][$index]['cover_address_state'] = $data['cover_address_state'] ?? null;
                        $this->data['page_metadata'][$index]['cover_address_zip'] = $data['cover_address_zip'] ?? null;
                        $this->data['page_metadata'][$index]['cover_designer_company'] = $data['cover_designer_company'] ?? null;
                        $this->data['page_metadata'][$index]['cover_designer_drawn_by'] = $data['cover_designer_drawn_by'] ?? null;
                        $this->data['page_metadata'][$index]['cover_designer_approved_by'] = $data['cover_designer_approved_by'] ?? null;
                        $this->data['page_metadata'][$index]['cover_designer_phone'] = $data['cover_designer_phone'] ?? null;
                        $this->data['page_metadata'][$index]['cover_designer_email'] = $data['cover_designer_email'] ?? null;
                        $this->data['page_metadata'][$index]['drawing_number'] = $data['drawing_number'] ?? null;
                        $this->data['page_metadata'][$index]['cover_revision_date'] = $data['cover_revision_date'] ?? null;
                        $this->data['page_metadata'][$index]['page_label'] = $data['page_label'] ?? null;
                        $this->data['page_metadata'][$index]['scope_estimate'] = $data['scope_estimate'] ?? [];
                        $this->data['page_metadata'][$index]['rooms_mentioned'] = $data['rooms_mentioned'] ?? [];
                        $this->data['page_metadata'][$index]['page_notes'] = $data['page_notes'] ?? null;
                        break;

                    case 'floor_plan':
                        $this->data['page_metadata'][$index]['page_label'] = $data['page_label'] ?? null;
                        $this->data['page_metadata'][$index]['rooms_on_page'] = $data['rooms_on_page'] ?? [];
                        $this->data['page_metadata'][$index]['page_notes'] = $data['page_notes'] ?? null;
                        break;

                    case 'elevations':
                        $this->data['page_metadata'][$index]['page_label'] = $data['page_label'] ?? null;
                        $this->data['page_metadata'][$index]['linear_feet'] = $data['linear_feet'] ?? null;
                        $this->data['page_metadata'][$index]['pricing_tier'] = $data['pricing_tier'] ?? null;
                        $this->data['page_metadata'][$index]['room_name'] = $data['room_name'] ?? null;
                        $this->data['page_metadata'][$index]['has_hardware_schedule'] = $data['has_hardware_schedule'] ?? null;
                        $this->data['page_metadata'][$index]['has_material_spec'] = $data['has_material_spec'] ?? null;
                        $this->data['page_metadata'][$index]['page_notes'] = $data['page_notes'] ?? null;
                        break;

                    case 'countertops':
                        $this->data['page_metadata'][$index]['page_label'] = $data['page_label'] ?? null;
                        $this->data['page_metadata'][$index]['countertop_features'] = $data['countertop_features'] ?? [];
                        $this->data['page_metadata'][$index]['page_notes'] = $data['page_notes'] ?? null;
                        break;

                    default:
                        $this->data['page_metadata'][$index]['page_label'] = $data['page_label'] ?? null;
                        $this->data['page_metadata'][$index]['page_notes'] = $data['page_notes'] ?? null;
                }
                break;
            }
        }

        // Sync the updated $this->data back to the form so getState() returns current values
        $this->form->fill($this->data);

        // Now save the draft with the updated data
        $this->saveDraft();
    }

    /**
     * Update page metadata by item key (for use in action callbacks)
     * This is an alias for applySlideOverDataToArray to match the action callback naming
     */
    protected function updatePageMetadataByKey(string $itemKey, array $data, ?string $primaryPurpose): void
    {
        $this->applySlideOverDataToArray($data, $itemKey, $primaryPurpose);
    }

    /**
     * Get form schema for slide-over modal based on page type
     */
    protected function getSlideOverFormSchema(?string $primaryPurpose): array
    {
        $commonNotesField = \Filament\Forms\Components\Textarea::make('page_notes')
            ->label('Notes (optional)')
            ->placeholder('Any special notes...')
            ->rows(2);

        return match ($primaryPurpose) {
            'cover' => $this->getCoverPageSlideOverSchema(),
            'floor_plan' => $this->getFloorPlanSlideOverSchema(),
            'elevations' => $this->getElevationsSlideOverSchema(),
            'countertops' => $this->getCountertopsSlideOverSchema(),
            default => [$commonNotesField],
        };
    }

    /**
     * Cover page slide-over form schema
     */
    protected function getCoverPageSlideOverSchema(): array
    {
        return [
            // AI Extract action at top
            \Filament\Schemas\Components\Actions::make([
                \Filament\Actions\Action::make('extract_cover_data_modal')
                    ->label('Extract from PDF')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->size('sm')
                    ->action(function ($get, $set) {
                        $this->extractCoverPageDataInModal($get, $set);
                    }),
            ]),

            // Project Address Section
            \Filament\Schemas\Components\Fieldset::make('Project Location')
                ->schema([
                    TextInput::make('cover_address_street')
                        ->label('Street Address')
                        ->placeholder('e.g., 25 Friendship Lane'),
                    \Filament\Schemas\Components\Grid::make(3)
                        ->schema([
                            TextInput::make('cover_address_city')
                                ->label('City')
                                ->placeholder('Nantucket'),
                            TextInput::make('cover_address_state')
                                ->label('State')
                                ->placeholder('MA'),
                            TextInput::make('cover_address_zip')
                                ->label('ZIP')
                                ->placeholder('02554'),
                        ]),
                ])
                ->columns(1),

            // Designer Info Section
            \Filament\Schemas\Components\Fieldset::make('Designer / Woodworker')
                ->schema([
                    TextInput::make('cover_designer_company')
                        ->label('Company Name')
                        ->placeholder('e.g., Trottier Fine Woodworking'),
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('cover_designer_drawn_by')
                                ->label('Drawn By')
                                ->placeholder('J. Garcia'),
                            TextInput::make('cover_designer_approved_by')
                                ->label('Approved By')
                                ->placeholder('J. Trottier'),
                        ]),
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('cover_designer_phone')
                                ->label('Phone')
                                ->tel(),
                            TextInput::make('cover_designer_email')
                                ->label('Email')
                                ->email(),
                        ]),
                ])
                ->columns(1),

            // Revision Info
            \Filament\Schemas\Components\Grid::make(2)
                ->schema([
                    TextInput::make('drawing_number')
                        ->label('Revision')
                        ->placeholder('e.g., Rev 4'),
                    TextInput::make('cover_revision_date')
                        ->label('Revision Date')
                        ->placeholder('9/27/25'),
                ]),

            TextInput::make('page_label')
                ->label('Project Description')
                ->placeholder('e.g., Kitchen Cabinetry')
                ->helperText('Main project description from cover'),

            // Designer's Scope Estimate
            Repeater::make('scope_estimate')
                ->label("Designer's Scope Estimate")
                ->schema([
                    TextInput::make('item_type')
                        ->label('Item Type')
                        ->placeholder('e.g., Tier 2 Cabinetry')
                        ->columnSpan(2),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->step(0.25),
                    Select::make('unit')
                        ->label('Unit')
                        ->options([
                            'LF' => 'LF (Linear Feet)',
                            'SF' => 'SF (Square Feet)',
                            'EA' => 'EA (Each)',
                            'SET' => 'SET',
                        ])
                        ->default('LF')
                        ->native(false),
                ])
                ->columns(4)
                ->defaultItems(0)
                ->addActionLabel('Add Line Item')
                ->helperText("These are the designer's estimates from the cover page")
                ->collapsible()
                ->collapsed(false),

            // Rooms Mentioned
            \Filament\Forms\Components\TagsInput::make('rooms_mentioned')
                ->label('Rooms Mentioned')
                ->placeholder('Add rooms...')
                ->suggestions([
                    'Kitchen', 'Pantry', 'Butler\'s Pantry',
                    'Laundry', 'Mudroom', 'Primary Bath',
                ]),

            // Notes
            \Filament\Forms\Components\Textarea::make('page_notes')
                ->label('Notes')
                ->placeholder('Any additional notes...')
                ->rows(2),
        ];
    }

    /**
     * Floor plan slide-over form schema
     */
    protected function getFloorPlanSlideOverSchema(): array
    {
        return [
            \Filament\Forms\Components\TagsInput::make('rooms_on_page')
                ->label('Rooms visible on this plan')
                ->placeholder('Add room names...')
                ->suggestions([
                    'Kitchen', 'Pantry', 'Butler\'s Pantry',
                    'Laundry', 'Mudroom', 'Primary Bath',
                    'Guest Bath', 'Powder Room', 'Primary Closet',
                    'Office', 'Living Room', 'Family Room',
                    'Dining Room', 'Bar', 'Wine Room',
                ])
                ->helperText('Type room names shown on this floor plan'),

            TextInput::make('page_label')
                ->label('Floor/Level Name')
                ->placeholder('e.g., First Floor, Basement')
                ->helperText('Which floor or level is this?'),

            \Filament\Forms\Components\Textarea::make('page_notes')
                ->label('Notes')
                ->placeholder('Any additional notes...')
                ->rows(2),
        ];
    }

    /**
     * Elevations slide-over form schema
     */
    protected function getElevationsSlideOverSchema(): array
    {
        return [
            TextInput::make('page_label')
                ->label('Wall / Location Name')
                ->placeholder('e.g., Sink Wall, Island, Pantry')
                ->helperText('Which wall or area is shown?'),

            \Filament\Schemas\Components\Grid::make(3)
                ->schema([
                    TextInput::make('linear_feet')
                        ->label('Linear Feet')
                        ->numeric()
                        ->step(0.25)
                        ->suffix('LF')
                        ->placeholder('0.00'),

                    Select::make('pricing_tier')
                        ->label('Pricing Tier')
                        ->options([
                            '1' => 'Level 1',
                            '2' => 'Level 2',
                            '3' => 'Level 3',
                            '4' => 'Level 4',
                            '5' => 'Level 5',
                        ])
                        ->native(false)
                        ->placeholder('Select'),

                    Select::make('room_name')
                        ->label('In Room')
                        ->options(fn () => $this->getRoomOptions())
                        ->placeholder('Select room')
                        ->native(false),
                ]),

            \Filament\Schemas\Components\Grid::make(2)
                ->schema([
                    \Filament\Forms\Components\Toggle::make('has_hardware_schedule')
                        ->label('Has Hardware Schedule')
                        ->inline(false),

                    \Filament\Forms\Components\Toggle::make('has_material_spec')
                        ->label('Has Material Specs')
                        ->inline(false),
                ]),

            \Filament\Forms\Components\Textarea::make('page_notes')
                ->label('Notes')
                ->placeholder('Any additional notes...')
                ->rows(2),
        ];
    }

    /**
     * Countertops slide-over form schema
     */
    protected function getCountertopsSlideOverSchema(): array
    {
        return [
            TextInput::make('page_label')
                ->label('Counter Area')
                ->placeholder('e.g., Kitchen Counters, Island Top')
                ->helperText('Which countertop area?'),

            \Filament\Forms\Components\CheckboxList::make('countertop_features')
                ->label('Shows')
                ->options([
                    'sink_cutout' => 'Sink Cutout',
                    'cooktop_cutout' => 'Cooktop Cutout',
                    'edge_profiles' => 'Edge Profiles',
                    'seam_locations' => 'Seam Locations',
                    'backsplash' => 'Backsplash Details',
                ])
                ->columns(3),

            \Filament\Forms\Components\Textarea::make('page_notes')
                ->label('Notes')
                ->placeholder('Any additional notes...')
                ->rows(2),
        ];
    }

    /**
     * Apply slide-over form data back to main form
     */
    protected function applySlideOverData(array $data, callable $set, ?string $primaryPurpose): void
    {
        // Apply all fields from slide-over back to main form
        foreach ($data as $key => $value) {
            $set($key, $value);
        }

        Notification::make()
            ->title('Details Saved')
            ->body('Page details have been updated.')
            ->success()
            ->duration(2000)
            ->send();
    }

    /**
     * Extract cover page data within the slide-over modal
     */
    protected function extractCoverPageDataInModal(callable $get, callable $set): void
    {
        // Reuse the main extraction logic
        $extractor = new \App\Services\PdfCoverPageExtractor();
        $extracted = $extractor->extract($this->pdfDocument);

        if (isset($extracted['error'])) {
            Notification::make()
                ->title('Extraction Error')
                ->body($extracted['error'])
                ->danger()
                ->send();
            return;
        }

        // Populate address fields
        if (!empty($extracted['project']['address'])) {
            $address = $extracted['project']['address'];
            $set('cover_address_street', $address['street'] ?? null);
            $set('cover_address_city', $address['city'] ?? null);
            $set('cover_address_state', $address['state'] ?? null);
            $set('cover_address_zip', $address['zip'] ?? null);
        }

        // Populate designer info
        if (!empty($extracted['designer'])) {
            $designer = $extracted['designer'];
            $set('cover_designer_company', $designer['company_name'] ?? null);
            $set('cover_designer_drawn_by', $designer['drawn_by'] ?? null);
            $set('cover_designer_approved_by', $designer['approved_by'] ?? null);
            $set('cover_designer_phone', $designer['phone'] ?? null);
            $set('cover_designer_email', $designer['email'] ?? null);
        }

        // Populate revision info
        if (!empty($extracted['revision'])) {
            $revision = $extracted['revision'];
            $set('drawing_number', $revision['current'] ?? null);
            $set('cover_revision_date', $revision['date'] ?? null);
        }

        // Populate project description
        if (!empty($extracted['project']['description'])) {
            $set('page_label', $extracted['project']['description']);
        }

        // Populate scope estimate
        if (!empty($extracted['scope_estimate']) && is_array($extracted['scope_estimate'])) {
            $scopeItems = [];
            foreach ($extracted['scope_estimate'] as $item) {
                $scopeItems[] = [
                    'item_type' => $item['item_type'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => $item['unit'] ?? 'LF',
                ];
            }
            $set('scope_estimate', $scopeItems);
        }

        // Populate rooms mentioned
        if (!empty($extracted['rooms_mentioned']) && is_array($extracted['rooms_mentioned'])) {
            $set('rooms_mentioned', $extracted['rooms_mentioned']);
        }

        Notification::make()
            ->title('Data Extracted')
            ->body('Cover page data has been extracted. Review and save.')
            ->success()
            ->send();
    }

    /**
     * AI Auto-classify all pages in the document using Gemini's vision API
     * This sends the entire PDF to Gemini and classifies all pages at once
     */
    protected function aiClassifyPages(): void
    {
        $aiService = new \App\Services\AiPdfParsingService();

        // Use the new vision-based classification that works without OCR
        $classifications = $aiService->classifyDocumentPages($this->pdfDocument);

        // Check for error
        if (isset($classifications['error'])) {
            Notification::make()
                ->title('AI Classification Error')
                ->body($classifications['error'])
                ->danger()
                ->send();
            return;
        }

        // Check for parse error (still might have partial data)
        if (isset($classifications['parse_error'])) {
            Notification::make()
                ->title('AI Response Parse Error')
                ->body("Could not parse AI response: {$classifications['parse_error']}")
                ->warning()
                ->send();
            return;
        }

        // Classifications should be an array of page data
        if (!is_array($classifications) || empty($classifications)) {
            Notification::make()
                ->title('No Classifications Returned')
                ->body('The AI did not return any page classifications.')
                ->warning()
                ->send();
            return;
        }

        $updatedCount = 0;

        foreach ($classifications as $classification) {
            $pageNumber = $classification['page_number'] ?? null;
            if (!$pageNumber) {
                continue;
            }

            // Update form data for this page (0-indexed)
            $pageIndex = $pageNumber - 1;
            if (isset($this->data['page_metadata'][$pageIndex])) {
                $this->data['page_metadata'][$pageIndex]['primary_purpose'] = $classification['primary_purpose'] ?? null;
                $this->data['page_metadata'][$pageIndex]['page_label'] = $classification['page_label'] ?? null;
                $this->data['page_metadata'][$pageIndex]['has_hardware_schedule'] = $classification['has_hardware_schedule'] ?? false;
                $this->data['page_metadata'][$pageIndex]['has_material_spec'] = $classification['has_material_spec'] ?? false;

                // Store linear feet and pricing tier for elevations
                if (!empty($classification['linear_feet'])) {
                    $this->data['page_metadata'][$pageIndex]['linear_feet'] = $classification['linear_feet'];
                }
                if (!empty($classification['pricing_tier'])) {
                    $this->data['page_metadata'][$pageIndex]['pricing_tier'] = (string) $classification['pricing_tier'];
                }

                // Store rooms if mentioned
                if (!empty($classification['rooms_mentioned'])) {
                    $this->data['page_metadata'][$pageIndex]['rooms_on_page'] = $classification['rooms_mentioned'];
                }

                // Store brief description in page notes
                if (!empty($classification['brief_description'])) {
                    $this->data['page_metadata'][$pageIndex]['page_notes'] = $classification['brief_description'];
                }

                $updatedCount++;
            }
        }

        // Also save to database
        $aiService->applyBulkClassification($this->pdfDocument, $classifications);

        // Refresh form state
        $this->form->fill($this->data);

        Notification::make()
            ->title('AI Classification Complete')
            ->body("Classified {$updatedCount} pages using Gemini Vision. Review and adjust as needed.")
            ->success()
            ->send();
    }

    /**
     * Extract structured data from the PDF cover page using AI vision
     * Populates: address, designer info, revision, scope estimates
     */
    protected function extractCoverPageData($get, $set): void
    {
        $extractor = new \App\Services\PdfCoverPageExtractor();

        Notification::make()
            ->title('Extracting Cover Page Data...')
            ->body('AI is analyzing the cover page. This may take a moment.')
            ->info()
            ->send();

        $extracted = $extractor->extract($this->pdfDocument);

        // Check for errors
        if (isset($extracted['error'])) {
            Notification::make()
                ->title('Extraction Failed')
                ->body($extracted['error'] . (isset($extracted['details']) ? ": {$extracted['details']}" : ''))
                ->danger()
                ->send();
            return;
        }

        // Populate address fields
        if (!empty($extracted['project']['address'])) {
            $address = $extracted['project']['address'];
            $set('cover_address_street', $address['street'] ?? null);
            $set('cover_address_city', $address['city'] ?? null);
            $set('cover_address_state', $address['state'] ?? null);
            $set('cover_address_zip', $address['zip'] ?? null);
        }

        // Populate designer info
        if (!empty($extracted['designer'])) {
            $designer = $extracted['designer'];
            $set('cover_designer_company', $designer['company_name'] ?? null);
            $set('cover_designer_drawn_by', $designer['drawn_by'] ?? null);
            $set('cover_designer_approved_by', $designer['approved_by'] ?? null);
            $set('cover_designer_phone', $designer['phone'] ?? null);
            $set('cover_designer_email', $designer['email'] ?? null);
        }

        // Populate revision info
        if (!empty($extracted['revision'])) {
            $revision = $extracted['revision'];
            $set('drawing_number', $revision['current'] ?? null);
            $set('cover_revision_date', $revision['date'] ?? null);
        }

        // Populate project description
        if (!empty($extracted['project']['description'])) {
            $set('page_label', $extracted['project']['description']);
        }

        // Populate scope estimate (designer's line items)
        if (!empty($extracted['scope_estimate']) && is_array($extracted['scope_estimate'])) {
            $scopeItems = [];
            foreach ($extracted['scope_estimate'] as $item) {
                $scopeItems[] = [
                    'item_type' => $item['item_type'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => $item['unit'] ?? 'LF',
                ];
            }
            $set('scope_estimate', $scopeItems);
        }

        // Populate rooms mentioned
        if (!empty($extracted['rooms_mentioned']) && is_array($extracted['rooms_mentioned'])) {
            $set('rooms_mentioned', $extracted['rooms_mentioned']);
        }

        // Store the raw extracted data in session for later use
        session(['cover_page_extracted' => $extracted]);

        Notification::make()
            ->title('Cover Page Data Extracted')
            ->body('Review the extracted data above and make any corrections needed.')
            ->success()
            ->send();
    }

    /**
     * Generate a descriptive label for page items by looking up the key in form data
     * This method accesses $this->data directly to get accurate page information
     *
     * @param string|int $key The repeater item key
     */
    protected function getPageItemLabelByKey($key): string
    {
        // Look up the page data from our form data using the key
        $pageMetadata = $this->data['page_metadata'] ?? [];

        // Find the page data for this key
        $pageData = $pageMetadata[$key] ?? null;

        // If we can't find the data, find position by iterating
        if (!$pageData && !empty($pageMetadata)) {
            $position = 1;
            foreach ($pageMetadata as $k => $data) {
                if ($k === $key) {
                    $pageData = $data;
                    break;
                }
                $position++;
            }
            // If we found the position but data is missing page_number, use position
            if ($pageData && empty($pageData['page_number'])) {
                $pageData['page_number'] = $position;
            }
        }

        // Fallback to just showing the index if nothing found
        if (!$pageData) {
            // Calculate position from array keys
            $keys = array_keys($pageMetadata);
            $position = array_search($key, $keys);
            $pageNum = $position !== false ? $position + 1 : '?';
            return "Page {$pageNum} — Click to classify";
        }

        // Now use the page data to generate the label
        return $this->getPageItemLabel($pageData);
    }

    /**
     * Generate a descriptive label for page items in the repeater
     * Simplified for "Don't Make Me Think" UX
     *
     * @param array $state The repeater item state
     * @param string|int|null $key The repeater item key (array index)
     */
    protected function getPageItemLabel(array $state, $key = null): string
    {
        // Use page_number from state
        $pageNum = $state['page_number'] ?? '?';

        // If page has a label, show it prominently
        if (!empty($state['page_label'])) {
            return "Page {$pageNum}: {$state['page_label']}";
        }

        // Otherwise show the type with an icon hint
        if (!empty($state['primary_purpose'])) {
            $icons = [
                'cover' => '📄',
                'floor_plan' => '🗺️',
                'elevations' => '📐',
                'countertops' => '🔲',
                'reference' => '📷',
                'other' => '📎',
            ];
            $icon = $icons[$state['primary_purpose']] ?? '';
            $purposeLabels = \App\Models\PdfPage::PRIMARY_PURPOSES;
            $label = $purposeLabels[$state['primary_purpose']] ?? ucwords(str_replace('_', ' ', $state['primary_purpose']));
            return "Page {$pageNum} {$icon} {$label}";
        }

        // Unclassified - make it obvious
        return "Page {$pageNum} — Click to classify";
    }

    /**
     * Step 2: Define Full Project Hierarchy
     * Project → Room → Room Location → Cabinet Run → Cabinet → Section → Components
     */
    protected function getStep2Schema(): array
    {
        return [
            \Filament\Forms\Components\Placeholder::make('rooms_info')
                ->label('')
                ->content(function () {
                    $roomCount = Room::where('project_id', $this->record->id)->count();
                    if ($roomCount > 0) {
                        return "📋 **{$roomCount} existing room(s)** loaded from project. Edit or add more below.";
                    }
                    return "➕ No rooms defined yet. Add rooms and their full hierarchy below.";
                })
                ->columnSpanFull(),

            // LEVEL 1: Rooms
            Repeater::make('rooms')
                ->label('Rooms')
                ->schema([
                    \Filament\Forms\Components\Hidden::make('room_id'),

                    Select::make('room_type')
                        ->label('Room Type')
                        ->options([
                            'kitchen' => 'Kitchen',
                            'bathroom' => 'Bathroom',
                            'pantry' => 'Pantry',
                            'laundry' => 'Laundry',
                            'mudroom' => 'Mudroom',
                            'closet' => 'Closet',
                            'office' => 'Office',
                            'bedroom' => 'Bedroom',
                            'living_room' => 'Living Room',
                            'dining_room' => 'Dining Room',
                            'other' => 'Other',
                        ])
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state && ! $get('room_name')) {
                                $set('room_name', ucwords(str_replace('_', ' ', $state)));
                            }
                        }),

                    TextInput::make('room_name')
                        ->label('Room Name')
                        ->required()
                        ->placeholder('e.g., Kitchen, Master Pantry'),

                    // LEVEL 2: Room Locations
                    Repeater::make('locations')
                        ->label('Locations in this Room')
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('location_id'),

                            TextInput::make('location_name')
                                ->label('Location Name')
                                ->required()
                                ->placeholder('e.g., North Wall, South Wall, Island'),

                            Select::make('location_type')
                                ->label('Location Type')
                                ->options([
                                    'wall' => 'Wall',
                                    'island' => 'Island',
                                    'peninsula' => 'Peninsula',
                                    'corner' => 'Corner',
                                    'alcove' => 'Alcove',
                                    'freestanding' => 'Freestanding',
                                ])
                                ->default('wall')
                                ->native(false),

                            // LEVEL 3: Cabinet Runs
                            Repeater::make('cabinet_runs')
                                ->label('Cabinet Runs')
                                ->schema([
                                    \Filament\Forms\Components\Hidden::make('cabinet_run_id'),

                                    TextInput::make('run_name')
                                        ->label('Run Name')
                                        ->required()
                                        ->placeholder('e.g., Base Cabinets, Upper Cabinets'),

                                    Select::make('run_type')
                                        ->label('Run Type')
                                        ->options([
                                            'base' => 'Base Cabinets',
                                            'upper' => 'Upper/Wall Cabinets',
                                            'tall' => 'Tall/Pantry Cabinets',
                                            'vanity' => 'Vanity',
                                            'island' => 'Island',
                                            'desk' => 'Desk',
                                            'bookcase' => 'Bookcase',
                                            'entertainment' => 'Entertainment Center',
                                            'other' => 'Other',
                                        ])
                                        ->default('base')
                                        ->native(false),

                                    Select::make('cabinet_level')
                                        ->label('Pricing Level')
                                        ->options(fn () => $this->getPricingLevelOptions())
                                        ->default('2')
                                        ->required()
                                        ->native(false),

                                    TextInput::make('linear_feet')
                                        ->label('Linear Feet')
                                        ->numeric()
                                        ->step(0.25)
                                        ->suffix('LF')
                                        ->live(onBlur: true),

                                    TextInput::make('run_notes')
                                        ->label('Notes')
                                        ->placeholder('Material, finish details...')
                                        ->columnSpanFull(),

                                    // LEVEL 4: Individual Cabinets
                                    Repeater::make('cabinets')
                                        ->label('Cabinets')
                                        ->schema([
                                            \Filament\Forms\Components\Hidden::make('cabinet_id'),

                                            TextInput::make('cabinet_number')
                                                ->label('Cabinet #')
                                                ->placeholder('e.g., B1, U2'),

                                            TextInput::make('width_inches')
                                                ->label('Width')
                                                ->numeric()
                                                ->step(0.125)
                                                ->suffix('"')
                                                ->placeholder('24'),

                                            TextInput::make('height_inches')
                                                ->label('Height')
                                                ->numeric()
                                                ->step(0.125)
                                                ->suffix('"')
                                                ->placeholder('30'),

                                            TextInput::make('depth_inches')
                                                ->label('Depth')
                                                ->numeric()
                                                ->step(0.125)
                                                ->suffix('"')
                                                ->placeholder('24'),

                                            TextInput::make('cabinet_notes')
                                                ->label('Notes')
                                                ->placeholder('Special instructions...')
                                                ->columnSpanFull(),

                                            // LEVEL 5: Cabinet Sections
                                            Repeater::make('sections')
                                                ->label('Sections')
                                                ->schema([
                                                    \Filament\Forms\Components\Hidden::make('section_id'),

                                                    TextInput::make('section_name')
                                                        ->label('Section Name')
                                                        ->placeholder('e.g., Left Door, Right Drawer Bank'),

                                                    Select::make('section_type')
                                                        ->label('Section Type')
                                                        ->options([
                                                            'door' => 'Door Section',
                                                            'drawer_bank' => 'Drawer Bank',
                                                            'open_shelf' => 'Open Shelving',
                                                            'appliance' => 'Appliance Opening',
                                                            'false_front' => 'False Front',
                                                            'pullout' => 'Pullout Section',
                                                            'lazy_susan' => 'Lazy Susan',
                                                            'other' => 'Other',
                                                        ])
                                                        ->native(false),

                                                    TextInput::make('section_width')
                                                        ->label('Width')
                                                        ->numeric()
                                                        ->step(0.125)
                                                        ->suffix('"'),

                                                    TextInput::make('section_height')
                                                        ->label('Height')
                                                        ->numeric()
                                                        ->step(0.125)
                                                        ->suffix('"'),

                                                    // LEVEL 6: Components (Doors, Drawers, Pullouts, Shelves)
                                                    \Filament\Schemas\Components\Tabs::make('Components')
                                                        ->tabs([
                                                            \Filament\Schemas\Components\Tabs\Tab::make('Doors')
                                                                ->icon('heroicon-o-square-2-stack')
                                                                ->schema([
                                                                    Repeater::make('doors')
                                                                        ->label('')
                                                                        ->schema([
                                                                            \Filament\Forms\Components\Hidden::make('door_id'),
                                                                            TextInput::make('door_name')
                                                                                ->label('Door Name')
                                                                                ->placeholder('e.g., Left Door'),
                                                                            TextInput::make('door_width')
                                                                                ->label('Width')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            TextInput::make('door_height')
                                                                                ->label('Height')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            Select::make('hinge_side')
                                                                                ->label('Hinge Side')
                                                                                ->options([
                                                                                    'left' => 'Left',
                                                                                    'right' => 'Right',
                                                                                    'top' => 'Top (Flipper)',
                                                                                    'bottom' => 'Bottom',
                                                                                ])
                                                                                ->native(false),
                                                                            \Filament\Forms\Components\Toggle::make('has_glass')
                                                                                ->label('Glass Panel'),
                                                                            Repeater::make('products')
                                                                                ->label('Products')
                                                                                ->schema([
                                                                                    Select::make('product_id')
                                                                                        ->label('Product')
                                                                                        ->options(fn () => \Webkul\Product\Models\Product::pluck('name', 'id'))
                                                                                        ->searchable()
                                                                                        ->required(),
                                                                                    TextInput::make('quantity')
                                                                                        ->label('Qty')
                                                                                        ->numeric()
                                                                                        ->default(1)
                                                                                        ->minValue(1),
                                                                                ])
                                                                                ->columns(2)
                                                                                ->defaultItems(0)
                                                                                ->addActionLabel('Add Product')
                                                                                ->columnSpanFull()
                                                                                ->collapsible()
                                                                                ->collapsed(),
                                                                        ])
                                                                        ->columns(3)
                                                                        ->defaultItems(0)
                                                                        ->collapsible()
                                                                        ->itemLabel(fn ($state) => $state['door_name'] ?? 'Door'),
                                                                ]),

                                                            \Filament\Schemas\Components\Tabs\Tab::make('Drawers')
                                                                ->icon('heroicon-o-inbox-stack')
                                                                ->schema([
                                                                    Repeater::make('drawers')
                                                                        ->label('')
                                                                        ->schema([
                                                                            \Filament\Forms\Components\Hidden::make('drawer_id'),
                                                                            TextInput::make('drawer_name')
                                                                                ->label('Drawer Name')
                                                                                ->placeholder('e.g., Top Drawer'),
                                                                            TextInput::make('front_width')
                                                                                ->label('Front Width')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            TextInput::make('front_height')
                                                                                ->label('Front Height')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            TextInput::make('box_depth')
                                                                                ->label('Box Depth')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            Select::make('slide_type')
                                                                                ->label('Slide Type')
                                                                                ->options([
                                                                                    'soft_close' => 'Soft Close',
                                                                                    'undermount' => 'Undermount',
                                                                                    'side_mount' => 'Side Mount',
                                                                                    'center_mount' => 'Center Mount',
                                                                                ])
                                                                                ->native(false),
                                                                            Repeater::make('products')
                                                                                ->label('Products')
                                                                                ->schema([
                                                                                    Select::make('product_id')
                                                                                        ->label('Product')
                                                                                        ->options(fn () => \Webkul\Product\Models\Product::pluck('name', 'id'))
                                                                                        ->searchable()
                                                                                        ->required(),
                                                                                    TextInput::make('quantity')
                                                                                        ->label('Qty')
                                                                                        ->numeric()
                                                                                        ->default(1)
                                                                                        ->minValue(1),
                                                                                ])
                                                                                ->columns(2)
                                                                                ->defaultItems(0)
                                                                                ->addActionLabel('Add Product')
                                                                                ->columnSpanFull()
                                                                                ->collapsible()
                                                                                ->collapsed(),
                                                                        ])
                                                                        ->columns(3)
                                                                        ->defaultItems(0)
                                                                        ->collapsible()
                                                                        ->itemLabel(fn ($state) => $state['drawer_name'] ?? 'Drawer'),
                                                                ]),

                                                            \Filament\Schemas\Components\Tabs\Tab::make('Pullouts')
                                                                ->icon('heroicon-o-arrows-right-left')
                                                                ->schema([
                                                                    Repeater::make('pullouts')
                                                                        ->label('')
                                                                        ->schema([
                                                                            \Filament\Forms\Components\Hidden::make('pullout_id'),
                                                                            TextInput::make('pullout_name')
                                                                                ->label('Pullout Name')
                                                                                ->placeholder('e.g., Trash Pullout'),
                                                                            Select::make('pullout_type')
                                                                                ->label('Type')
                                                                                ->options([
                                                                                    'trash' => 'Trash/Recycling',
                                                                                    'spice' => 'Spice Rack',
                                                                                    'tray' => 'Tray Divider',
                                                                                    'mixer_lift' => 'Mixer Lift',
                                                                                    'cookie_sheet' => 'Cookie Sheet',
                                                                                    'pot_organizer' => 'Pot Organizer',
                                                                                    'other' => 'Other',
                                                                                ])
                                                                                ->native(false),
                                                                            TextInput::make('pullout_width')
                                                                                ->label('Width')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            TextInput::make('pullout_depth')
                                                                                ->label('Depth')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            Repeater::make('products')
                                                                                ->label('Products')
                                                                                ->schema([
                                                                                    Select::make('product_id')
                                                                                        ->label('Product')
                                                                                        ->options(fn () => \Webkul\Product\Models\Product::pluck('name', 'id'))
                                                                                        ->searchable()
                                                                                        ->required(),
                                                                                    TextInput::make('quantity')
                                                                                        ->label('Qty')
                                                                                        ->numeric()
                                                                                        ->default(1)
                                                                                        ->minValue(1),
                                                                                ])
                                                                                ->columns(2)
                                                                                ->defaultItems(0)
                                                                                ->addActionLabel('Add Product')
                                                                                ->columnSpanFull()
                                                                                ->collapsible()
                                                                                ->collapsed(),
                                                                        ])
                                                                        ->columns(2)
                                                                        ->defaultItems(0)
                                                                        ->collapsible()
                                                                        ->itemLabel(fn ($state) => $state['pullout_name'] ?? 'Pullout'),
                                                                ]),

                                                            \Filament\Schemas\Components\Tabs\Tab::make('Shelves')
                                                                ->icon('heroicon-o-bars-3')
                                                                ->schema([
                                                                    Repeater::make('shelves')
                                                                        ->label('')
                                                                        ->schema([
                                                                            \Filament\Forms\Components\Hidden::make('shelf_id'),
                                                                            TextInput::make('shelf_name')
                                                                                ->label('Shelf Name')
                                                                                ->placeholder('e.g., Adjustable Shelf'),
                                                                            Select::make('shelf_type')
                                                                                ->label('Type')
                                                                                ->options([
                                                                                    'fixed' => 'Fixed',
                                                                                    'adjustable' => 'Adjustable',
                                                                                    'roll_out' => 'Roll-Out',
                                                                                    'half_depth' => 'Half Depth',
                                                                                    'glass' => 'Glass',
                                                                                ])
                                                                                ->native(false),
                                                                            TextInput::make('shelf_width')
                                                                                ->label('Width')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            TextInput::make('shelf_depth')
                                                                                ->label('Depth')
                                                                                ->numeric()
                                                                                ->step(0.125)
                                                                                ->suffix('"'),
                                                                            TextInput::make('shelf_quantity')
                                                                                ->label('Qty')
                                                                                ->numeric()
                                                                                ->default(1),
                                                                            Repeater::make('products')
                                                                                ->label('Products')
                                                                                ->schema([
                                                                                    Select::make('product_id')
                                                                                        ->label('Product')
                                                                                        ->options(fn () => \Webkul\Product\Models\Product::pluck('name', 'id'))
                                                                                        ->searchable()
                                                                                        ->required(),
                                                                                    TextInput::make('quantity')
                                                                                        ->label('Qty')
                                                                                        ->numeric()
                                                                                        ->default(1)
                                                                                        ->minValue(1),
                                                                                ])
                                                                                ->columns(2)
                                                                                ->defaultItems(0)
                                                                                ->addActionLabel('Add Product')
                                                                                ->columnSpanFull()
                                                                                ->collapsible()
                                                                                ->collapsed(),
                                                                        ])
                                                                        ->columns(3)
                                                                        ->defaultItems(0)
                                                                        ->collapsible()
                                                                        ->itemLabel(fn ($state) => ($state['shelf_name'] ?? 'Shelf') . ' x' . ($state['shelf_quantity'] ?? 1)),
                                                                ]),
                                                        ])
                                                        ->columnSpanFull(),
                                                ])
                                                ->columns(2)
                                                ->defaultItems(0)
                                                ->collapsible()
                                                ->collapsed()
                                                ->itemLabel(fn ($state) => ($state['section_name'] ?? 'Section') . ' (' . ($state['section_type'] ?? 'unknown') . ')')
                                                ->addActionLabel('Add Section')
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(4)
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->collapsed()
                                        ->itemLabel(fn ($state) => ($state['cabinet_number'] ?? 'Cabinet') . ' - ' . ($state['width_inches'] ?? '?') . '"W x ' . ($state['height_inches'] ?? '?') . '"H')
                                        ->addActionLabel('Add Cabinet')
                                        ->columnSpanFull(),
                                ])
                                ->columns(3)
                                ->defaultItems(1)
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn ($state) => ($state['run_name'] ?? 'New Run') . ' - ' . ($state['linear_feet'] ?? '0') . ' LF')
                                ->addActionLabel('Add Cabinet Run'),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn ($state) => ($state['location_name'] ?? 'Location') . ' (' . ucwords($state['location_type'] ?? 'wall') . ')')
                        ->addActionLabel('Add Location'),
                ])
                ->columns(2)
                ->defaultItems(1)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn ($state) => ($state['room_name'] ?? 'New Room') . ' (' . ucwords(str_replace('_', ' ', $state['room_type'] ?? 'Unknown')) . ')')
                ->addActionLabel('Add Room')
                ->columnSpanFull(),
        ];
    }

    /**
     * Step 3: Additional Items & Review
     */
    protected function getStep3Schema(): array
    {
        return [
            // Pricing Summary
            \Filament\Schemas\Components\Section::make('Pricing Summary')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('total_linear_feet')
                        ->label('Total Linear Feet')
                        ->content(function ($get) {
                            $rooms = $get('rooms') ?? [];
                            $total = 0;
                            foreach ($rooms as $room) {
                                foreach ($room['cabinet_runs'] ?? [] as $run) {
                                    $total += (float) ($run['linear_feet'] ?? 0);
                                }
                            }
                            return number_format($total, 2) . ' LF';
                        }),

                    \Filament\Forms\Components\Placeholder::make('estimated_total')
                        ->label('Estimated Total')
                        ->content(function ($get) {
                            $rooms = $get('rooms') ?? [];
                            $total = 0;
                            foreach ($rooms as $room) {
                                foreach ($room['cabinet_runs'] ?? [] as $run) {
                                    $level = (int) ($run['cabinet_level'] ?? 2);
                                    $lf = (float) ($run['linear_feet'] ?? 0);
                                    $product = $this->getCabinetProduct($level);
                                    if ($product && isset($product['unit_price'])) {
                                        $total += $lf * $product['unit_price'];
                                    }
                                }
                            }
                            return '$' . number_format($total, 2);
                        }),
                ])
                ->columns(2),

            // Additional Items
            \Filament\Schemas\Components\Section::make('Additional Items')
                ->description('Add countertops, shelves, or other non-cabinet items')
                ->schema([
                    Repeater::make('additional_items')
                        ->label('')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->relationship('product', 'name')
                                ->native(false)
                                ->required(),

                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->step(0.25),

                            TextInput::make('notes')
                                ->label('Notes')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    public function getPdfUrl(): string
    {
        return Storage::disk('public')->url($this->pdfDocument->file_path);
    }

    /**
     * Next Page
     *
     * @return void
     */
    public function nextPage(): void
    {
        $this->currentPage++;
    }

    /**
     * Previous Page
     *
     * @return void
     */
    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    /**
     * Create Sales Order
     *
     * @return void
     */
    public function createSalesOrder(): void
    {
        $data = $this->form->getState();

        // Validate project has a customer
        if (! $this->record->partner_id) {
            Notification::make()
                ->title('No Customer Assigned')
                ->body('Please assign a customer to this project before creating a sales order.')
                ->danger()
                ->send();

            return;
        }

        $now = now();

        // Step 1: Save enhanced page metadata to pdf_pages table
        $this->savePageMetadata($data['page_metadata'] ?? []);

        // Create sales order
        $salesOrderId = \DB::table('sales_orders')->insertGetId([
            'project_id'          => $this->record->id,
            'partner_id'          => $this->record->partner_id,
            'partner_invoice_id'  => $this->record->partner_id,
            'partner_shipping_id' => $this->record->partner_id,
            'company_id'          => $this->record->company_id ?? 1,
            'state'               => 'draft',
            'invoice_status'      => 'no',
            'date_order'          => $now,
            'currency_id'         => 1,
            'creator_id'          => auth()->id() ?? 1,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        $subtotal = 0;
        $lineNumber = 1;

        // Process rooms and cabinet runs
        if (! empty($data['rooms'])) {
            foreach ($data['rooms'] as $room) {
                $roomName = $room['room_name'];

                if (! empty($room['cabinet_runs'])) {
                    foreach ($room['cabinet_runs'] as $run) {
                        $level = (int) $run['cabinet_level'];
                        $linearFeet = (float) $run['linear_feet'];

                        // Get Cabinet product with pricing level
                        $cabinetProduct = $this->getCabinetProduct($level);

                        if ($cabinetProduct) {
                            $lineTotal = $linearFeet * $cabinetProduct['unit_price'];
                            $subtotal += $lineTotal;

                            $lineName = "Cabinet - {$roomName} - {$run['run_name']} (Level {$level})";
                            if (! empty($run['notes'])) {
                                $lineName .= "\nNotes: {$run['notes']}";
                            }

                            \DB::table('sales_order_lines')->insert([
                                'order_id'        => $salesOrderId,
                                'product_id'      => $cabinetProduct['product_id'],
                                'name'            => $lineName,
                                'sort'            => $lineNumber++,
                                'product_uom_qty' => $linearFeet,
                                'price_unit'      => $cabinetProduct['unit_price'],
                                'price_subtotal'  => $lineTotal,
                                'qty_delivered'   => 0,
                                'qty_to_invoice'  => $linearFeet,
                                'qty_invoiced'    => 0,
                                'creator_id'      => auth()->id() ?? 1,
                                'created_at'      => $now,
                                'updated_at'      => $now,
                            ]);
                        }
                    }
                }
            }
        }

        // Process additional items
        if (! empty($data['additional_items'])) {
            foreach ($data['additional_items'] as $item) {
                $product = \DB::table('products_products')
                    ->where('id', $item['product_id'])
                    ->first(['id', 'name', 'price']);

                if ($product) {
                    $quantity = (float) $item['quantity'];
                    $lineTotal = $quantity * $product->price;
                    $subtotal += $lineTotal;

                    $lineName = $product->name;
                    if (! empty($item['notes'])) {
                        $lineName .= "\nNotes: {$item['notes']}";
                    }

                    \DB::table('sales_order_lines')->insert([
                        'order_id'        => $salesOrderId,
                        'product_id'      => $product->id,
                        'name'            => $lineName,
                        'sort'            => $lineNumber++,
                        'product_uom_qty' => $quantity,
                        'price_unit'      => $product->price,
                        'price_subtotal'  => $lineTotal,
                        'qty_delivered'   => 0,
                        'qty_to_invoice'  => $quantity,
                        'qty_invoiced'    => 0,
                        'creator_id'      => auth()->id() ?? 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }
            }
        }

        // Update sales order totals
        \DB::table('sales_orders')
            ->where('id', $salesOrderId)
            ->update([
                'amount_untaxed' => $subtotal,
                'amount_total'   => $subtotal,
                'updated_at'     => $now,
            ]);

        Notification::make()
            ->title('Sales Order Created')
            ->body('Sales order created successfully with '.($lineNumber - 1).' line items. Total: $'.number_format($subtotal, 2))
            ->success()
            ->send();

        // Send complexity and material alerts to appropriate team members
        $this->sendProjectAlerts($salesOrderId, $data);

        $this->redirect(ProjectResource::getUrl('view', ['record' => $this->record]));
    }

    /**
     * Get Cabinet Product
     *
     * @param int $level
     * @return ?array
     */
    protected function getCabinetProduct(int $level = 2): ?array
    {
        $product = \DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first(['id', 'name', 'price']);

        if (! $product) {
            return null;
        }

        $pricingLevelAttr = \DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first(['id']);

        if (! $pricingLevelAttr) {
            return null;
        }

        $levelOption = \DB::table('products_attribute_options')
            ->where('attribute_id', $pricingLevelAttr->id)
            ->where('name', 'LIKE', "Level {$level}%")
            ->first(['id', 'name', 'extra_price']);

        if (! $levelOption) {
            return null;
        }

        $unitPrice = floatval($product->price) + floatval($levelOption->extra_price);

        return [
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'unit_price'   => $unitPrice,
        ];
    }

    /**
     * Try Automatic
     *
     * @return void
     */
    public function tryAutomatic(): void
    {
        try {
            $parsingService = app(PdfParsingService::class);
            $parsedData = $parsingService->parseArchitecturalDrawing($this->pdfDocument);

            $rooms = [];
            $additionalItems = [];

            // Group line items by product type
            foreach ($parsedData['line_items'] as $lineItem) {
                if (! $lineItem['product_id']) {
                    continue; // Skip unmatched items
                }

                // Check if it's a cabinet item
                $product = \DB::table('products_products')
                    ->where('id', $lineItem['product_id'])
                    ->where('reference', 'CABINET')
                    ->exists();

                if ($product) {
                    // Extract level from attribute selections
                    $level = 2; // Default
                    if (! empty($lineItem['attribute_selections'])) {
                        foreach ($lineItem['attribute_selections'] as $attr) {
                            if ($attr['attribute_name'] === 'Pricing Level') {
                                preg_match('/Level (\d)/', $attr['option_name'], $matches);
                                if (! empty($matches[1])) {
                                    $level = (int) $matches[1];
                                }
                            }
                        }
                    }

                    // Add as cabinet run in "Auto-Parsed" room
                    if (! isset($rooms['Auto-Parsed'])) {
                        $rooms['Auto-Parsed'] = [
                            'room_name'    => 'Auto-Parsed Items',
                            'cabinet_runs' => [],
                        ];
                    }

                    $rooms['Auto-Parsed']['cabinet_runs'][] = [
                        'run_name'      => $lineItem['raw_name'],
                        'cabinet_level' => (string) $level,
                        'linear_feet'   => $lineItem['quantity'],
                        'notes'         => 'Automatically extracted from PDF',
                    ];
                } else {
                    // Non-cabinet items go to additional_items
                    $additionalItems[] = [
                        'product_id' => $lineItem['product_id'],
                        'quantity'   => $lineItem['quantity'],
                        'notes'      => 'Automatically extracted from PDF: '.$lineItem['raw_name'],
                    ];
                }
            }

            // Convert rooms array to form format
            $formRooms = array_values($rooms);

            $this->form->fill([
                'rooms'            => $formRooms,
                'additional_items' => $additionalItems,
            ]);

            $matchedCount = count($parsedData['line_items']);
            Notification::make()
                ->title('Automatic Parsing Complete')
                ->body("Pre-filled form with {$matchedCount} items extracted from PDF. Please review and adjust as needed.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Automatic Parsing Failed')
                ->body("Could not automatically parse PDF: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }

    /**
     * Get PDF page ID with caching to avoid N+1 queries
     */
    /**
     * Get Pdf Page Id
     *
     * @param int $pageNumber
     * @return ?int
     */
    protected function getPdfPageId(int $pageNumber): ?int
    {
        if (! isset($this->pdfPageIdCache[$pageNumber])) {
            $this->pdfPageIdCache[$pageNumber] = \App\Models\PdfPage::where('document_id', $this->pdfDocument->id)
                ->where('page_number', $pageNumber)
                ->value('id');
        }

        return $this->pdfPageIdCache[$pageNumber];
    }

    /**
     * Persist page classification to database immediately
     *
     * This is called when the user selects a page type via ToggleButtons.
     * We save immediately to pdf_pages for progressive data persistence.
     *
     * @param int $pageNumber
     * @param string|null $primaryPurpose
     * @param array $additionalData Optional additional data (page_label, etc.)
     * @return \App\Models\PdfPage|null
     */
    protected function persistPageClassification(int $pageNumber, ?string $primaryPurpose, array $additionalData = []): ?\App\Models\PdfPage
    {
        if (!$this->pdfDocument) {
            return null;
        }

        // Find or create the page record
        $pdfPage = \App\Models\PdfPage::firstOrCreate(
            [
                'document_id' => $this->pdfDocument->id,
                'page_number' => $pageNumber,
            ],
            [
                'project_id' => $this->record->id,
                'creator_id' => auth()->id(),
            ]
        );

        // Use the model's classify method for proper status tracking
        if ($primaryPurpose) {
            $pdfPage->classify(
                $primaryPurpose,
                $additionalData['page_label'] ?? null,
                auth()->id()
            );
        } else {
            // Clear classification if null
            $pdfPage->update([
                'primary_purpose' => null,
                'page_label' => null,
                'processing_status' => \App\Models\PdfPage::STATUS_PENDING,
            ]);
        }

        // Update any additional fields from additionalData
        $updateFields = [];
        $allowedFields = ['page_notes', 'has_hardware_schedule', 'has_material_spec', 'drawing_number', 'view_types', 'section_labels'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $additionalData)) {
                $updateFields[$field] = $additionalData[$field];
            }
        }

        if (!empty($updateFields)) {
            $pdfPage->update($updateFields);
        }

        // Clear cache for this page
        unset($this->pdfPageIdCache[$pageNumber]);

        return $pdfPage->fresh();
    }

    /**
     * Get or create PdfPage model for a page number
     *
     * @param int $pageNumber
     * @return \App\Models\PdfPage
     */
    protected function getOrCreatePdfPage(int $pageNumber): \App\Models\PdfPage
    {
        return \App\Models\PdfPage::firstOrCreate(
            [
                'document_id' => $this->pdfDocument->id,
                'page_number' => $pageNumber,
            ],
            [
                'project_id' => $this->record->id,
                'creator_id' => auth()->id(),
            ]
        );
    }

    /**
     * Send alerts to team members based on project complexity and requirements
     *
     * Triggers alerts for:
     * - Level 4/5 complexity → Levi (Production Lead)
     * - Ferry/Island delivery → JG (Delivery Coordinator)
     * - Premium materials ($185+/LF) → Purchasing Manager
     */
    /**
     * Send Project Alerts
     *
     * @param int $salesOrderId
     * @param array $formData
     * @return void
     */
    protected function sendProjectAlerts(int $salesOrderId, array $formData): void
    {
        $hasHighComplexity = false;
        $hasFerryDelivery = false;
        $hasPremiumMaterials = false;

        // Check for Level 4/5 complexity in cabinet runs
        if (! empty($formData['rooms'])) {
            foreach ($formData['rooms'] as $room) {
                if (! empty($room['cabinet_runs'])) {
                    foreach ($room['cabinet_runs'] as $run) {
                        $level = (int) $run['cabinet_level'];
                        if ($level >= 4) {
                            $hasHighComplexity = true;
                            break 2; // Exit both foreach loops
                        }
                    }
                }
            }
        }

        // Check for ferry delivery requirement
        // This checks if site access plan indicates ferry requirement
        $hasFerryDelivery = \DB::table('projects_site_access_plans')
            ->where('project_id', $this->record->id)
            ->where('requires_ferry', true)
            ->exists();

        // Also check project tags for ferry/island indicators
        if (! $hasFerryDelivery) {
            $hasFerryDelivery = \DB::table('projects_project_tags')
                ->join('projects_tags', 'projects_tags.id', '=', 'projects_project_tags.tag_id')
                ->where('projects_project_tags.project_id', $this->record->id)
                ->whereIn('projects_tags.name', ['Ferry Access Required', 'Nantucket', 'Island Delivery', 'Martha\'s Vineyard'])
                ->exists();
        }

        // Check for premium materials
        // For now, we consider Level 4+ as using premium materials
        // In the future, this could check actual material selections
        $hasPremiumMaterials = $hasHighComplexity;

        // Send alerts if conditions met
        if ($hasHighComplexity) {
            $this->sendComplexityAlert();
        }

        if ($hasFerryDelivery) {
            $this->sendFerryDeliveryAlert();
        }

        if ($hasPremiumMaterials) {
            $this->sendPremiumMaterialsAlert();
        }
    }

    /**
     * Alert Levi (Production Lead) about high complexity project
     */
    /**
     * Send Complexity Alert
     *
     * @return void
     */
    protected function sendComplexityAlert(): void
    {
        $levi = Employee::where('company_id', $this->record->company_id)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%Levi%')
                    ->orWhere('job_title', 'LIKE', '%Production Lead%')
                    ->orWhere('job_title', 'LIKE', '%Shop Lead%');
            })
            ->first();

        if ($levi && $levi->user) {
            // Send FilamentPHP notification
            Notification::make()
                ->warning()
                ->title('🔧 High Complexity Project Alert')
                ->body("Project {$this->record->project_number} uses Level 4/5 pricing. Production review required before scheduling.")
                ->actions([
                    Action::make('view')
                        ->button()
                        ->label('View Project')
                        ->url(ProjectResource::getUrl('view', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($levi->user);

            // Log to Chatter activity feed
            $this->record->addMessage([
                'type'        => 'activity',
                'subject'     => 'High Complexity Alert',
                'body'        => "Level 4/5 complexity detected. {$levi->name} (Production Lead) notified for production review.",
                'is_internal' => true,
            ]);
        }
    }

    /**
     * Alert JG (Delivery Coordinator) about ferry delivery requirement
     */
    /**
     * Send Ferry Delivery Alert
     *
     * @return void
     */
    protected function sendFerryDeliveryAlert(): void
    {
        $jg = Employee::where('company_id', $this->record->company_id)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%JG%')
                    ->orWhere('job_title', 'LIKE', '%Delivery%')
                    ->orWhere('job_title', 'LIKE', '%CAD%')
                    ->orWhere('job_title', 'LIKE', '%Logistics%');
            })
            ->first();

        if ($jg && $jg->user) {
            // Send FilamentPHP notification
            Notification::make()
                ->info()
                ->title('🚢 Ferry Delivery Required')
                ->body("Project {$this->record->project_number} requires ferry/island delivery. Special logistics planning needed.")
                ->actions([
                    Action::make('view')
                        ->button()
                        ->label('View Project')
                        ->url(ProjectResource::getUrl('view', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($jg->user);

            // Log to Chatter activity feed
            $this->record->addMessage([
                'type'        => 'activity',
                'subject'     => 'Ferry Delivery Alert',
                'body'        => "Ferry/island delivery required. {$jg->name} (Delivery Coordinator) notified for logistics planning.",
                'is_internal' => true,
            ]);
        }
    }

    /**
     * Alert Purchasing Manager about premium materials requirement
     */
    /**
     * Send Premium Materials Alert
     *
     * @return void
     */
    protected function sendPremiumMaterialsAlert(): void
    {
        $purchasing = Employee::where('company_id', $this->record->company_id)
            ->where(function ($q) {
                $q->where('job_title', 'LIKE', '%Purchas%')
                    ->orWhere('job_title', 'LIKE', '%Inventory%')
                    ->orWhere('job_title', 'LIKE', '%Materials%');
            })
            ->first();

        if ($purchasing && $purchasing->user) {
            // Send FilamentPHP notification
            Notification::make()
                ->success()
                ->title('💎 Premium Materials Detected')
                ->body("Project {$this->record->project_number} requires premium materials. Early procurement recommended to ensure availability.")
                ->actions([
                    Action::make('view')
                        ->button()
                        ->label('View Project')
                        ->url(ProjectResource::getUrl('view', ['record' => $this->record->id])),
                ])
                ->sendToDatabase($purchasing->user);

            // Log to Chatter activity feed
            $this->record->addMessage([
                'type'        => 'activity',
                'subject'     => 'Premium Materials Alert',
                'body'        => "Premium materials required. {$purchasing->name} (Purchasing) notified for early procurement.",
                'is_internal' => true,
            ]);
        }
    }

    /**
     * Determine which annotation system version to use
     *
     * @return string 'v1' or 'v2'
     */
    /**
     * Annotation System Version
     *
     * @return string
     */
    public function annotationSystemVersion(): string
    {
        // Check user preference (can be set via settings)
        $userPreference = auth()->user()->settings['annotation_system_version'] ?? null;

        if ($userPreference) {
            return $userPreference;
        }

        // Check environment variable for global default
        return config('app.annotation_system_version', 'v1');
    }

    /**
     * Check if V2 annotation system should be used
     */
    /**
     * Use Annotation System V2
     *
     * @return bool
     */
    public function useAnnotationSystemV2(): bool
    {
        return $this->annotationSystemVersion() === 'v2';
    }

    /**
     * Get pricing level options from products or fallback to defaults
     *
     * @return array
     */
    public function getPricingLevelOptions(): array
    {
        if ($this->pricingLevelsCache !== null) {
            return $this->pricingLevelsCache;
        }

        // Try to get cabinet products from database - wrapped in try-catch for schema variations
        try {
            $products = \Webkul\Product\Models\Product::query()
                ->where(function ($q) {
                    $q->where('name', 'LIKE', '%Cabinet Level%')
                        ->orWhere('name', 'LIKE', '%Linear Foot%')
                        ->orWhere('sku', 'LIKE', 'CAB-LVL-%');
                })
                ->orderBy('name')
                ->get();

            if ($products->isNotEmpty()) {
                $this->pricingLevelsCache = [];
                foreach ($products as $product) {
                    // Extract level from name or sku
                    if (preg_match('/Level\s*(\d+)/i', $product->name, $matches)) {
                        $level = $matches[1];
                        $price = $product->lst_price ?? $product->standard_price ?? 0;
                        $this->pricingLevelsCache[$level] = "Level {$level} - {$product->name} (\${$price}/LF)";
                    }
                }

                if (! empty($this->pricingLevelsCache)) {
                    return $this->pricingLevelsCache;
                }
            }
        } catch (\Exception $e) {
            // Query failed - use fallback pricing levels
        }

        // Fallback to default pricing levels
        $this->pricingLevelsCache = [
            '1' => 'Level 1 - Basic ($138/LF)',
            '2' => 'Level 2 - Standard ($168/LF)',
            '3' => 'Level 3 - Enhanced ($192/LF)',
            '4' => 'Level 4 - Premium ($210/LF)',
            '5' => 'Level 5 - Custom ($225/LF)',
        ];

        return $this->pricingLevelsCache;
    }

    /**
     * Open the edit details modal for a specific page
     * Called via wire:click from the placeholder button
     */
    public function openEditDetailsModal(int $pageNumber): void
    {
        $this->editingPageNumber = $pageNumber;

        // Find the page data by page number
        $pageMetadata = $this->data['page_metadata'] ?? [];
        $pageData = null;

        foreach ($pageMetadata as $page) {
            if (($page['page_number'] ?? null) == $pageNumber) {
                $pageData = $page;
                break;
            }
        }

        if (!$pageData) {
            Notification::make()
                ->warning()
                ->title('Page not found')
                ->body("Could not find data for page {$pageNumber}")
                ->send();
            return;
        }

        // Fill the edit form with current data
        $this->editDetailsData = $this->getSlideOverFormDataFromArray($pageData);

        // For cover pages, pre-fill with project data if fields are empty
        $primaryPurpose = $pageData['primary_purpose'] ?? null;
        if ($primaryPurpose === 'cover') {
            $this->prefillCoverPageFromProject();
        }

        // Open the modal using Filament's dispatch method
        $this->dispatch('open-modal', id: 'edit-details-modal');
    }

    /**
     * Pre-fill cover page fields from project data when they are empty
     */
    protected function prefillCoverPageFromProject(): void
    {
        if (!$this->record) {
            return;
        }

        // Get project address (primary address first)
        $address = $this->record->addresses()
            ->where('is_primary', true)
            ->first() ?? $this->record->addresses()->first();

        if ($address) {
            // Only fill if current value is empty
            if (empty($this->editDetailsData['cover_address_street'])) {
                $this->editDetailsData['cover_address_street'] = $address->street1;
            }
            if (empty($this->editDetailsData['cover_address_city'])) {
                $this->editDetailsData['cover_address_city'] = $address->city;
            }
            if (empty($this->editDetailsData['cover_address_state'])) {
                // Get state - try relationship first, then lookup by ID
                $stateCode = $address->state?->code ?? $address->state?->name;
                if (empty($stateCode) && $address->state_id) {
                    $state = \Webkul\Support\Models\State::find($address->state_id);
                    $stateCode = $state?->code ?? $state?->name;
                }
                $this->editDetailsData['cover_address_state'] = $stateCode;
            }
            if (empty($this->editDetailsData['cover_address_zip'])) {
                $this->editDetailsData['cover_address_zip'] = $address->zip;
            }
            // Also set country if available
            if (empty($this->editDetailsData['cover_address_country'])) {
                $countryCode = $address->country?->code ?? $address->country?->name;
                if (empty($countryCode) && $address->country_id) {
                    $country = \Webkul\Support\Models\Country::find($address->country_id);
                    $countryCode = $country?->code ?? $country?->name;
                }
                // Default to US if state is in US but no country set
                if (empty($countryCode) && $address->state_id) {
                    $state = \Webkul\Support\Models\State::find($address->state_id);
                    if ($state) {
                        $country = \Webkul\Support\Models\Country::find($state->country_id);
                        $countryCode = $country?->code ?? 'US';
                    }
                }
                $this->editDetailsData['cover_address_country'] = $countryCode ?? 'US';
            }
        }

        // Get partner (designer/architect) information
        $partner = $this->record->partner;
        if ($partner) {
            if (empty($this->editDetailsData['cover_designer_company'])) {
                $this->editDetailsData['cover_designer_company'] = $partner->name;
            }
            if (empty($this->editDetailsData['cover_designer_phone'])) {
                $this->editDetailsData['cover_designer_phone'] = $partner->phone;
            }
            if (empty($this->editDetailsData['cover_designer_email'])) {
                $this->editDetailsData['cover_designer_email'] = $partner->email;
            }
        }
    }

    /**
     * AI Extract page details - universal method that works for all page types
     * Uses AI vision to analyze the page image and extract structured data
     */
    public function aiExtractPageDetails(): void
    {
        if (!$this->editingPageNumber || !$this->pdfDocument) {
            Notification::make()
                ->warning()
                ->title('No page selected')
                ->body('Please select a page to extract details from.')
                ->send();
            return;
        }

        $purpose = $this->getEditingPagePurpose();
        if (!$purpose) {
            Notification::make()
                ->warning()
                ->title('Page not classified')
                ->body('Please classify the page type first before extracting details.')
                ->send();
            return;
        }

        // Get the PdfPage model for this page
        $pdfPage = \App\Models\PdfPage::where('document_id', $this->pdfDocument->id)
            ->where('page_number', $this->editingPageNumber)
            ->first();

        if (!$pdfPage) {
            Notification::make()
                ->warning()
                ->title('Page not found')
                ->body('Could not find the page in the database.')
                ->send();
            return;
        }

        try {
            $aiService = app(\App\Services\AiPdfParsingService::class);
            $extractedData = [];

            // Call the appropriate parsing method based on page type
            switch ($purpose) {
                case 'cover':
                    $extractedData = $aiService->parseCoverPage($pdfPage);
                    $this->applyAiCoverPageData($extractedData);
                    break;

                case 'floor_plan':
                    $extractedData = $aiService->parseFloorPlan($pdfPage);
                    $this->applyAiFloorPlanData($extractedData);
                    break;

                case 'elevations':
                    $extractedData = $aiService->parseElevation($pdfPage);
                    $this->applyAiElevationData($extractedData);
                    break;

                case 'countertops':
                    // For countertops, we can use a generic extraction
                    $extractedData = $aiService->parseElevation($pdfPage);
                    $this->applyAiCountertopData($extractedData);
                    break;

                default:
                    Notification::make()
                        ->info()
                        ->title('AI extraction not available')
                        ->body("AI extraction is not yet available for '{$purpose}' pages.")
                        ->send();
                    return;
            }

            if (isset($extractedData['error'])) {
                Notification::make()
                    ->danger()
                    ->title('AI Extraction Failed')
                    ->body($extractedData['error'])
                    ->send();
                return;
            }

            // Log the activity
            $this->logPageActivity('ai_extract', [
                'page_number' => $this->editingPageNumber,
                'page_type' => $purpose,
                'extracted_fields' => array_keys($extractedData),
            ]);

            Notification::make()
                ->success()
                ->title('AI Extraction Complete')
                ->body('Details have been extracted. Review and save the changes.')
                ->send();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI extraction failed', [
                'error' => $e->getMessage(),
                'page' => $this->editingPageNumber,
            ]);

            Notification::make()
                ->danger()
                ->title('AI Extraction Error')
                ->body('An error occurred during extraction: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Apply AI-extracted cover page data to the form (only fills empty fields)
     */
    protected function applyAiCoverPageData(array $data): void
    {
        // Map AI response fields to form fields
        $mappings = [
            'project_name' => 'page_label', // Map project name to page label
            'designer' => 'cover_designer_company',
            'revision' => 'cover_revision_date',
            'drawing_set_title' => 'page_label',
        ];

        foreach ($mappings as $aiField => $formField) {
            if ($formField && !empty($data[$aiField]) && empty($this->editDetailsData[$formField])) {
                $this->editDetailsData[$formField] = $data[$aiField];
            }
        }

        // Handle address data from AI response
        if (!empty($data['address']) && is_array($data['address'])) {
            $address = $data['address'];
            if (!empty($address['street']) && empty($this->editDetailsData['cover_address_street'])) {
                $this->editDetailsData['cover_address_street'] = $address['street'];
            }
            if (!empty($address['city']) && empty($this->editDetailsData['cover_address_city'])) {
                $this->editDetailsData['cover_address_city'] = $address['city'];
            }
            if (!empty($address['state']) && empty($this->editDetailsData['cover_address_state'])) {
                $this->editDetailsData['cover_address_state'] = $address['state'];
            }
            if (!empty($address['zip']) && empty($this->editDetailsData['cover_address_zip'])) {
                $this->editDetailsData['cover_address_zip'] = $address['zip'];
            }
        }

        // Handle rooms mentioned
        if (!empty($data['rooms_mentioned']) && empty($this->editDetailsData['rooms_mentioned'])) {
            $this->editDetailsData['rooms_mentioned'] = $data['rooms_mentioned'];
        }

        // Handle scope summary as notes
        if (!empty($data['scope_summary']) && empty($this->editDetailsData['page_notes'])) {
            $this->editDetailsData['page_notes'] = $data['scope_summary'];
        }
    }

    /**
     * Apply AI-extracted floor plan data to the form
     */
    protected function applyAiFloorPlanData(array $data): void
    {
        // Extract room names for page label
        if (!empty($data['rooms']) && empty($this->editDetailsData['page_label'])) {
            $roomNames = array_column($data['rooms'], 'name');
            $this->editDetailsData['page_label'] = implode(' / ', $roomNames) . ' Floor Plan';
        }

        // Handle rooms on page
        if (!empty($data['rooms']) && empty($this->editDetailsData['rooms_on_page'])) {
            $this->editDetailsData['rooms_on_page'] = array_column($data['rooms'], 'name');
        }

        // Handle notes
        if (!empty($data['notes']) && empty($this->editDetailsData['page_notes'])) {
            $this->editDetailsData['page_notes'] = $data['notes'];
        }
    }

    /**
     * Apply AI-extracted elevation data to the form
     */
    protected function applyAiElevationData(array $data): void
    {
        // Map AI fields to form fields
        if (!empty($data['location_name']) && empty($this->editDetailsData['page_label'])) {
            $this->editDetailsData['page_label'] = $data['location_name'];
        }

        if (!empty($data['room_name']) && empty($this->editDetailsData['room_name'])) {
            $this->editDetailsData['room_name'] = $data['room_name'];
        }

        if (!empty($data['linear_feet']) && empty($this->editDetailsData['linear_feet'])) {
            $this->editDetailsData['linear_feet'] = $data['linear_feet'];
        }

        if (!empty($data['pricing_tier']) && empty($this->editDetailsData['pricing_tier'])) {
            $this->editDetailsData['pricing_tier'] = (string) $data['pricing_tier'];
        }

        // Check for hardware and material specs
        if (!empty($data['hardware'])) {
            $this->editDetailsData['has_hardware_schedule'] = true;
        }

        if (!empty($data['materials'])) {
            $this->editDetailsData['has_material_spec'] = true;
        }

        // Build notes from special features
        if (!empty($data['special_features']) && empty($this->editDetailsData['page_notes'])) {
            $this->editDetailsData['page_notes'] = 'Features: ' . implode(', ', $data['special_features']);
        }
    }

    /**
     * Apply AI-extracted countertop data to the form
     */
    protected function applyAiCountertopData(array $data): void
    {
        if (!empty($data['location_name']) && empty($this->editDetailsData['page_label'])) {
            $this->editDetailsData['page_label'] = $data['location_name'] . ' Countertops';
        }

        if (!empty($data['room_name']) && empty($this->editDetailsData['room_name'])) {
            $this->editDetailsData['room_name'] = $data['room_name'];
        }
    }

    /**
     * Log page activity for audit trail
     */
    protected function logPageActivity(string $action, array $data = []): void
    {
        if (!$this->pdfDocument) {
            return;
        }

        // Get or create the PDF page record
        $pdfPage = \App\Models\PdfPage::firstOrCreate(
            [
                'document_id' => $this->pdfDocument->id,
                'page_number' => $this->editingPageNumber ?? 0,
            ],
            [
                'project_id' => $this->record->id,
                'creator_id' => auth()->id(),
            ]
        );

        // Update the page metadata with activity log
        $metadata = $pdfPage->page_metadata ?? [];
        $metadata['activity_log'] = $metadata['activity_log'] ?? [];
        $metadata['activity_log'][] = [
            'action' => $action,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        $pdfPage->update(['page_metadata' => $metadata]);
    }

    /**
     * Open the chatter/activity panel for the current page
     */
    public function openPageChatter(): void
    {
        if (!$this->editingPageNumber || !$this->pdfDocument) {
            Notification::make()
                ->warning()
                ->title('No page selected')
                ->body('Please select a page to view activity.')
                ->send();
            return;
        }

        // Get or create the PDF page record
        $pdfPage = \App\Models\PdfPage::firstOrCreate(
            [
                'document_id' => $this->pdfDocument->id,
                'page_number' => $this->editingPageNumber,
            ],
            [
                'project_id' => $this->record->id,
                'creator_id' => auth()->id(),
            ]
        );

        // For now, show a notification with activity summary
        // In the future, this could open a dedicated chatter modal
        $metadata = $pdfPage->page_metadata ?? [];
        $activityLog = $metadata['activity_log'] ?? [];
        $activityCount = count($activityLog);

        if ($activityCount === 0) {
            Notification::make()
                ->info()
                ->title('No Activity Yet')
                ->body('This page has no recorded activity.')
                ->send();
        } else {
            $lastActivity = end($activityLog);
            Notification::make()
                ->info()
                ->title("Page Activity ({$activityCount} events)")
                ->body("Last: {$lastActivity['action']} by {$lastActivity['user_name']} at " .
                    \Carbon\Carbon::parse($lastActivity['timestamp'])->diffForHumans())
                ->send();
        }

        // TODO: In the future, dispatch to open a full chatter modal
        // $this->dispatch('open-modal', id: 'page-chatter-modal');
    }

    /**
     * Close the edit details modal
     */
    public function closeEditDetailsModal(): void
    {
        $this->dispatch('close-modal', id: 'edit-details-modal');
        $this->editingPageNumber = null;
        $this->editDetailsData = [];
    }

    /**
     * Get the current editing page's primary purpose
     */
    public function getEditingPagePurpose(): ?string
    {
        if (!$this->editingPageNumber) {
            return null;
        }

        $pageMetadata = $this->data['page_metadata'] ?? [];
        foreach ($pageMetadata as $page) {
            if (($page['page_number'] ?? null) == $this->editingPageNumber) {
                return $page['primary_purpose'] ?? null;
            }
        }
        return null;
    }

    /**
     * Submit the edit details form
     * Called from the modal form submission
     */
    public function submitEditDetails(): void
    {
        $this->saveEditDetails($this->editDetailsData);
        $this->closeEditDetailsModal();
    }

    /**
     * Save the edit details modal data back to page_metadata
     */
    protected function saveEditDetails(array $data): void
    {
        if (!$this->editingPageNumber) {
            return;
        }

        // Get current form data and work with a copy to ensure updates stick
        $formData = $this->data;
        $pageMetadata = $formData['page_metadata'] ?? [];
        $primaryPurpose = null;
        $targetIndex = null;

        // Find the target page index
        foreach ($pageMetadata as $index => $page) {
            if (($page['page_number'] ?? null) == $this->editingPageNumber) {
                $primaryPurpose = $page['primary_purpose'] ?? null;
                $targetIndex = $index;
                break;
            }
        }

        if ($targetIndex === null) {
            \Illuminate\Support\Facades\Log::warning('saveEditDetails: Page not found', [
                'editingPageNumber' => $this->editingPageNumber,
            ]);
            return;
        }

        // Apply updates based on page type
        switch ($primaryPurpose) {
            case 'cover':
                $formData['page_metadata'][$targetIndex]['cover_address_street'] = $data['cover_address_street'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_address_city'] = $data['cover_address_city'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_address_country'] = $data['cover_address_country'] ?? 'US';
                $formData['page_metadata'][$targetIndex]['cover_address_state'] = $data['cover_address_state'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_address_zip'] = $data['cover_address_zip'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_designer_company'] = $data['cover_designer_company'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_designer_drawn_by'] = $data['cover_designer_drawn_by'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_designer_approved_by'] = $data['cover_designer_approved_by'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_designer_phone'] = $data['cover_designer_phone'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_designer_email'] = $data['cover_designer_email'] ?? null;
                $formData['page_metadata'][$targetIndex]['drawing_number'] = $data['drawing_number'] ?? null;
                $formData['page_metadata'][$targetIndex]['cover_revision_date'] = $data['cover_revision_date'] ?? null;
                $formData['page_metadata'][$targetIndex]['page_label'] = $data['page_label'] ?? null;
                $formData['page_metadata'][$targetIndex]['scope_estimate'] = $data['scope_estimate'] ?? [];
                $formData['page_metadata'][$targetIndex]['rooms_mentioned'] = $data['rooms_mentioned'] ?? [];
                $formData['page_metadata'][$targetIndex]['page_notes'] = $data['page_notes'] ?? null;
                break;

            case 'floor_plan':
                $formData['page_metadata'][$targetIndex]['page_label'] = $data['page_label'] ?? null;
                $formData['page_metadata'][$targetIndex]['rooms_on_page'] = $data['rooms_on_page'] ?? [];
                $formData['page_metadata'][$targetIndex]['page_notes'] = $data['page_notes'] ?? null;
                break;

            case 'elevations':
                $formData['page_metadata'][$targetIndex]['page_label'] = $data['page_label'] ?? null;
                $formData['page_metadata'][$targetIndex]['linear_feet'] = $data['linear_feet'] ?? null;
                $formData['page_metadata'][$targetIndex]['pricing_tier'] = $data['pricing_tier'] ?? null;
                $formData['page_metadata'][$targetIndex]['room_name'] = $data['room_name'] ?? null;
                $formData['page_metadata'][$targetIndex]['has_hardware_schedule'] = $data['has_hardware_schedule'] ?? null;
                $formData['page_metadata'][$targetIndex]['has_material_spec'] = $data['has_material_spec'] ?? null;
                $formData['page_metadata'][$targetIndex]['page_notes'] = $data['page_notes'] ?? null;
                break;

            case 'countertops':
                $formData['page_metadata'][$targetIndex]['page_label'] = $data['page_label'] ?? null;
                $formData['page_metadata'][$targetIndex]['countertop_features'] = $data['countertop_features'] ?? [];
                $formData['page_metadata'][$targetIndex]['page_notes'] = $data['page_notes'] ?? null;
                break;

            default:
                $formData['page_metadata'][$targetIndex]['page_label'] = $data['page_label'] ?? null;
                $formData['page_metadata'][$targetIndex]['page_notes'] = $data['page_notes'] ?? null;
        }

        // Update $this->data with the modified formData
        $this->data = $formData;

        // Save the draft with updated data directly
        // NOTE: We intentionally do NOT call $this->form->fill() here because it would
        // regenerate UUID keys for the repeater items, causing the page cards to disappear
        // and the component to lose its state. The draft is saved from $formData directly.
        $this->saveDraftWithData($formData);

        // Reset the editing state
        $this->editingPageNumber = null;
        $this->editDetailsData = [];

        Notification::make()
            ->success()
            ->title('Details saved')
            ->body('Page details have been updated.')
            ->send();
    }

    /**
     * Save draft state for the PDF review wizard
     *
     * @return void
     */
    public function saveDraft(): void
    {
        $formData = $this->form->getState();
        $this->saveDraftWithData($formData, true);
    }

    /**
     * Save draft with explicit data (bypasses form state sync issues)
     *
     * @param array $formData The data to save
     * @param bool $showNotification Whether to show notification
     * @return void
     */
    protected function saveDraftWithData(array $formData, bool $showNotification = false): void
    {
        $sessionId = 'pdf-review-' . $this->record->id . '-' . $this->pdf;

        if ($this->draft) {
            $this->draft->update([
                'form_data' => $formData,
                'expires_at' => now()->addDays(7),
            ]);
            $this->draft->refresh(); // Refresh to get the saved data back
        } else {
            $this->draft = ProjectDraft::create([
                'user_id' => auth()->id(),
                'session_id' => $sessionId,
                'current_step' => 'pdf-review',
                'form_data' => $formData,
                'expires_at' => now()->addDays(7),
            ]);
        }

        $this->lastSavedAt = 'just now';

        if ($showNotification) {
            Notification::make()
                ->success()
                ->title('Draft Saved')
                ->body('Your progress has been saved.')
                ->duration(2000)
                ->send();
        }
    }

    /**
     * Discard the current draft
     *
     * @return void
     */
    public function discardDraft(): void
    {
        if ($this->draft) {
            $this->draft->delete();
            $this->draft = null;
        }

        // Reload form with fresh data
        $existingRooms = $this->buildExistingRoomsData();
        $coverPageData = $this->buildCoverPageData();

        $pageMetadata = [];
        for ($i = 1; $i <= $this->getTotalPages(); $i++) {
            $pageData = [
                'page_number' => $i,
                'rooms'       => [['room_number' => '', 'room_type' => '', 'room_id' => null]],
                'detail_number' => '',
                'notes'         => '',
            ];
            $pageData = array_merge($pageData, $coverPageData);
            $pageMetadata[] = $pageData;
        }

        $this->form->fill([
            'page_metadata' => $pageMetadata,
            'rooms'         => $existingRooms,
        ]);

        Notification::make()
            ->warning()
            ->title('Draft Discarded')
            ->body('Started fresh with project data.')
            ->send();
    }

    /**
     * Save full project hierarchy to database (without creating sales order)
     * Project → Room → Room Location → Cabinet Run → Cabinet → Section → Components
     *
     * @return void
     */
    public function saveRoomsAndCabinets(): void
    {
        $data = $this->form->getState();

        if (empty($data['rooms'])) {
            Notification::make()
                ->warning()
                ->title('No Rooms to Save')
                ->body('Add at least one room before saving.')
                ->send();
            return;
        }

        $stats = [
            'rooms' => 0,
            'locations' => 0,
            'runs' => 0,
            'cabinets' => 0,
            'sections' => 0,
            'doors' => 0,
            'drawers' => 0,
            'pullouts' => 0,
            'shelves' => 0,
        ];

        foreach ($data['rooms'] as $roomData) {
            // LEVEL 1: Save Room
            $room = $this->saveRoom($roomData, $stats);

            // LEVEL 2: Save Room Locations
            foreach ($roomData['locations'] ?? [] as $locationData) {
                $location = $this->saveLocation($room, $locationData, $stats);

                // LEVEL 3: Save Cabinet Runs
                foreach ($locationData['cabinet_runs'] ?? [] as $runData) {
                    if (empty($runData['run_name']) && empty($runData['linear_feet'])) {
                        continue;
                    }

                    $run = $this->saveCabinetRun($location, $runData, $stats);

                    // LEVEL 4: Save Cabinets
                    foreach ($runData['cabinets'] ?? [] as $cabinetData) {
                        if (empty($cabinetData['cabinet_number']) && empty($cabinetData['width_inches'])) {
                            continue;
                        }

                        $cabinet = $this->saveCabinet($run, $room, $cabinetData, $stats);

                        // LEVEL 5: Save Sections
                        foreach ($cabinetData['sections'] ?? [] as $sectionData) {
                            if (empty($sectionData['section_name']) && empty($sectionData['section_type'])) {
                                continue;
                            }

                            $section = $this->saveSection($cabinet, $sectionData, $stats);

                            // LEVEL 6: Save Components
                            $this->saveDoors($cabinet, $section, $sectionData['doors'] ?? [], $stats);
                            $this->saveDrawers($cabinet, $section, $sectionData['drawers'] ?? [], $stats);
                            $this->savePullouts($cabinet, $section, $sectionData['pullouts'] ?? [], $stats);
                            $this->saveShelves($cabinet, $section, $sectionData['shelves'] ?? [], $stats);
                        }
                    }
                }
            }
        }

        // Update project total linear feet
        $totalLf = CabinetRun::whereHas('roomLocation.room', fn ($q) => $q->where('project_id', $this->record->id))
            ->sum('total_linear_feet');

        $this->record->update([
            'estimated_linear_feet' => $totalLf,
        ]);

        // Build summary message
        $summary = [];
        if ($stats['rooms'] > 0) $summary[] = "{$stats['rooms']} room(s)";
        if ($stats['locations'] > 0) $summary[] = "{$stats['locations']} location(s)";
        if ($stats['runs'] > 0) $summary[] = "{$stats['runs']} cabinet run(s)";
        if ($stats['cabinets'] > 0) $summary[] = "{$stats['cabinets']} cabinet(s)";
        if ($stats['sections'] > 0) $summary[] = "{$stats['sections']} section(s)";
        $componentCount = $stats['doors'] + $stats['drawers'] + $stats['pullouts'] + $stats['shelves'];
        if ($componentCount > 0) $summary[] = "{$componentCount} component(s)";

        Notification::make()
            ->success()
            ->title('Full Hierarchy Saved')
            ->body("Saved " . implode(', ', $summary) . ". Total: {$totalLf} LF")
            ->send();
    }

    /**
     * Save cover page extracted data to Project and related models
     * Called when user confirms cover page data in Step 1
     * Now with conflict detection - shows merge modal if existing data would be overwritten
     */
    public function saveCoverPageData(): void
    {
        $data = $this->form->getState();

        // Find the cover page data (first page with primary_purpose = 'cover')
        $coverPageData = collect($data['page_metadata'] ?? [])
            ->firstWhere('primary_purpose', 'cover');

        if (!$coverPageData) {
            Notification::make()
                ->warning()
                ->title('No Cover Page')
                ->body('No page has been marked as a cover page.')
                ->send();
            return;
        }

        // Detect conflicts with existing data
        $conflicts = $this->detectDataConflicts($coverPageData);

        if (!empty($conflicts)) {
            // Store pending data and conflicts for the modal
            $this->pendingCoverPageData = $coverPageData;
            $this->dataConflicts = $conflicts;

            // Pre-select all fields by default
            $this->selectedFields = array_keys($conflicts);

            // Open the conflict resolution modal
            $this->dispatch('open-modal', id: 'cover-page-conflict-modal');
            return;
        }

        // No conflicts - proceed with save
        $this->executeCoverPageSave($coverPageData);
    }

    /**
     * Detect conflicts between extracted cover page data and existing project data
     * Returns array of conflicts with current vs extracted values
     */
    protected function detectDataConflicts(array $coverPageData): array
    {
        $conflicts = [];

        // Get current project address
        $currentAddress = $this->record->addresses()->where('is_primary', true)->first()
            ?? $this->record->addresses()->first();

        // Get current designer
        $currentDesigner = $this->record->designer;

        // Check Address - Street
        if (!empty($coverPageData['cover_address_street'])) {
            $currentStreet = $currentAddress?->street1;
            if (!empty($currentStreet) && $currentStreet !== $coverPageData['cover_address_street']) {
                $conflicts['address_street'] = [
                    'label' => 'Street Address',
                    'current' => $currentStreet,
                    'extracted' => $coverPageData['cover_address_street'],
                    'icon' => 'heroicon-o-map-pin',
                ];
            }
        }

        // Check Address - City
        if (!empty($coverPageData['cover_address_city'])) {
            $currentCity = $currentAddress?->city;
            if (!empty($currentCity) && $currentCity !== $coverPageData['cover_address_city']) {
                $conflicts['address_city'] = [
                    'label' => 'City',
                    'current' => $currentCity,
                    'extracted' => $coverPageData['cover_address_city'],
                    'icon' => 'heroicon-o-building-office-2',
                ];
            }
        }

        // Check Address - State
        if (!empty($coverPageData['cover_address_state'])) {
            $currentState = $currentAddress?->state?->code ?? $currentAddress?->state?->name;
            if (!empty($currentState) && strtoupper($currentState) !== strtoupper($coverPageData['cover_address_state'])) {
                $conflicts['address_state'] = [
                    'label' => 'State',
                    'current' => $currentState,
                    'extracted' => $coverPageData['cover_address_state'],
                    'icon' => 'heroicon-o-globe-americas',
                ];
            }
        }

        // Check Designer Company
        if (!empty($coverPageData['cover_designer_company'])) {
            $currentDesignerName = $currentDesigner?->name;
            if (!empty($currentDesignerName) && $currentDesignerName !== $coverPageData['cover_designer_company']) {
                $conflicts['designer'] = [
                    'label' => 'Designer/Woodworker',
                    'current' => $currentDesignerName,
                    'extracted' => $coverPageData['cover_designer_company'],
                    'icon' => 'heroicon-o-user-circle',
                ];
            }
        }

        // Check Revision Number
        if (!empty($coverPageData['drawing_number'])) {
            $currentRevision = $this->record->design_revision_number;
            if (!empty($currentRevision) && $currentRevision !== $coverPageData['drawing_number']) {
                $conflicts['revision'] = [
                    'label' => 'Revision Number',
                    'current' => $currentRevision,
                    'extracted' => $coverPageData['drawing_number'],
                    'icon' => 'heroicon-o-document-text',
                ];
            }
        }

        // Check Design Notes
        $newNotes = $this->buildDesignNotes($coverPageData);
        if (!empty($newNotes)) {
            $currentNotes = $this->record->design_notes;
            if (!empty($currentNotes) && $currentNotes !== $newNotes) {
                $conflicts['design_notes'] = [
                    'label' => 'Design Notes',
                    'current' => $currentNotes,
                    'extracted' => $newNotes,
                    'icon' => 'heroicon-o-pencil-square',
                ];
            }
        }

        // Check Description
        if (!empty($coverPageData['page_label'])) {
            $currentDescription = $this->record->description;
            if (!empty($currentDescription) && $currentDescription !== $coverPageData['page_label']) {
                $conflicts['description'] = [
                    'label' => 'Project Description',
                    'current' => $currentDescription,
                    'extracted' => $coverPageData['page_label'],
                    'icon' => 'heroicon-o-document',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Build design notes string from cover page data
     */
    protected function buildDesignNotes(array $coverPageData): string
    {
        $notes = [];
        if (!empty($coverPageData['cover_designer_drawn_by'])) {
            $notes[] = "Drawn by: {$coverPageData['cover_designer_drawn_by']}";
        }
        if (!empty($coverPageData['cover_designer_approved_by'])) {
            $notes[] = "Approved by: {$coverPageData['cover_designer_approved_by']}";
        }
        if (!empty($coverPageData['cover_revision_date'])) {
            $notes[] = "Revision date: {$coverPageData['cover_revision_date']}";
        }
        return implode("\n", $notes);
    }

    /**
     * Toggle a field selection in the conflict resolution modal
     */
    public function toggleFieldSelection(string $field): void
    {
        if (in_array($field, $this->selectedFields)) {
            $this->selectedFields = array_values(array_diff($this->selectedFields, [$field]));
        } else {
            $this->selectedFields[] = $field;
        }
    }

    /**
     * Select all fields in conflict resolution
     */
    public function selectAllFields(): void
    {
        $this->selectedFields = array_keys($this->dataConflicts);
    }

    /**
     * Deselect all fields (keep current values)
     */
    public function deselectAllFields(): void
    {
        $this->selectedFields = [];
    }

    /**
     * Confirm the merge with selected fields
     */
    public function confirmMerge(): void
    {
        if (empty($this->pendingCoverPageData)) {
            return;
        }

        // Build a modified cover page data that only includes selected fields
        $mergedData = $this->pendingCoverPageData;

        // If a conflict field is NOT selected, remove that data so it won't be saved
        foreach ($this->dataConflicts as $field => $conflict) {
            if (!in_array($field, $this->selectedFields)) {
                // Don't overwrite this field
                switch ($field) {
                    case 'address_street':
                        $mergedData['cover_address_street'] = null;
                        break;
                    case 'address_city':
                        $mergedData['cover_address_city'] = null;
                        break;
                    case 'address_state':
                        $mergedData['cover_address_state'] = null;
                        break;
                    case 'designer':
                        $mergedData['cover_designer_company'] = null;
                        break;
                    case 'revision':
                        $mergedData['drawing_number'] = null;
                        break;
                    case 'design_notes':
                        $mergedData['cover_designer_drawn_by'] = null;
                        $mergedData['cover_designer_approved_by'] = null;
                        $mergedData['cover_revision_date'] = null;
                        break;
                    case 'description':
                        $mergedData['page_label'] = null;
                        break;
                }
            }
        }

        // Close modal and execute save
        $this->dispatch('close-modal', id: 'cover-page-conflict-modal');
        $this->executeCoverPageSave($mergedData);

        // Clear pending data
        $this->pendingCoverPageData = null;
        $this->dataConflicts = [];
        $this->selectedFields = [];
    }

    /**
     * Cancel the merge and keep all existing data
     */
    public function cancelMerge(): void
    {
        $this->dispatch('close-modal', id: 'cover-page-conflict-modal');
        $this->pendingCoverPageData = null;
        $this->dataConflicts = [];
        $this->selectedFields = [];

        Notification::make()
            ->info()
            ->title('Merge Cancelled')
            ->body('Existing project data was preserved.')
            ->send();
    }

    /**
     * Execute the actual save of cover page data
     * Called directly when no conflicts, or after conflict resolution
     */
    protected function executeCoverPageSave(array $coverPageData): void
    {
        $updates = [];
        $saved = [];

        // Update project revision info
        if (!empty($coverPageData['drawing_number'])) {
            $updates['design_revision_number'] = $coverPageData['drawing_number'];
            $saved[] = 'Revision';
        }

        // Update project description if provided
        if (!empty($coverPageData['page_label'])) {
            $updates['description'] = $coverPageData['page_label'];
            $saved[] = 'Description';
        }

        // Build design notes from designer info
        $newNotes = $this->buildDesignNotes($coverPageData);
        if (!empty($newNotes)) {
            $updates['design_notes'] = $newNotes;
            $saved[] = 'Design notes';
        }

        // Apply project updates
        if (!empty($updates)) {
            $this->record->update($updates);
        }

        // Save or update project address
        $hasAddress = !empty($coverPageData['cover_address_street'])
                   || !empty($coverPageData['cover_address_city'])
                   || !empty($coverPageData['cover_address_state']);

        if ($hasAddress) {
            // Look up country ID (default to US if not specified)
            $countryCode = $coverPageData['cover_address_country'] ?? 'US';
            $country = \Webkul\Support\Models\Country::where('code', $countryCode)->first();
            $countryId = $country?->id ?? 233; // Default to USA (ID 233)

            // Look up state ID
            $stateId = null;
            if (!empty($coverPageData['cover_address_state'])) {
                $state = \Webkul\Support\Models\State::where('code', $coverPageData['cover_address_state'])
                    ->orWhere('name', $coverPageData['cover_address_state'])
                    ->first();
                $stateId = $state?->id;
            }

            // Find or create address
            $address = $this->record->addresses()->where('is_primary', true)->first()
                    ?? $this->record->addresses()->first();

            $addressData = array_filter([
                'street1' => $coverPageData['cover_address_street'] ?? null,
                'city' => $coverPageData['cover_address_city'] ?? null,
                'country_id' => $countryId,
                'state_id' => $stateId,
                'zip' => $coverPageData['cover_address_zip'] ?? null,
                'is_primary' => true,
            ], fn($v) => $v !== null);

            if ($address && !empty($addressData)) {
                $address->update($addressData);
                $saved[] = 'Address';
            } elseif (!$address && !empty($addressData)) {
                $this->record->addresses()->create(array_merge($addressData, [
                    'address_type' => 'project',
                    // country_id is already in $addressData from lookup above
                ]));
                $saved[] = 'Address';
            }
        }

        // Save designer/partner info
        if (!empty($coverPageData['cover_designer_company'])) {
            // Look for existing partner or create new
            $partner = \Webkul\Partner\Models\Partner::firstOrCreate(
                ['name' => $coverPageData['cover_designer_company']],
                [
                    'type' => 'company',
                    'sub_type' => 'designer',
                    'phone' => $coverPageData['cover_designer_phone'] ?? null,
                    'email' => $coverPageData['cover_designer_email'] ?? null,
                    'creator_id' => auth()->id(),
                ]
            );

            // Link as designer for this project if not already linked
            if (!$this->record->designer_id || $this->record->designer_id !== $partner->id) {
                $this->record->update(['designer_id' => $partner->id]);
                $saved[] = 'Designer';
            }
        }

        // Store revision info and scope estimates in PdfDocument metadata with history
        $existingMetadata = $this->pdfDocument->metadata;
        if (!is_array($existingMetadata)) {
            $existingMetadata = [];
        }

        // Build current revision record
        $currentRevision = [
            'revision_number' => $coverPageData['drawing_number'] ?? null,
            'revision_date' => $coverPageData['cover_revision_date'] ?? null,
            'drawn_by' => $coverPageData['cover_designer_drawn_by'] ?? null,
            'approved_by' => $coverPageData['cover_designer_approved_by'] ?? null,
            'designer_company' => $coverPageData['cover_designer_company'] ?? null,
            'scope_estimate' => $coverPageData['scope_estimate'] ?? [],
            'rooms_mentioned' => $coverPageData['rooms_mentioned'] ?? [],
            'extracted_at' => now()->toIso8601String(),
            'extracted_by' => auth()->user()?->name ?? 'System',
        ];

        // Get existing revision history or initialize
        $revisionHistory = $existingMetadata['revision_history'] ?? [];

        // Only add to history if there's actual revision data
        if (!empty($currentRevision['revision_number']) || !empty($currentRevision['scope_estimate'])) {
            // Add to beginning of history array (most recent first)
            array_unshift($revisionHistory, $currentRevision);

            // Keep only the last 10 revisions to prevent bloat
            $revisionHistory = array_slice($revisionHistory, 0, 10);

            $this->pdfDocument->update([
                'metadata' => array_merge($existingMetadata, [
                    'current_revision' => $currentRevision,
                    'revision_history' => $revisionHistory,
                    'scope_estimate' => $coverPageData['scope_estimate'] ?? [],
                    'rooms_mentioned' => $coverPageData['rooms_mentioned'] ?? [],
                ]),
            ]);
            $saved[] = 'Revision history';
        }

        // Post to Chatter with revision details
        if (!empty($saved)) {
            $this->postRevisionToChatter($coverPageData, $saved);

            Notification::make()
                ->success()
                ->title('Cover Page Data Saved')
                ->body('Updated: ' . implode(', ', $saved))
                ->send();
        } else {
            Notification::make()
                ->info()
                ->title('No Changes')
                ->body('No new data to save from cover page.')
                ->send();
        }
    }

    /**
     * Post revision details to Chatter activity feed
     */
    protected function postRevisionToChatter(array $coverPageData, array $savedItems): void
    {
        $revisionNum = $coverPageData['drawing_number'] ?? 'Unknown';
        $revisionDate = $coverPageData['cover_revision_date'] ?? null;

        // Build a detailed message for the Chatter feed
        $messageParts = [];
        $messageParts[] = "📄 **Cover Page Data Extracted**";
        $messageParts[] = "";

        if (!empty($coverPageData['drawing_number'])) {
            $messageParts[] = "**Revision:** {$coverPageData['drawing_number']}";
        }
        if (!empty($coverPageData['cover_revision_date'])) {
            $messageParts[] = "**Date:** {$coverPageData['cover_revision_date']}";
        }
        if (!empty($coverPageData['cover_designer_company'])) {
            $messageParts[] = "**Designer:** {$coverPageData['cover_designer_company']}";
        }
        if (!empty($coverPageData['cover_designer_drawn_by'])) {
            $messageParts[] = "**Drawn by:** {$coverPageData['cover_designer_drawn_by']}";
        }

        // Add scope estimate summary
        if (!empty($coverPageData['scope_estimate']) && is_array($coverPageData['scope_estimate'])) {
            $messageParts[] = "";
            $messageParts[] = "**Scope Estimate:**";
            foreach ($coverPageData['scope_estimate'] as $item) {
                $qty = $item['quantity'] ?? '?';
                $unit = $item['unit'] ?? '';
                $type = $item['item_type'] ?? 'Unknown';
                $messageParts[] = "• {$qty} {$unit} {$type}";
            }
        }

        // Add rooms mentioned
        if (!empty($coverPageData['rooms_mentioned']) && is_array($coverPageData['rooms_mentioned'])) {
            $messageParts[] = "";
            $messageParts[] = "**Rooms:** " . implode(', ', $coverPageData['rooms_mentioned']);
        }

        $messageParts[] = "";
        $messageParts[] = "_Saved: " . implode(', ', $savedItems) . "_";

        $message = implode("\n", $messageParts);

        // Post to project's Chatter
        try {
            $this->record->addMessage([
                'type' => 'comment',
                'body' => $message,
            ]);
        } catch (\Exception $e) {
            // Log but don't fail - Chatter is a nice-to-have
            \Log::warning('Failed to post revision to Chatter: ' . $e->getMessage());
        }
    }

    /**
     * Save a room record
     */
    protected function saveRoom(array $roomData, array &$stats): Room
    {
        $room = null;
        if (! empty($roomData['room_id'])) {
            $room = Room::find($roomData['room_id']);
        }

        if ($room) {
            $room->update([
                'name' => $roomData['room_name'],
                'room_type' => $roomData['room_type'] ?? $room->room_type,
            ]);
        } else {
            $room = Room::create([
                'project_id' => $this->record->id,
                'name' => $roomData['room_name'],
                'room_type' => $roomData['room_type'] ?? 'other',
                'creator_id' => auth()->id(),
            ]);
            $stats['rooms']++;
        }

        return $room;
    }

    /**
     * Save a room location record
     */
    protected function saveLocation(Room $room, array $locationData, array &$stats): RoomLocation
    {
        $location = null;
        if (! empty($locationData['location_id'])) {
            $location = RoomLocation::find($locationData['location_id']);
        }

        if ($location) {
            $location->update([
                'name' => $locationData['location_name'] ?? 'Main',
                'location_type' => $locationData['location_type'] ?? 'wall',
            ]);
        } else {
            $location = RoomLocation::create([
                'room_id' => $room->id,
                'name' => $locationData['location_name'] ?? 'Main',
                'location_type' => $locationData['location_type'] ?? 'wall',
                'sequence' => RoomLocation::where('room_id', $room->id)->count() + 1,
                'creator_id' => auth()->id(),
            ]);
            $stats['locations']++;
        }

        return $location;
    }

    /**
     * Save a cabinet run record
     */
    protected function saveCabinetRun(RoomLocation $location, array $runData, array &$stats): CabinetRun
    {
        $run = null;
        if (! empty($runData['cabinet_run_id'])) {
            $run = CabinetRun::find($runData['cabinet_run_id']);
        }

        if ($run) {
            $run->update([
                'name' => $runData['run_name'],
                'run_type' => $runData['run_type'] ?? 'base',
                'cabinet_level' => $runData['cabinet_level'] ?? '2',
                'total_linear_feet' => (float) ($runData['linear_feet'] ?? 0),
                'notes' => $runData['run_notes'] ?? null,
            ]);
        } else {
            $run = CabinetRun::create([
                'room_location_id' => $location->id,
                'name' => $runData['run_name'],
                'run_type' => $runData['run_type'] ?? 'base',
                'cabinet_level' => $runData['cabinet_level'] ?? '2',
                'total_linear_feet' => (float) ($runData['linear_feet'] ?? 0),
                'notes' => $runData['run_notes'] ?? null,
                'creator_id' => auth()->id(),
            ]);
            $stats['runs']++;
        }

        return $run;
    }

    /**
     * Save a cabinet record
     */
    protected function saveCabinet(CabinetRun $run, Room $room, array $cabinetData, array &$stats): Cabinet
    {
        $cabinet = null;
        if (! empty($cabinetData['cabinet_id'])) {
            $cabinet = Cabinet::find($cabinetData['cabinet_id']);
        }

        $data = [
            'cabinet_number' => $cabinetData['cabinet_number'] ?? null,
            'width_inches' => $cabinetData['width_inches'] ?? null,
            'height_inches' => $cabinetData['height_inches'] ?? null,
            'depth_inches' => $cabinetData['depth_inches'] ?? null,
            'shop_notes' => $cabinetData['cabinet_notes'] ?? null,
        ];

        if ($cabinet) {
            $cabinet->update($data);
        } else {
            $cabinet = Cabinet::create(array_merge($data, [
                'project_id' => $this->record->id,
                'room_id' => $room->id,
                'cabinet_run_id' => $run->id,
                'position_in_run' => Cabinet::where('cabinet_run_id', $run->id)->count() + 1,
                'quantity' => 1,
                'creator_id' => auth()->id(),
            ]));
            $stats['cabinets']++;
        }

        return $cabinet;
    }

    /**
     * Save a cabinet section record
     */
    protected function saveSection(Cabinet $cabinet, array $sectionData, array &$stats): object
    {
        $section = null;
        if (! empty($sectionData['section_id'])) {
            $section = \DB::table('projects_cabinet_sections')->where('id', $sectionData['section_id'])->first();
        }

        $data = [
            'name' => $sectionData['section_name'] ?? '',
            'section_type' => $sectionData['section_type'] ?? 'door',
            'width_inches' => $sectionData['section_width'] ?? null,
            'height_inches' => $sectionData['section_height'] ?? null,
            'updated_at' => now(),
        ];

        if ($section) {
            \DB::table('projects_cabinet_sections')
                ->where('id', $section->id)
                ->update($data);
            return (object) array_merge((array) $section, $data);
        } else {
            $sectionId = \DB::table('projects_cabinet_sections')->insertGetId(array_merge($data, [
                'cabinet_id' => $cabinet->id,
                'section_number' => \DB::table('projects_cabinet_sections')->where('cabinet_id', $cabinet->id)->count() + 1,
                'sort_order' => \DB::table('projects_cabinet_sections')->where('cabinet_id', $cabinet->id)->count(),
                'created_at' => now(),
            ]));
            $stats['sections']++;
            return (object) array_merge($data, ['id' => $sectionId]);
        }
    }

    /**
     * Save door components
     */
    protected function saveDoors(Cabinet $cabinet, object $section, array $doorsData, array &$stats): void
    {
        foreach ($doorsData as $doorData) {
            if (empty($doorData['door_name']) && empty($doorData['door_width'])) {
                continue;
            }

            $door = null;
            $doorId = null;
            if (! empty($doorData['door_id'])) {
                $door = \DB::table('projects_doors')->where('id', $doorData['door_id'])->first();
                $doorId = $door?->id;
            }

            $data = [
                'door_name' => $doorData['door_name'] ?? null,
                'width_inches' => $doorData['door_width'] ?? null,
                'height_inches' => $doorData['door_height'] ?? null,
                'hinge_side' => $doorData['hinge_side'] ?? null,
                'has_glass' => $doorData['has_glass'] ?? false,
                'updated_at' => now(),
            ];

            if ($door) {
                \DB::table('projects_doors')->where('id', $door->id)->update($data);
            } else {
                $doorId = \DB::table('projects_doors')->insertGetId(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'door_number' => \DB::table('projects_doors')->where('cabinet_id', $cabinet->id)->count() + 1,
                    'sort_order' => \DB::table('projects_doors')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['doors']++;
            }

            // Save products for this door
            if ($doorId && !empty($doorData['products'])) {
                $this->saveComponentProducts('door_id', $doorId, $doorData['products']);
            }
        }
    }

    /**
     * Save drawer components
     */
    protected function saveDrawers(Cabinet $cabinet, object $section, array $drawersData, array &$stats): void
    {
        foreach ($drawersData as $drawerData) {
            if (empty($drawerData['drawer_name']) && empty($drawerData['front_width'])) {
                continue;
            }

            $drawer = null;
            $drawerId = null;
            if (! empty($drawerData['drawer_id'])) {
                $drawer = \DB::table('projects_drawers')->where('id', $drawerData['drawer_id'])->first();
                $drawerId = $drawer?->id;
            }

            $data = [
                'drawer_name' => $drawerData['drawer_name'] ?? null,
                'front_width_inches' => $drawerData['front_width'] ?? null,
                'front_height_inches' => $drawerData['front_height'] ?? null,
                'box_depth_inches' => $drawerData['box_depth'] ?? null,
                'slide_type' => $drawerData['slide_type'] ?? null,
                'updated_at' => now(),
            ];

            if ($drawer) {
                \DB::table('projects_drawers')->where('id', $drawer->id)->update($data);
            } else {
                $drawerId = \DB::table('projects_drawers')->insertGetId(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'drawer_number' => \DB::table('projects_drawers')->where('cabinet_id', $cabinet->id)->count() + 1,
                    'sort_order' => \DB::table('projects_drawers')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['drawers']++;
            }

            // Save products for this drawer
            if ($drawerId && !empty($drawerData['products'])) {
                $this->saveComponentProducts('drawer_id', $drawerId, $drawerData['products']);
            }
        }
    }

    /**
     * Save pullout components
     */
    protected function savePullouts(Cabinet $cabinet, object $section, array $pulloutsData, array &$stats): void
    {
        foreach ($pulloutsData as $pulloutData) {
            if (empty($pulloutData['pullout_name']) && empty($pulloutData['pullout_type'])) {
                continue;
            }

            $pullout = null;
            $pulloutId = null;
            if (! empty($pulloutData['pullout_id'])) {
                $pullout = \DB::table('projects_pullouts')->where('id', $pulloutData['pullout_id'])->first();
                $pulloutId = $pullout?->id;
            }

            $data = [
                'pullout_name' => $pulloutData['pullout_name'] ?? null,
                'pullout_type' => $pulloutData['pullout_type'] ?? null,
                'width_inches' => $pulloutData['pullout_width'] ?? null,
                'depth_inches' => $pulloutData['pullout_depth'] ?? null,
                'updated_at' => now(),
            ];

            if ($pullout) {
                \DB::table('projects_pullouts')->where('id', $pullout->id)->update($data);
            } else {
                $pulloutId = \DB::table('projects_pullouts')->insertGetId(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'sort_order' => \DB::table('projects_pullouts')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['pullouts']++;
            }

            // Save products for this pullout
            if ($pulloutId && !empty($pulloutData['products'])) {
                $this->saveComponentProducts('pullout_id', $pulloutId, $pulloutData['products']);
            }
        }
    }

    /**
     * Save shelf components
     */
    protected function saveShelves(Cabinet $cabinet, object $section, array $shelvesData, array &$stats): void
    {
        foreach ($shelvesData as $shelfData) {
            if (empty($shelfData['shelf_name']) && empty($shelfData['shelf_type'])) {
                continue;
            }

            $shelf = null;
            $shelfId = null;
            if (! empty($shelfData['shelf_id'])) {
                $shelf = \DB::table('projects_shelves')->where('id', $shelfData['shelf_id'])->first();
                $shelfId = $shelf?->id;
            }

            $data = [
                'shelf_name' => $shelfData['shelf_name'] ?? null,
                'shelf_type' => $shelfData['shelf_type'] ?? null,
                'width_inches' => $shelfData['shelf_width'] ?? null,
                'depth_inches' => $shelfData['shelf_depth'] ?? null,
                'quantity' => $shelfData['shelf_quantity'] ?? 1,
                'updated_at' => now(),
            ];

            if ($shelf) {
                \DB::table('projects_shelves')->where('id', $shelf->id)->update($data);
            } else {
                $shelfId = \DB::table('projects_shelves')->insertGetId(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'sort_order' => \DB::table('projects_shelves')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['shelves']++;
            }

            // Save products for this shelf
            if ($shelfId && !empty($shelfData['products'])) {
                $this->saveComponentProducts('shelf_id', $shelfId, $shelfData['products']);
            }
        }
    }

    /**
     * Save products/hardware for a component (door, drawer, shelf, pullout)
     *
     * @param string $foreignKeyColumn The column name (door_id, drawer_id, shelf_id, pullout_id)
     * @param int $componentId The ID of the component
     * @param array $products Array of product data from the repeater
     */
    protected function saveComponentProducts(string $foreignKeyColumn, int $componentId, array $products): void
    {
        // Delete existing products for this component
        \DB::table('hardware_requirements')
            ->where($foreignKeyColumn, $componentId)
            ->delete();

        // Insert new products
        foreach ($products as $productData) {
            if (empty($productData['product_id'])) {
                continue;
            }

            \DB::table('hardware_requirements')->insert([
                $foreignKeyColumn => $componentId,
                'product_id' => $productData['product_id'],
                'quantity_required' => $productData['quantity'] ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Save page metadata to the pdf_pages table
     *
     * @param array $pageMetadata
     * @return void
     */
    protected function savePageMetadata(array $pageMetadata): void
    {
        foreach ($pageMetadata as $pageData) {
            $pageNumber = $pageData['page_number'] ?? null;
            if (!$pageNumber) {
                continue;
            }

            // Find or create the page record
            $page = \App\Models\PdfPage::firstOrCreate(
                [
                    'document_id' => $this->pdfDocument->id,
                    'page_number' => $pageNumber,
                ],
                [
                    'created_at' => now(),
                ]
            );

            // Update with enhanced classification data
            $page->update([
                'primary_purpose' => $pageData['primary_purpose'] ?? null,
                'page_label' => $pageData['page_label'] ?? null,
                'drawing_number' => $pageData['drawing_number'] ?? null,
                'view_types' => $pageData['view_types'] ?? null,
                'section_labels' => $pageData['section_labels'] ?? null,
                'has_hardware_schedule' => $pageData['has_hardware_schedule'] ?? false,
                'has_material_spec' => $pageData['has_material_spec'] ?? false,
                'face_frame_material' => $pageData['face_frame_material'] ?? null,
                'interior_material' => $pageData['interior_material'] ?? null,
                'locations_documented' => $pageData['locations_documented'] ?? null,
                'is_location_detail' => in_array($pageData['primary_purpose'] ?? '', ['location_detail', 'multi_location']),
                'page_notes' => $pageData['page_notes'] ?? null,
                // Keep legacy page_type in sync for backwards compatibility
                'page_type' => $this->mapPrimaryPurposeToLegacyType($pageData['primary_purpose'] ?? null),
            ]);
        }
    }

    /**
     * Map new primary_purpose to legacy page_type for backwards compatibility
     *
     * @param string|null $purpose
     * @return string|null
     */
    protected function mapPrimaryPurposeToLegacyType(?string $purpose): ?string
    {
        $mapping = [
            'cover' => 'cover_page',
            'plan_view' => 'floor_plan',
            'overview' => 'elevation',
            'location_detail' => 'elevation',
            'multi_location' => 'elevation',
            'millwork' => 'countertops',
            'reference' => 'rendering',
            'schedule' => 'schedule',
            'other' => 'other',
        ];

        return $mapping[$purpose] ?? $purpose;
    }

    /**
     * Build cover page data from project partner and address information
     *
     * @return array
     */
    protected function buildCoverPageData(): array
    {
        $partner = $this->record->partner;

        // Build project address from project addresses
        $projectAddress = '';
        if ($this->record->addresses()->count() > 0) {
            $address = $this->record->addresses()->where('is_primary', true)->first()
                       ?? $this->record->addresses()->first();

            $parts = array_filter([
                $address->street1,
                $address->street2,
                $address->city,
                $address->state?->name,
                $address->zip,
            ]);

            $projectAddress = ! empty($parts) ? implode(', ', $parts) : '';
        }

        return [
            // Customer Details
            'cover_customer_name' => $partner->name ?? '',
            'cover_customer_address' => [
                'street1' => $partner->street1 ?? '',
                'street2' => $partner->street2 ?? '',
                'city' => $partner->city ?? '',
                'country_id' => $partner->country_id ?? 1,
                'state_id' => $partner->state_id ?? null,
                'zip' => $partner->zip ?? '',
            ],
            'cover_customer_phone' => $partner->phone ?? '',
            'cover_customer_email' => $partner->email ?? '',

            // Project Details
            'cover_project_number' => $this->record->project_number ?? '',
            'cover_project_name' => $this->record->name ?? '',
            'cover_project_address' => $projectAddress,
            'cover_project_date' => $this->record->created_at?->format('F d, Y') ?? now()->format('F d, Y'),
        ];
    }

    /**
     * Confirm draft data to project record
     * Saves all draft data (rooms, cabinet runs, scope estimates) permanently to project
     */
    public function confirmToProject(): void
    {
        $data = $this->form->getState();
        $savedItems = [];

        // 1. Save rooms and cabinet runs from the proposal builder
        $rooms = $data['rooms'] ?? [];
        if (!empty($rooms)) {
            foreach ($rooms as $roomData) {
                $roomName = $roomData['room_name'] ?? $roomData['room_type'] ?? null;
                if (!$roomName) continue;

                // Find or create room
                $room = Room::firstOrCreate(
                    ['project_id' => $this->record->id, 'name' => $roomName],
                    ['description' => $roomData['description'] ?? null]
                );

                // Save cabinet runs
                foreach ($roomData['cabinet_runs'] ?? [] as $runData) {
                    $runName = $runData['run_name'] ?? 'Unnamed Run';
                    $linearFeet = $runData['linear_feet'] ?? 0;
                    $level = $runData['cabinet_level'] ?? '2';

                    // Find or create a location for this run
                    $location = RoomLocation::firstOrCreate(
                        ['room_id' => $room->id, 'name' => $runName],
                        ['location_type' => 'wall']
                    );

                    // Create or update cabinet run
                    CabinetRun::updateOrCreate(
                        ['room_location_id' => $location->id, 'name' => $runName],
                        [
                            'linear_feet' => $linearFeet,
                            'pricing_level' => $level,
                            'project_id' => $this->record->id,
                        ]
                    );
                }
            }
            $savedItems[] = count($rooms) . ' rooms with cabinet runs';
        }

        // 2. Save cover page data if present
        $coverPage = collect($data['page_metadata'] ?? [])
            ->firstWhere('primary_purpose', 'cover');

        if ($coverPage) {
            $this->executeCoverPageSave($coverPage);
            $savedItems[] = 'cover page data';
        }

        // 3. Save page metadata to PDF pages
        foreach ($data['page_metadata'] ?? [] as $index => $pageData) {
            $pageNumber = $pageData['page_number'] ?? ($index + 1);
            $pdfPageId = $this->getPdfPageId($pageNumber);

            if ($pdfPageId) {
                $pdfPage = \App\Models\PdfPage::find($pdfPageId);
                if ($pdfPage) {
                    $pdfPage->update([
                        'primary_purpose' => $pageData['primary_purpose'] ?? null,
                        'page_label' => $pageData['page_label'] ?? null,
                        'page_notes' => $pageData['page_notes'] ?? null,
                        'has_hardware_schedule' => $pageData['has_hardware_schedule'] ?? false,
                        'has_material_spec' => $pageData['has_material_spec'] ?? false,
                    ]);
                }
            }
        }
        $savedItems[] = 'page classifications';

        // 4. Delete the draft since we've confirmed
        if ($this->draft) {
            $this->draft->update(['status' => 'completed']);
        }

        // Calculate totals for confirmation message
        $totalLf = collect($rooms)->sum(function($room) {
            return collect($room['cabinet_runs'] ?? [])->sum(fn($run) => (float)($run['linear_feet'] ?? 0));
        });

        Notification::make()
            ->success()
            ->title('Confirmed to Project')
            ->body("Saved: " . implode(', ', $savedItems) . ". Total: " . number_format($totalLf, 1) . " LF")
            ->persistent()
            ->actions([
                Action::make('view_project')
                    ->label('View Project')
                    ->url(ProjectResource::getUrl('view', ['record' => $this->record]))
            ])
            ->send();
    }

    // =====================================================
    // ENTITY CRUD MODAL METHODS
    // =====================================================

    /**
     * Get project rooms with full hierarchy for tree display
     * Loads from database instead of form state
     */
    public function getProjectRooms(): array
    {
        $rooms = Room::where('project_id', $this->record->id)
            ->with([
                'locations.cabinetRuns' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'locations.cabinetRuns.cabinets' // Eager load cabinets for linear_feet calculation
            ])
            ->orderBy('floor_number')
            ->orderBy('name')
            ->get();

        // Transform to array format for blade templates
        return $rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'room_type' => $room->room_type,
                'floor_number' => $room->floor_number,
                'pdf_page_number' => $room->pdf_page_number,
                'child_count' => $room->locations->count(),
                'locations' => $room->locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'location_type' => $location->location_type,
                        'runs' => $location->cabinetRuns->map(function ($run) {
                            return [
                                'id' => $run->id,
                                'name' => $run->name,
                                'run_type' => $run->run_type,
                                'linear_feet' => $run->linear_feet,
                                'pricing_tier' => $run->pricing_tier ?? $run->cabinet_level,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Open entity creator modal with context-aware defaults
     */
    public function openEntityCreator(string $entityType, ?int $parentId = null): void
    {
        $this->entityType = $entityType;
        $this->entityMode = 'create';
        $this->entityId = null;
        $this->entityParentId = $parentId;

        // Get defaults and enhance with PDF context
        $defaults = $this->getEntityDefaults($entityType);
        $this->entityFormData = $this->applyPdfContextDefaults($entityType, $defaults);

        $this->dispatch('open-modal', id: 'entity-crud-modal');
    }

    /**
     * Apply context-aware defaults based on current PDF page
     * UX: Pre-fills form fields from page label to reduce typing
     */
    protected function applyPdfContextDefaults(string $entityType, array $defaults): array
    {
        // Get current page info
        $pageLabel = $this->editDetailsData['page_label'] ?? '';
        $pageType = $this->editDetailsData['page_type'] ?? '';
        $linearFeet = $this->editDetailsData['linear_feet'] ?? null;

        if (empty($pageLabel)) {
            return $defaults;
        }

        // Parse room and location from page label
        // Common patterns: "Kitchen Elevations", "Kitchen - Sink Wall", "Master Bath Floor Plan"
        $context = $this->extractContextFromPageLabel($pageLabel);

        switch ($entityType) {
            case 'room':
                if (!empty($context['room'])) {
                    $defaults['name'] = $context['room'];
                    $defaults['_suggested_from_pdf'] = true;

                    // Auto-detect room type from name
                    $roomType = $this->detectRoomType($context['room']);
                    if ($roomType) {
                        $defaults['room_type'] = $roomType;
                    }
                }
                break;

            case 'room_location':
                if (!empty($context['location'])) {
                    $defaults['name'] = $context['location'];
                    $defaults['_suggested_from_pdf'] = true;

                    // Auto-detect location type
                    $locationType = $this->detectLocationType($context['location']);
                    if ($locationType) {
                        $defaults['location_type'] = $locationType;
                    }
                }
                break;

            case 'cabinet_run':
                // Use linear feet from edit details if available
                if ($linearFeet) {
                    $defaults['linear_feet'] = $linearFeet;
                    $defaults['_suggested_from_pdf'] = true;
                }

                // Try to detect run type from page label
                if (!empty($context['run_type'])) {
                    $defaults['run_type'] = $context['run_type'];
                }
                break;
        }

        return $defaults;
    }

    /**
     * Extract room name and location from page label
     * Patterns: "Kitchen Elevations", "Kitchen - Sink Wall", "Master Bath - Island"
     */
    protected function extractContextFromPageLabel(string $label): array
    {
        $context = ['room' => '', 'location' => '', 'run_type' => ''];

        // Common room keywords to strip
        $stripSuffixes = [
            'Elevations', 'Elevation', 'Floor Plan', 'Plan', 'Details',
            'Section', 'Sections', 'View', 'Views', 'Layout'
        ];

        // Check for "Room - Location" pattern
        if (str_contains($label, ' - ')) {
            $parts = explode(' - ', $label, 2);
            $context['room'] = trim($parts[0]);
            $context['location'] = trim($parts[1]);
        } else {
            // Strip common suffixes to get room name
            $cleanLabel = $label;
            foreach ($stripSuffixes as $suffix) {
                $cleanLabel = preg_replace('/\s*' . preg_quote($suffix, '/') . '\s*$/i', '', $cleanLabel);
            }
            $context['room'] = trim($cleanLabel);
        }

        // Detect run type keywords in label
        $labelLower = strtolower($label);
        if (str_contains($labelLower, 'base')) {
            $context['run_type'] = 'base';
        } elseif (str_contains($labelLower, 'wall') || str_contains($labelLower, 'upper')) {
            $context['run_type'] = 'wall';
        } elseif (str_contains($labelLower, 'tall') || str_contains($labelLower, 'pantry')) {
            $context['run_type'] = 'tall';
        } elseif (str_contains($labelLower, 'island')) {
            $context['run_type'] = 'island';
        }

        return $context;
    }

    /**
     * Detect room type from room name
     */
    protected function detectRoomType(string $name): ?string
    {
        $nameLower = strtolower($name);

        $typeMap = [
            'kitchen' => 'kitchen',
            'bath' => 'bathroom',
            'bathroom' => 'bathroom',
            'powder' => 'bathroom',
            'laundry' => 'laundry',
            'pantry' => 'pantry',
            'closet' => 'closet',
            'mudroom' => 'mudroom',
            'mud room' => 'mudroom',
            'office' => 'office',
            'study' => 'office',
            'bedroom' => 'bedroom',
            'master' => 'bedroom',
            'living' => 'living_room',
            'family' => 'living_room',
            'dining' => 'dining_room',
            'garage' => 'garage',
            'basement' => 'basement',
        ];

        foreach ($typeMap as $keyword => $type) {
            if (str_contains($nameLower, $keyword)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Detect location type from location name
     */
    protected function detectLocationType(string $name): ?string
    {
        $nameLower = strtolower($name);

        $typeMap = [
            'island' => 'island',
            'peninsula' => 'peninsula',
            'corner' => 'corner',
            'alcove' => 'alcove',
            'nook' => 'alcove',
            'sink wall' => 'sink_wall',
            'sink' => 'sink_wall',
            'range wall' => 'range_wall',
            'range' => 'range_wall',
            'stove' => 'range_wall',
            'refrigerator' => 'refrigerator_wall',
            'fridge' => 'refrigerator_wall',
        ];

        foreach ($typeMap as $keyword => $type) {
            if (str_contains($nameLower, $keyword)) {
                return $type;
            }
        }

        return 'wall'; // Default to wall
    }

    /**
     * Check if a room with similar name already exists
     * UX: Prevents duplicate creation
     */
    public function findSimilarRooms(string $name): array
    {
        if (empty($name)) {
            return [];
        }

        $nameLower = strtolower(trim($name));

        return Room::where('project_id', $this->record->id)
            ->get()
            ->filter(function ($room) use ($nameLower) {
                $roomNameLower = strtolower($room->name);
                // Check for exact match or contains
                return $roomNameLower === $nameLower
                    || str_contains($roomNameLower, $nameLower)
                    || str_contains($nameLower, $roomNameLower);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get suggested context from current PDF page for display
     */
    public function getSuggestedContext(): array
    {
        $pageLabel = $this->editDetailsData['page_label'] ?? '';
        if (empty($pageLabel)) {
            return [];
        }

        return $this->extractContextFromPageLabel($pageLabel);
    }

    /**
     * Close entity modal
     */
    public function closeEntityModal(): void
    {
        $this->dispatch('close-modal', id: 'entity-crud-modal');
        $this->entityType = '';
        $this->entityMode = 'create';
        $this->entityId = null;
        $this->entityParentId = null;
        $this->entityFormData = [];
    }

    /**
     * Get default form values for entity type
     */
    protected function getEntityDefaults(string $entityType): array
    {
        return match ($entityType) {
            'room' => [
                'name' => '',
                'room_type' => 'kitchen',
                'floor_number' => 1,
                'notes' => '',
            ],
            'room_location' => [
                'name' => '',
                'location_type' => 'wall',
                'overall_width_inches' => null,
                'cabinet_level' => '2',
                'material_category' => '',
                'finish_option' => '',
                'notes' => '',
            ],
            'cabinet_run' => [
                'name' => '',
                'run_type' => 'base',
                'linear_feet' => null,
                'sort_order' => 0,
            ],
            'cabinet' => [
                'name' => '',
                'cabinet_type' => 'base',
                'length_inches' => 24,
                'depth_inches' => 24,
                'height_inches' => 30,
                'quantity' => 1,
                'cabinet_level' => '2',
                'material_category' => '',
                'finish_option' => '',
                // Hardware fields
                'hinge_product_id' => '',
                'hinge_quantity' => 0,
                'slide_product_id' => '',
                'slide_quantity' => 0,
                // Door/Drawer configuration
                'door_style' => '',
                'door_mounting' => '',
                'door_count' => 0,
                'drawer_count' => 0,
            ],
            'section' => [
                'name' => '',
                'section_type' => 'door',
                'width_inches' => null,
                'height_inches' => null,
                'notes' => '',
            ],
            'door' => [
                'door_number' => '',
                'door_name' => '',
                'width_inches' => null,
                'height_inches' => null,
                'hinge_side' => 'left',
                'has_glass' => false,
                'finish_type' => '',
                'notes' => '',
                // Hardware product associations
                'hinge_product_id' => '',
                'decorative_hardware_product_id' => '',
            ],
            'drawer' => [
                'drawer_number' => '',
                'drawer_name' => '',
                'front_width_inches' => null,
                'front_height_inches' => null,
                'drawer_position' => 1,
                'slide_type' => '',
                'soft_close' => true,
                'finish_type' => '',
                'notes' => '',
                // Hardware product associations
                'slide_product_id' => '',
                'decorative_hardware_product_id' => '',
                // Drawer box dimensions
                'drawer_box_width_inches' => null,
                'drawer_box_height_inches' => null,
                'drawer_box_depth_inches' => null,
            ],
            'shelf' => [
                'shelf_number' => '',
                'shelf_name' => '',
                'width_inches' => null,
                'depth_inches' => null,
                'thickness_inches' => null,
                'shelf_type' => 'adjustable',
                'material' => 'plywood',
                'edge_treatment' => '',
                'finish_type' => '',
                'notes' => '',
                // Hardware product association (for roll-out shelves)
                'slide_product_id' => '',
            ],
            'pullout' => [
                'pullout_number' => '',
                'pullout_name' => '',
                'pullout_type' => 'roll_out_tray',
                'manufacturer' => '',
                'model_number' => '',
                'width_inches' => null,
                'height_inches' => null,
                'depth_inches' => null,
                'soft_close' => true,
                'quantity' => 1,
                'notes' => '',
                // Hardware product associations
                'product_id' => '',
                'slide_product_id' => '',
            ],
            default => [],
        };
    }

    /**
     * Load existing entity data for editing
     */
    protected function loadEntityData(string $entityType, int $entityId): array
    {
        $entity = match ($entityType) {
            'room' => Room::find($entityId),
            'room_location' => RoomLocation::find($entityId),
            'cabinet_run' => CabinetRun::find($entityId),
            'cabinet' => Cabinet::find($entityId),
            'section' => CabinetSection::find($entityId),
            'door' => Door::find($entityId),
            'drawer' => Drawer::find($entityId),
            'shelf' => Shelf::find($entityId),
            'pullout' => Pullout::find($entityId),
            default => null,
        };

        if (!$entity) {
            return [];
        }

        return match ($entityType) {
            'room' => [
                'name' => $entity->name,
                'room_type' => $entity->room_type,
                'floor_number' => $entity->floor_number,
                'notes' => $entity->notes ?? '',
            ],
            'room_location' => [
                'name' => $entity->name,
                'location_type' => $entity->location_type,
                'overall_width_inches' => $entity->overall_width_inches,
                'cabinet_level' => $entity->cabinet_level,
                'material_category' => $entity->material_category ?? '',
                'finish_option' => $entity->finish_option ?? '',
                'notes' => $entity->notes ?? '',
            ],
            'cabinet_run' => [
                'name' => $entity->name,
                'run_type' => $entity->run_type,
                'linear_feet' => $entity->linear_feet,
                'sort_order' => $entity->sort_order ?? 0,
            ],
            'cabinet' => [
                'name' => $entity->name,
                'cabinet_type' => $entity->cabinet_type,
                'length_inches' => $entity->length_inches,
                'depth_inches' => $entity->depth_inches,
                'height_inches' => $entity->height_inches,
                'quantity' => $entity->quantity ?? 1,
                'cabinet_level' => $entity->cabinet_level ?? '2',
                'material_category' => $entity->material_category ?? '',
                'finish_option' => $entity->finish_option ?? '',
                // Hardware fields
                'hinge_product_id' => $entity->hinge_product_id ?? '',
                'hinge_quantity' => $entity->hinge_quantity ?? 0,
                'slide_product_id' => $entity->slide_product_id ?? '',
                'slide_quantity' => $entity->slide_quantity ?? 0,
                // Door/Drawer configuration
                'door_style' => $entity->door_style ?? '',
                'door_mounting' => $entity->door_mounting ?? '',
                'door_count' => $entity->door_count ?? 0,
                'drawer_count' => $entity->drawer_count ?? 0,
            ],
            'section' => [
                'name' => $entity->name,
                'section_type' => $entity->section_type ?? 'door',
                'width_inches' => $entity->width_inches,
                'height_inches' => $entity->height_inches,
                'notes' => $entity->notes ?? '',
            ],
            'door' => [
                'door_number' => $entity->door_number ?? '',
                'door_name' => $entity->door_name ?? '',
                'width_inches' => $entity->width_inches,
                'height_inches' => $entity->height_inches,
                'hinge_side' => $entity->hinge_side ?? 'left',
                'has_glass' => $entity->has_glass ?? false,
                'finish_type' => $entity->finish_type ?? '',
                'notes' => $entity->notes ?? '',
                // Hardware product associations
                'hinge_product_id' => $entity->hinge_product_id ?? '',
                'decorative_hardware_product_id' => $entity->decorative_hardware_product_id ?? '',
            ],
            'drawer' => [
                'drawer_number' => $entity->drawer_number ?? '',
                'drawer_name' => $entity->drawer_name ?? '',
                'front_width_inches' => $entity->front_width_inches,
                'front_height_inches' => $entity->front_height_inches,
                'drawer_position' => $entity->drawer_position ?? 1,
                'slide_type' => $entity->slide_type ?? '',
                'soft_close' => $entity->soft_close ?? true,
                'finish_type' => $entity->finish_type ?? '',
                'notes' => $entity->notes ?? '',
                // Hardware product associations
                'slide_product_id' => $entity->slide_product_id ?? '',
                'decorative_hardware_product_id' => $entity->decorative_hardware_product_id ?? '',
                // Drawer box dimensions
                'drawer_box_width_inches' => $entity->drawer_box_width_inches,
                'drawer_box_height_inches' => $entity->drawer_box_height_inches,
                'drawer_box_depth_inches' => $entity->drawer_box_depth_inches,
            ],
            'shelf' => [
                'shelf_number' => $entity->shelf_number ?? '',
                'shelf_name' => $entity->shelf_name ?? '',
                'width_inches' => $entity->width_inches,
                'depth_inches' => $entity->depth_inches,
                'thickness_inches' => $entity->thickness_inches,
                'shelf_type' => $entity->shelf_type ?? 'adjustable',
                'material' => $entity->material ?? 'plywood',
                'edge_treatment' => $entity->edge_treatment ?? '',
                'finish_type' => $entity->finish_type ?? '',
                'notes' => $entity->notes ?? '',
                // Hardware product association (for roll-out shelves)
                'slide_product_id' => $entity->slide_product_id ?? '',
            ],
            'pullout' => [
                'pullout_number' => $entity->pullout_number ?? '',
                'pullout_name' => $entity->pullout_name ?? '',
                'pullout_type' => $entity->pullout_type ?? 'roll_out_tray',
                'manufacturer' => $entity->manufacturer ?? '',
                'model_number' => $entity->model_number ?? '',
                'width_inches' => $entity->width_inches,
                'height_inches' => $entity->height_inches,
                'depth_inches' => $entity->depth_inches,
                'soft_close' => $entity->soft_close ?? true,
                'quantity' => $entity->quantity ?? 1,
                'notes' => $entity->notes ?? '',
                // Hardware product associations
                'product_id' => $entity->product_id ?? '',
                'slide_product_id' => $entity->slide_product_id ?? '',
            ],
            default => $entity->toArray(),
        };
    }

    /**
     * Track recently created entities for visual feedback
     */
    public array $recentlyCreatedEntities = [];

    /**
     * Save entity (create or update)
     */
    public function saveEntity(): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('saveEntity called', [
                'mode' => $this->entityMode,
                'entityType' => $this->entityType,
                'entityParentId' => $this->entityParentId,
                'selectedSectionId' => $this->selectedSectionId,
                'formData' => $this->entityFormData,
            ]);

            if ($this->entityMode === 'create') {
                $this->createEntity();
            } else {
                $this->updateEntity();
            }

            \Illuminate\Support\Facades\Log::info('Entity saved, refreshing cache', [
                'selectedSectionId' => $this->selectedSectionId,
            ]);

            // Refresh hierarchy cache after save
            $this->refreshHierarchyCache();

            \Illuminate\Support\Facades\Log::info('Cache refreshed', [
                'selectedSectionId' => $this->selectedSectionId,
                'componentsInCache' => isset($this->hierarchyCache['components'][$this->selectedSectionId])
                    ? count($this->hierarchyCache['components'][$this->selectedSectionId])
                    : 'section not found in cache',
            ]);

            $this->closeEntityModal();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save entity', [
                'error' => $e->getMessage(),
                'type' => $this->entityType,
            ]);

            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to save: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Save entity to LOCAL STATE and reset form to add another
     * NO database call - instant response
     */
    public function saveEntityAndContinue(): void
    {
        // Validate required fields
        if (empty($this->entityFormData['name'])) {
            Notification::make()
                ->warning()
                ->title('Name required')
                ->duration(2000)
                ->send();
            return;
        }

        if ($this->entityMode === 'create') {
            // Add to pending entities (local state only)
            $this->addToPendingEntities();
        } else {
            // Update existing pending entity
            $this->updatePendingEntity();
        }

        // Reset form for next entry (keep entityType and parentId)
        $defaults = $this->getEntityDefaults($this->entityType);
        $this->entityFormData = $this->applyPdfContextDefaults($this->entityType, $defaults);
    }

    /**
     * Add entity to local pending state (no DB save)
     */
    protected function addToPendingEntities(): void
    {
        $tempId = 'temp_' . (++$this->tempIdCounter);
        $data = $this->entityFormData;
        $data['_temp_id'] = $tempId;

        $this->pendingEntities[$tempId] = [
            'type' => $this->entityType,
            'data' => $data,
            'parent_id' => $this->entityParentId,
            'created_at' => now()->toISOString(),
        ];

        // Track for visual feedback
        $this->recentlyCreatedEntities[] = [
            'type' => $this->entityType,
            'id' => $tempId,
            'name' => $data['name'] ?? 'Unnamed',
            'created_at' => now()->toISOString(),
            'pending' => true,
        ];

        // Keep only last 10 items
        if (count($this->recentlyCreatedEntities) > 10) {
            $this->recentlyCreatedEntities = array_slice($this->recentlyCreatedEntities, -10);
        }

        $this->hasUnsavedEntityChanges = true;
    }

    /**
     * Update a pending entity in local state
     */
    protected function updatePendingEntity(): void
    {
        $id = $this->entityId;

        if (is_string($id) && str_starts_with($id, 'temp_')) {
            // Update pending entity
            if (isset($this->pendingEntities[$id])) {
                $this->pendingEntities[$id]['data'] = $this->entityFormData;
            }
        }

        $this->hasUnsavedEntityChanges = true;
    }

    /**
     * Get all entities (database + pending) for display
     */
    public function getAllRoomsWithPending(): array
    {
        // Get existing rooms from database
        $dbRooms = $this->record->rooms()
            ->orderBy('name')
            ->get()
            ->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'room_type' => $room->room_type,
                'floor_number' => $room->floor_number,
                'pending' => false,
            ])
            ->toArray();

        // Add pending rooms
        $pendingRooms = collect($this->pendingEntities)
            ->filter(fn($entity) => $entity['type'] === 'room')
            ->map(fn($entity, $tempId) => [
                'id' => $tempId,
                'name' => $entity['data']['name'] ?? 'Unnamed',
                'room_type' => $entity['data']['room_type'] ?? 'other',
                'floor_number' => $entity['data']['floor_number'] ?? 1,
                'pending' => true,
            ])
            ->values()
            ->toArray();

        return array_merge($dbRooms, $pendingRooms);
    }

    /**
     * Get locations for a room (database + pending)
     */
    public function getLocationsWithPending(string|int $roomId): array
    {
        $dbLocations = [];

        // Only query DB if it's a real ID
        if (is_int($roomId) || (is_string($roomId) && !str_starts_with($roomId, 'temp_'))) {
            $dbLocations = RoomLocation::where('room_id', $roomId)
                ->orderBy('name')
                ->get()
                ->map(fn($loc) => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'location_type' => $loc->location_type,
                    'pending' => false,
                ])
                ->toArray();
        }

        // Add pending locations for this room
        $pendingLocations = collect($this->pendingEntities)
            ->filter(fn($entity) => $entity['type'] === 'room_location' && $entity['parent_id'] == $roomId)
            ->map(fn($entity, $tempId) => [
                'id' => $tempId,
                'name' => $entity['data']['name'] ?? 'Unnamed',
                'location_type' => $entity['data']['location_type'] ?? 'wall',
                'pending' => true,
            ])
            ->values()
            ->toArray();

        return array_merge($dbLocations, $pendingLocations);
    }

    /**
     * Persist all pending entities to database
     * Called when user clicks "Save All" or closes modal
     */
    public function persistPendingEntities(): void
    {
        if (empty($this->pendingEntities)) {
            return;
        }

        $idMapping = []; // Maps temp IDs to real IDs
        $counts = ['room' => 0, 'room_location' => 0, 'cabinet_run' => 0, 'cabinet' => 0, 'section' => 0];

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // First pass: Create rooms (no parent dependencies)
            foreach ($this->pendingEntities as $tempId => $entity) {
                if ($entity['type'] === 'room') {
                    $data = $entity['data'];
                    unset($data['_temp_id'], $data['_suggested_from_pdf']);
                    $data['project_id'] = $this->record->id;
                    $data['creator_id'] = auth()->id();

                    $room = Room::create($data);
                    $idMapping[$tempId] = $room->id;
                    $counts['room']++;
                }
            }

            // Second pass: Create room locations
            foreach ($this->pendingEntities as $tempId => $entity) {
                if ($entity['type'] === 'room_location') {
                    $data = $entity['data'];
                    unset($data['_temp_id'], $data['_suggested_from_pdf']);

                    // Resolve parent ID (could be temp or real)
                    $parentId = $entity['parent_id'];
                    if (is_string($parentId) && str_starts_with($parentId, 'temp_')) {
                        $parentId = $idMapping[$parentId] ?? null;
                    }

                    if ($parentId) {
                        $data['room_id'] = $parentId;
                        $data['creator_id'] = auth()->id();

                        $location = RoomLocation::create($data);
                        $idMapping[$tempId] = $location->id;
                        $counts['room_location']++;
                    }
                }
            }

            // Third pass: Create cabinet runs
            foreach ($this->pendingEntities as $tempId => $entity) {
                if ($entity['type'] === 'cabinet_run') {
                    $data = $entity['data'];
                    unset($data['_temp_id'], $data['_suggested_from_pdf']);

                    $parentId = $entity['parent_id'];
                    if (is_string($parentId) && str_starts_with($parentId, 'temp_')) {
                        $parentId = $idMapping[$parentId] ?? null;
                    }

                    if ($parentId) {
                        $data['room_location_id'] = $parentId;

                        $run = CabinetRun::create($data);
                        $idMapping[$tempId] = $run->id;
                        $counts['cabinet_run']++;
                    }
                }
            }

            // Fourth pass: Create cabinets
            foreach ($this->pendingEntities as $tempId => $entity) {
                if ($entity['type'] === 'cabinet') {
                    $data = $entity['data'];
                    unset($data['_temp_id'], $data['_suggested_from_pdf']);

                    $parentId = $entity['parent_id'];
                    if (is_string($parentId) && str_starts_with($parentId, 'temp_')) {
                        $parentId = $idMapping[$parentId] ?? null;
                    }

                    if ($parentId) {
                        $run = CabinetRun::find($parentId);
                        $data['cabinet_run_id'] = $parentId;
                        $data['room_id'] = $run?->roomLocation?->room_id;

                        $cabinet = Cabinet::create($data);
                        $idMapping[$tempId] = $cabinet->id;
                        $counts['cabinet']++;
                    }
                }
            }

            // Fifth pass: Create sections
            foreach ($this->pendingEntities as $tempId => $entity) {
                if ($entity['type'] === 'section') {
                    $data = $entity['data'];
                    unset($data['_temp_id'], $data['_suggested_from_pdf']);

                    $parentId = $entity['parent_id'];
                    if (is_string($parentId) && str_starts_with($parentId, 'temp_')) {
                        $parentId = $idMapping[$parentId] ?? null;
                    }

                    if ($parentId) {
                        $data['cabinet_id'] = $parentId;

                        CabinetSection::create($data);
                        $counts['section']++;
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            // Clear pending state
            $this->pendingEntities = [];
            $this->recentlyCreatedEntities = [];
            $this->hasUnsavedEntityChanges = false;

            // Show success notification with counts
            $parts = [];
            if ($counts['room'] > 0) $parts[] = "{$counts['room']} room(s)";
            if ($counts['room_location'] > 0) $parts[] = "{$counts['room_location']} location(s)";
            if ($counts['cabinet_run'] > 0) $parts[] = "{$counts['cabinet_run']} run(s)";
            if ($counts['cabinet'] > 0) $parts[] = "{$counts['cabinet']} cabinet(s)";
            if ($counts['section'] > 0) $parts[] = "{$counts['section']} section(s)";

            Notification::make()
                ->success()
                ->title('Saved to Database')
                ->body('Created: ' . implode(', ', $parts))
                ->send();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Failed to persist pending entities', [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Save Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Remove a pending entity from local state
     */
    public function removePendingEntity(string $tempId): void
    {
        if (isset($this->pendingEntities[$tempId])) {
            unset($this->pendingEntities[$tempId]);

            // Also remove from recently created
            $this->recentlyCreatedEntities = array_filter(
                $this->recentlyCreatedEntities,
                fn($item) => $item['id'] !== $tempId
            );
        }
    }

    /**
     * Discard all pending changes
     */
    public function discardPendingEntities(): void
    {
        $this->pendingEntities = [];
        $this->recentlyCreatedEntities = [];
        $this->hasUnsavedEntityChanges = false;

        Notification::make()
            ->warning()
            ->title('Changes Discarded')
            ->body('All unsaved entities have been removed.')
            ->send();
    }

    /**
     * Sanitize form data - convert empty strings to null for database fields
     */
    protected function sanitizeEntityData(array $data): array
    {
        // Fields that should be null instead of empty string
        $nullableFields = [
            'hinge_product_id',
            'decorative_hardware_product_id',
            'slide_product_id',
            'finish_type',
            'door_style',
            'door_mounting',
            'notes',
            'description',
            'hardware_notes',
            'installation_notes',
        ];

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * Create a new entity
     */
    protected function createEntity(): void
    {
        $data = $this->sanitizeEntityData($this->entityFormData);

        switch ($this->entityType) {
            case 'room':
                $data['project_id'] = $this->record->id;
                $data['creator_id'] = auth()->id();
                Room::create($data);
                break;

            case 'room_location':
                $data['room_id'] = $this->entityParentId;
                $data['creator_id'] = auth()->id();
                RoomLocation::create($data);
                break;

            case 'cabinet_run':
                $data['room_location_id'] = $this->entityParentId;
                CabinetRun::create($data);
                break;

            case 'cabinet':
                $run = CabinetRun::find($this->entityParentId);
                $data['cabinet_run_id'] = $this->entityParentId;
                $data['room_id'] = $run?->roomLocation?->room_id;
                Cabinet::create($data);
                break;

            case 'section':
                $data['cabinet_id'] = $this->entityParentId;
                CabinetSection::create($data);
                break;

            case 'door':
                $section = CabinetSection::find($this->entityParentId);
                $data['section_id'] = $this->entityParentId;
                $data['cabinet_id'] = $section?->cabinet_id;
                $created = Door::create($data);
                \Illuminate\Support\Facades\Log::info('Door created', [
                    'door_id' => $created->id,
                    'section_id' => $this->entityParentId,
                    'cabinet_id' => $section?->cabinet_id,
                    'name' => $data['name'] ?? $data['door_name'] ?? 'unnamed',
                ]);
                break;

            case 'drawer':
                $section = CabinetSection::find($this->entityParentId);
                $data['section_id'] = $this->entityParentId;
                $data['cabinet_id'] = $section?->cabinet_id;
                Drawer::create($data);
                break;

            case 'shelf':
                $section = CabinetSection::find($this->entityParentId);
                $data['section_id'] = $this->entityParentId;
                $data['cabinet_id'] = $section?->cabinet_id;
                Shelf::create($data);
                break;

            case 'pullout':
                $section = CabinetSection::find($this->entityParentId);
                $data['section_id'] = $this->entityParentId;
                $data['cabinet_id'] = $section?->cabinet_id;
                Pullout::create($data);
                break;
        }

        $label = $this->getEntityLabel($this->entityType);
        Notification::make()
            ->success()
            ->title("{$label} Created")
            ->body("The {$label} has been created successfully.")
            ->send();
    }

    /**
     * Update an existing entity
     */
    protected function updateEntity(): void
    {
        $entity = match ($this->entityType) {
            'room' => Room::find($this->entityId),
            'room_location' => RoomLocation::find($this->entityId),
            'cabinet_run' => CabinetRun::find($this->entityId),
            'cabinet' => Cabinet::find($this->entityId),
            'section' => CabinetSection::find($this->entityId),
            'door' => Door::find($this->entityId),
            'drawer' => Drawer::find($this->entityId),
            'shelf' => Shelf::find($this->entityId),
            'pullout' => Pullout::find($this->entityId),
            default => null,
        };

        if (!$entity) {
            throw new \Exception('Entity not found');
        }

        $entity->update($this->sanitizeEntityData($this->entityFormData));

        $label = $this->getEntityLabel($this->entityType);
        Notification::make()
            ->success()
            ->title("{$label} Updated")
            ->body("The {$label} has been updated successfully.")
            ->send();
    }

    /**
     * Delete an entity
     */
    public function deleteEntity(): void
    {
        try {
            $entity = match ($this->entityType) {
                'room' => Room::find($this->entityId),
                'room_location' => RoomLocation::find($this->entityId),
                'cabinet_run' => CabinetRun::find($this->entityId),
                'cabinet' => Cabinet::find($this->entityId),
                'section' => CabinetSection::find($this->entityId),
                'door' => Door::find($this->entityId),
                'drawer' => Drawer::find($this->entityId),
                'shelf' => Shelf::find($this->entityId),
                'pullout' => Pullout::find($this->entityId),
                default => null,
            };

            if ($entity) {
                $entity->delete();

                // Refresh hierarchy cache after delete
                $this->refreshHierarchyCache();

                $label = $this->getEntityLabel($this->entityType);
                Notification::make()
                    ->success()
                    ->title("{$label} Deleted")
                    ->body("The {$label} has been deleted.")
                    ->send();
            }

            $this->closeEntityModal();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to delete: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Delete entity directly with type and ID parameters
     * Used by inline delete buttons in Edit Details modal
     */
    public function deleteEntityDirect(string $entityType, int $entityId): void
    {
        try {
            $entity = match ($entityType) {
                'room' => Room::find($entityId),
                'room_location' => RoomLocation::find($entityId),
                'cabinet_run' => CabinetRun::find($entityId),
                'cabinet' => Cabinet::find($entityId),
                'section' => CabinetSection::find($entityId),
                'door' => Door::find($entityId),
                'drawer' => Drawer::find($entityId),
                'shelf' => Shelf::find($entityId),
                'pullout' => Pullout::find($entityId),
                default => null,
            };

            if ($entity) {
                $entityName = $entity->name ?? 'Unknown';
                $entity->delete();

                // Refresh hierarchy cache after delete
                $this->refreshHierarchyCache();

                $label = $this->getEntityLabel($entityType);
                Notification::make()
                    ->success()
                    ->title("{$label} Deleted")
                    ->body("'{$entityName}' has been deleted.")
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to delete: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Get human-readable label for entity type
     */
    protected function getEntityLabel(string $entityType): string
    {
        return match ($entityType) {
            'room' => 'Room',
            'room_location' => 'Location',
            'cabinet_run' => 'Cabinet Run',
            'cabinet' => 'Cabinet',
            'section' => 'Section',
            'door' => 'Door',
            'drawer' => 'Drawer',
            'shelf' => 'Shelf',
            'pullout' => 'Pullout',
            default => 'Entity',
        };
    }

    /**
     * Parse fractional measurement input and convert to decimal inches
     *
     * Supports woodworker-friendly formats:
     * - "12.5" -> 12.5 (decimal)
     * - "12 1/2" -> 12.5 (whole + fraction with space)
     * - "12-1/2" -> 12.5 (whole + fraction with dash)
     * - "3/4" -> 0.75 (fraction only)
     * - "1/2" -> 0.5
     * - "2'" -> 24 (feet to inches)
     * - "2' 6" -> 30 (feet and inches)
     * - "2' 6 1/2" -> 30.5 (feet, inches, and fraction)
     *
     * @param  string|float|null  $input  The input measurement
     * @return float|null The decimal value or null if invalid
     */
    public function parseFractionalMeasurement($input): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }

        // If already a number, return it
        if (is_numeric($input)) {
            return (float) $input;
        }

        // Convert to string for parsing
        $input = trim((string) $input);

        // Handle feet notation first (e.g., "2'" or "2' 6" or "2' 6 1/2")
        if (preg_match("/^(\d+)'(?:\s*(\d+(?:\s+\d+\/\d+|\-\d+\/\d+|\/\d+)?)?)?$/", $input, $feetMatches)) {
            $feet = (int) $feetMatches[1];
            $inches = 0.0;

            if (!empty($feetMatches[2])) {
                // Parse the inches part (which may include fractions)
                $inches = $this->parseFractionalMeasurement($feetMatches[2]) ?? 0;
            }

            return ($feet * 12) + $inches;
        }

        // Pattern: Match "12 1/2" (whole number space fraction)
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $input, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator == 0) {
                return null;
            }

            return $whole + ($numerator / $denominator);
        }

        // Format: "12-1/2" (whole number dash fraction)
        if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $input, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator == 0) {
                return null;
            }

            return $whole + ($numerator / $denominator);
        }

        // Format: "3/4" (just fraction)
        if (preg_match('/^(\d+)\/(\d+)$/', $input, $matches)) {
            $numerator = (int) $matches[1];
            $denominator = (int) $matches[2];

            if ($denominator == 0) {
                return null;
            }

            return $numerator / $denominator;
        }

        // Try as decimal
        if (is_numeric($input)) {
            return (float) $input;
        }

        // Invalid format - return null
        return null;
    }

    /**
     * Handle measurement field update - parses fractional input and updates the field
     */
    public function updateMeasurementField(string $field, $value): void
    {
        $parsed = $this->parseFractionalMeasurement($value);
        $this->entityFormData[$field] = $parsed;
    }

    /**
     * Get entity modal heading
     */
    public function getEntityModalHeading(): string
    {
        $label = $this->getEntityLabel($this->entityType);
        return $this->entityMode === 'create' ? "Create {$label}" : "Edit {$label}";
    }

    // =====================================================
    // HIERARCHICAL NAVIGATION METHODS
    // =====================================================

    /**
     * Initialize breadcrumbs to root level (rooms)
     */
    public function initHierarchy(): void
    {
        $this->hierarchyLevel = 'rooms';
        $this->breadcrumbs = [
            ['level' => 'rooms', 'id' => null, 'name' => 'All Rooms']
        ];
        $this->selectedRoomId = null;
        $this->selectedRoomName = null;
        $this->selectedLocationId = null;
        $this->selectedLocationName = null;
        $this->selectedRunId = null;
        $this->selectedRunName = null;
        $this->selectedCabinetId = null;
        $this->selectedCabinetName = null;
        $this->selectedSectionId = null;
        $this->selectedSectionName = null;
    }

    /**
     * Drill down into a room to see its locations
     */
    public function drillDownToRoom(int $roomId): void
    {
        $room = Room::find($roomId);
        if (!$room) return;

        $this->selectedRoomId = $roomId;
        $this->selectedRoomName = $room->name;
        $this->hierarchyLevel = 'locations';

        $this->breadcrumbs = [
            ['level' => 'rooms', 'id' => null, 'name' => 'All Rooms'],
            ['level' => 'locations', 'id' => $roomId, 'name' => $room->name]
        ];
    }

    /**
     * Drill down into a location to see its cabinet runs
     */
    public function drillDownToLocation(int $locationId): void
    {
        $location = RoomLocation::with('room')->find($locationId);
        if (!$location) return;

        $this->selectedLocationId = $locationId;
        $this->selectedLocationName = $location->name;
        $this->selectedRoomId = $location->room_id;
        $this->selectedRoomName = $location->room->name ?? 'Unknown Room';
        $this->hierarchyLevel = 'runs';

        $this->breadcrumbs = [
            ['level' => 'rooms', 'id' => null, 'name' => 'All Rooms'],
            ['level' => 'locations', 'id' => $location->room_id, 'name' => $this->selectedRoomName],
            ['level' => 'runs', 'id' => $locationId, 'name' => $location->name]
        ];
    }

    /**
     * Drill down into a cabinet run to see its cabinets
     */
    public function drillDownToRun(int $runId): void
    {
        $run = CabinetRun::with(['roomLocation.room'])->find($runId);
        if (!$run) return;

        $this->selectedRunId = $runId;
        $this->selectedRunName = $run->name;
        $this->selectedLocationId = $run->room_location_id;
        $this->selectedLocationName = $run->roomLocation->name ?? 'Unknown Location';
        $this->selectedRoomId = $run->roomLocation->room_id ?? null;
        $this->selectedRoomName = $run->roomLocation->room->name ?? 'Unknown Room';
        $this->hierarchyLevel = 'cabinets';

        $this->breadcrumbs = [
            ['level' => 'rooms', 'id' => null, 'name' => 'All Rooms'],
            ['level' => 'locations', 'id' => $this->selectedRoomId, 'name' => $this->selectedRoomName],
            ['level' => 'runs', 'id' => $this->selectedLocationId, 'name' => $this->selectedLocationName],
            ['level' => 'cabinets', 'id' => $runId, 'name' => $run->name]
        ];
    }

    /**
     * Drill down into a cabinet to see its sections
     */
    public function drillDownToCabinet(int $cabinetId): void
    {
        $cabinet = Cabinet::with(['cabinetRun.roomLocation.room'])->find($cabinetId);
        if (!$cabinet) return;

        $this->selectedCabinetId = $cabinetId;
        $this->selectedCabinetName = $cabinet->cabinet_number ?? "Cabinet #{$cabinetId}";
        $this->selectedRunId = $cabinet->cabinet_run_id;
        $this->selectedRunName = $cabinet->cabinetRun->name ?? 'Unknown Run';
        $this->selectedLocationId = $cabinet->cabinetRun->room_location_id ?? null;
        $this->selectedLocationName = $cabinet->cabinetRun->roomLocation->name ?? 'Unknown Location';
        $this->selectedRoomId = $cabinet->cabinetRun->roomLocation->room_id ?? null;
        $this->selectedRoomName = $cabinet->cabinetRun->roomLocation->room->name ?? 'Unknown Room';
        $this->hierarchyLevel = 'sections';

        $this->breadcrumbs = [
            ['level' => 'rooms', 'id' => null, 'name' => 'All Rooms'],
            ['level' => 'locations', 'id' => $this->selectedRoomId, 'name' => $this->selectedRoomName],
            ['level' => 'runs', 'id' => $this->selectedLocationId, 'name' => $this->selectedLocationName],
            ['level' => 'cabinets', 'id' => $this->selectedRunId, 'name' => $this->selectedRunName],
            ['level' => 'sections', 'id' => $cabinetId, 'name' => $this->selectedCabinetName]
        ];
    }

    /**
     * Drill down into a section to see its components (doors/drawers)
     */
    public function drillDownToSection(int $sectionId): void
    {
        // Get section data from cache
        $section = $this->hierarchyCache['sections'][$sectionId] ?? null;
        if (!$section) {
            // Debug: Log available section IDs for troubleshooting
            $availableSectionIds = array_keys($this->hierarchyCache['sections'] ?? []);
            \Log::warning("drillDownToSection: Section ID {$sectionId} not found in cache. Available IDs: " . implode(', ', $availableSectionIds));

            Notification::make()
                ->title('Entity not found')
                ->body("Section #{$sectionId} was not found in the hierarchy cache. Try refreshing the page.")
                ->danger()
                ->send();
            return;
        }

        $this->selectedSectionId = $sectionId;
        $this->selectedSectionName = $section['name'] ?? "Section #{$sectionId}";
        $this->hierarchyLevel = 'components';

        // Add section to breadcrumbs
        $this->breadcrumbs[] = ['level' => 'components', 'id' => $sectionId, 'name' => $this->selectedSectionName];
    }

    /**
     * Navigate to a specific breadcrumb level
     */
    public function navigateToBreadcrumb(string $level, ?int $id = null): void
    {
        switch ($level) {
            case 'rooms':
                $this->initHierarchy();
                break;
            case 'locations':
                if ($id) $this->drillDownToRoom($id);
                break;
            case 'runs':
                if ($id) $this->drillDownToLocation($id);
                break;
            case 'cabinets':
                if ($id) $this->drillDownToRun($id);
                break;
            case 'sections':
                if ($id) $this->drillDownToCabinet($id);
                break;
            case 'components':
                if ($id) $this->drillDownToSection($id);
                break;
        }
    }

    /**
     * Edit an entity from breadcrumb click
     * Loads the entity into the inline edit panel (same as clicking items in the list)
     */
    public function editBreadcrumbEntity(string $entityType, int $entityId): void
    {
        // Map breadcrumb entity types to highlightEntity types
        $highlightType = match ($entityType) {
            'room' => 'room',
            'room_location' => 'location',
            'cabinet_run' => 'run',
            'cabinet' => 'cabinet',
            'section' => 'section',
            default => $entityType,
        };

        // Use the same highlight/inline edit system as the list items
        $this->highlightEntity($highlightType, $entityId);
    }

    /**
     * Go back one level in the hierarchy
     */
    public function navigateUp(): void
    {
        if (count($this->breadcrumbs) <= 1) {
            $this->initHierarchy();
            return;
        }

        // Remove the last breadcrumb
        array_pop($this->breadcrumbs);

        // Navigate to the new last breadcrumb
        $lastBreadcrumb = end($this->breadcrumbs);
        if ($lastBreadcrumb) {
            $this->navigateToBreadcrumb($lastBreadcrumb['level'], $lastBreadcrumb['id']);
        }
    }

    /**
     * Load full project hierarchy into cache (called once on mount)
     * This eliminates per-click DB queries for tree navigation
     */
    public function loadFullProjectHierarchy(): void
    {
        if ($this->hierarchyLoaded) {
            return;
        }

        // Single query with full eager loading of entire hierarchy
        $rooms = Room::where('project_id', $this->record->id)
            ->with([
                'locations' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'locations.cabinetRuns' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'locations.cabinetRuns.cabinets' => function ($query) {
                    $query->orderBy('position_in_run')->orderBy('id');
                },
                'locations.cabinetRuns.cabinets.sections' => function ($query) {
                    $query->orderBy('sort_order')->orderBy('id');
                },
                'locations.cabinetRuns.cabinets.sections.doors',
                'locations.cabinetRuns.cabinets.sections.drawers',
                'locations.cabinetRuns.cabinets.sections.shelves',
                'locations.cabinetRuns.cabinets.sections.pullouts',
            ])
            ->orderBy('floor_number')
            ->orderBy('name')
            ->get();

        // Build indexed cache for O(1) lookups
        $this->hierarchyCache = [
            'rooms' => [],
            'locations' => [],
            'runs' => [],
            'cabinets' => [],
            'sections' => [],
            'components' => [], // Doors and drawers indexed by section_id
        ];

        foreach ($rooms as $room) {
            $roomData = [
                'id' => $room->id,
                'name' => $room->name,
                'room_type' => $room->room_type,
                'floor_number' => $room->floor_number,
                'pdf_page_number' => $room->pdf_page_number,
                'child_count' => $room->locations->count(),
                'child_ids' => $room->locations->pluck('id')->toArray(),
            ];
            $this->hierarchyCache['rooms'][$room->id] = $roomData;

            foreach ($room->locations as $location) {
                $locationData = [
                    'id' => $location->id,
                    'name' => $location->name,
                    'type' => $location->location_type ?? 'wall',
                    'sort_order' => $location->sort_order,
                    'room_id' => $room->id,
                    'child_count' => $location->cabinetRuns->count(),
                    'child_ids' => $location->cabinetRuns->pluck('id')->toArray(),
                ];
                $this->hierarchyCache['locations'][$location->id] = $locationData;

                foreach ($location->cabinetRuns as $run) {
                    $runData = [
                        'id' => $run->id,
                        'name' => $run->name,
                        'type' => $run->run_type ?? 'base',
                        'material' => $run->material_type,
                        'level' => $run->pricing_level,
                        'linear_feet' => $run->linear_feet,
                        'stored_linear_feet' => $run->stored_linear_feet,
                        'has_discrepancy' => $run->has_linear_feet_discrepancy,
                        'has_missing_measurements' => $run->has_missing_measurements,
                        'missing_linear_feet' => $run->missing_linear_feet,
                        'cabinets_missing_width_count' => $run->cabinets_missing_width_count,
                        'location_id' => $location->id,
                        'child_count' => $run->cabinets->count(),
                        'child_ids' => $run->cabinets->pluck('id')->toArray(),
                    ];
                    $this->hierarchyCache['runs'][$run->id] = $runData;

                    foreach ($run->cabinets as $cabinet) {
                        $cabinetData = [
                            'id' => $cabinet->id,
                            'name' => $cabinet->cabinet_number ?: "Cabinet #{$cabinet->id}",
                            'cabinet_type' => $cabinet->cabinet_type ?? 'base',
                            'width' => $cabinet->width_inches,
                            'linear_feet' => $cabinet->linear_feet,
                            'quantity' => $cabinet->quantity,
                            'price' => $cabinet->total_price,
                            'run_id' => $run->id,
                            'child_count' => $cabinet->sections->count(),
                            'child_ids' => $cabinet->sections->pluck('id')->toArray(),
                        ];
                        $this->hierarchyCache['cabinets'][$cabinet->id] = $cabinetData;

                        foreach ($cabinet->sections as $section) {
                            // Build component list for this section (doors, drawers, shelves, pullouts)
                            $sectionComponents = [];

                            foreach ($section->doors as $door) {
                                $sectionComponents[] = [
                                    'id' => $door->id,
                                    'component_type' => 'door',
                                    'name' => $door->door_name ?: $door->door_number ?: "Door #{$door->id}",
                                    'number' => $door->door_number,
                                    'width' => $door->width_inches,
                                    'height' => $door->height_inches,
                                    'dimensions' => $door->width_inches && $door->height_inches
                                        ? number_format($door->width_inches, 1) . '"W × ' . number_format($door->height_inches, 1) . '"H'
                                        : null,
                                    'hinge_side' => $door->hinge_side,
                                    'has_glass' => $door->has_glass ?? false,
                                    'finish_type' => $door->finish_type,
                                    'section_id' => $section->id,
                                    'cabinet_id' => $cabinet->id,
                                    'sort_order' => $door->sort_order ?? 0,
                                ];
                            }

                            foreach ($section->drawers as $drawer) {
                                $sectionComponents[] = [
                                    'id' => $drawer->id,
                                    'component_type' => 'drawer',
                                    'name' => $drawer->drawer_name ?: $drawer->drawer_number ?: "Drawer #{$drawer->id}",
                                    'number' => $drawer->drawer_number,
                                    'width' => $drawer->front_width_inches,
                                    'height' => $drawer->front_height_inches,
                                    'dimensions' => $drawer->front_width_inches && $drawer->front_height_inches
                                        ? number_format($drawer->front_width_inches, 1) . '"W × ' . number_format($drawer->front_height_inches, 1) . '"H'
                                        : null,
                                    'position' => $drawer->drawer_position,
                                    'slide_type' => $drawer->slide_type,
                                    'finish_type' => $drawer->finish_type,
                                    'section_id' => $section->id,
                                    'cabinet_id' => $cabinet->id,
                                    'sort_order' => $drawer->sort_order ?? 0,
                                ];
                            }

                            foreach ($section->shelves as $shelf) {
                                $sectionComponents[] = [
                                    'id' => $shelf->id,
                                    'component_type' => 'shelf',
                                    'name' => $shelf->shelf_name ?: $shelf->shelf_number ?: "Shelf #{$shelf->id}",
                                    'number' => $shelf->shelf_number,
                                    'width' => $shelf->width_inches,
                                    'depth' => $shelf->depth_inches,
                                    'thickness' => $shelf->thickness_inches,
                                    'dimensions' => $shelf->width_inches && $shelf->depth_inches
                                        ? number_format($shelf->width_inches, 1) . '"W × ' . number_format($shelf->depth_inches, 1) . '"D'
                                        : null,
                                    'shelf_type' => $shelf->shelf_type,
                                    'material' => $shelf->material,
                                    'finish_type' => $shelf->finish_type,
                                    'section_id' => $section->id,
                                    'cabinet_id' => $cabinet->id,
                                    'sort_order' => $shelf->sort_order ?? 0,
                                ];
                            }

                            foreach ($section->pullouts as $pullout) {
                                $sectionComponents[] = [
                                    'id' => $pullout->id,
                                    'component_type' => 'pullout',
                                    'name' => $pullout->pullout_name ?: $pullout->pullout_number ?: "Pullout #{$pullout->id}",
                                    'number' => $pullout->pullout_number,
                                    'width' => $pullout->width_inches,
                                    'height' => $pullout->height_inches,
                                    'depth' => $pullout->depth_inches,
                                    'dimensions' => $pullout->width_inches && $pullout->height_inches
                                        ? number_format($pullout->width_inches, 1) . '"W × ' . number_format($pullout->height_inches, 1) . '"H'
                                        : null,
                                    'pullout_type' => $pullout->pullout_type,
                                    'manufacturer' => $pullout->manufacturer,
                                    'model_number' => $pullout->model_number,
                                    'soft_close' => $pullout->soft_close ?? false,
                                    'quantity' => $pullout->quantity ?? 1,
                                    'section_id' => $section->id,
                                    'cabinet_id' => $cabinet->id,
                                    'sort_order' => $pullout->sort_order ?? 0,
                                ];
                            }

                            // Sort components by sort_order
                            usort($sectionComponents, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

                            // Store components indexed by section_id
                            $this->hierarchyCache['components'][$section->id] = $sectionComponents;

                            $sectionData = [
                                'id' => $section->id,
                                'name' => $section->name ?: $section->section_label ?: "Section #{$section->id}",
                                'section_type' => $section->section_type ?? 'standard',
                                'width' => $section->width_inches,
                                'height' => $section->height_inches,
                                'cabinet_id' => $cabinet->id,
                                'child_count' => count($sectionComponents),
                                'child_ids' => array_column($sectionComponents, 'id'),
                            ];
                            $this->hierarchyCache['sections'][$section->id] = $sectionData;
                        }
                    }
                }
            }
        }

        $this->hierarchyLoaded = true;
    }

    /**
     * Refresh hierarchy cache after CRUD operations
     */
    public function refreshHierarchyCache(): void
    {
        $this->hierarchyLoaded = false;
        $this->hierarchyCache = [];
        $this->loadFullProjectHierarchy();

        // Debug: Log component count for current section
        if ($this->selectedSectionId && isset($this->hierarchyCache['components'][$this->selectedSectionId])) {
            \Illuminate\Support\Facades\Log::info('Hierarchy cache refreshed', [
                'section_id' => $this->selectedSectionId,
                'component_count' => count($this->hierarchyCache['components'][$this->selectedSectionId]),
                'components' => array_column($this->hierarchyCache['components'][$this->selectedSectionId], 'name'),
            ]);
        }
    }

    /**
     * Get items for the current hierarchy level (from cache)
     * Cache is pre-loaded in mount() for instant navigation
     */
    public function getHierarchyItems(): array
    {
        // Return empty if cache not loaded yet (shouldn't happen - cache loaded in mount)
        if (!$this->hierarchyLoaded) {
            return [];
        }

        return match ($this->hierarchyLevel) {
            'rooms' => $this->getCachedRooms(),
            'locations' => $this->getCachedLocations($this->selectedRoomId),
            'runs' => $this->getCachedRuns($this->selectedLocationId),
            'cabinets' => $this->getCachedCabinets($this->selectedRunId),
            'sections' => $this->getCachedSections($this->selectedCabinetId),
            'components' => $this->getCachedComponents($this->selectedSectionId),
            default => []
        };
    }

    /**
     * Get rooms from cache
     */
    protected function getCachedRooms(): array
    {
        return array_values($this->hierarchyCache['rooms'] ?? []);
    }

    /**
     * Get locations from cache for a specific room
     */
    protected function getCachedLocations(?int $roomId): array
    {
        if (!$roomId) return [];

        $room = $this->hierarchyCache['rooms'][$roomId] ?? null;
        if (!$room) return [];

        $locations = [];
        foreach ($room['child_ids'] ?? [] as $locationId) {
            if (isset($this->hierarchyCache['locations'][$locationId])) {
                $locations[] = $this->hierarchyCache['locations'][$locationId];
            }
        }
        return $locations;
    }

    /**
     * Get runs from cache for a specific location
     */
    protected function getCachedRuns(?int $locationId): array
    {
        if (!$locationId) return [];

        $location = $this->hierarchyCache['locations'][$locationId] ?? null;
        if (!$location) return [];

        $runs = [];
        foreach ($location['child_ids'] ?? [] as $runId) {
            if (isset($this->hierarchyCache['runs'][$runId])) {
                $runs[] = $this->hierarchyCache['runs'][$runId];
            }
        }
        return $runs;
    }

    /**
     * Get cabinets from cache for a specific run
     */
    protected function getCachedCabinets(?int $runId): array
    {
        if (!$runId) return [];

        $run = $this->hierarchyCache['runs'][$runId] ?? null;
        if (!$run) return [];

        $cabinets = [];
        foreach ($run['child_ids'] ?? [] as $cabinetId) {
            if (isset($this->hierarchyCache['cabinets'][$cabinetId])) {
                $cabinets[] = $this->hierarchyCache['cabinets'][$cabinetId];
            }
        }
        return $cabinets;
    }

    /**
     * Get sections from cache for a specific cabinet
     */
    protected function getCachedSections(?int $cabinetId): array
    {
        if (!$cabinetId) return [];

        $cabinet = $this->hierarchyCache['cabinets'][$cabinetId] ?? null;
        if (!$cabinet) return [];

        $sections = [];
        foreach ($cabinet['child_ids'] ?? [] as $sectionId) {
            if (isset($this->hierarchyCache['sections'][$sectionId])) {
                $sections[] = $this->hierarchyCache['sections'][$sectionId];
            }
        }
        return $sections;
    }

    /**
     * Get components (doors and drawers) from cache for a specific section
     */
    protected function getCachedComponents(?int $sectionId): array
    {
        if (!$sectionId) return [];

        return $this->hierarchyCache['components'][$sectionId] ?? [];
    }

    /**
     * Get locations for a specific room (DB fallback - kept for compatibility)
     */
    public function getRoomLocations(?int $roomId): array
    {
        // Use cache if available
        if ($this->hierarchyLoaded) {
            return $this->getCachedLocations($roomId);
        }

        if (!$roomId) return [];

        return RoomLocation::where('room_id', $roomId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'type' => $loc->location_type ?? 'wall',
                'sort_order' => $loc->sort_order,
                'child_count' => $loc->cabinetRuns()->count(),
            ])
            ->toArray();
    }

    /**
     * Get cabinet runs for a specific location (uses cache or DB fallback)
     */
    public function getLocationRuns(?int $locationId): array
    {
        // Use cache if available
        if ($this->hierarchyLoaded) {
            return $this->getCachedRuns($locationId);
        }

        if (!$locationId) return [];

        return CabinetRun::where('room_location_id', $locationId)
            ->with('cabinets') // Eager load for accurate LF calculation
            ->orderBy('sort_order')
            ->get()
            ->map(fn($run) => [
                'id' => $run->id,
                'name' => $run->name,
                'type' => $run->run_type ?? 'base',
                'material' => $run->material_type,
                'level' => $run->pricing_level,
                'linear_feet' => $run->linear_feet, // Uses calculated accessor
                'stored_linear_feet' => $run->stored_linear_feet,
                'has_discrepancy' => $run->has_linear_feet_discrepancy,
                'has_missing_measurements' => $run->has_missing_measurements,
                'missing_linear_feet' => $run->missing_linear_feet,
                'cabinets_missing_width_count' => $run->cabinets_missing_width_count,
                'child_count' => $run->cabinets->count(),
            ])
            ->toArray();
    }

    /**
     * Get cabinets for a specific run (uses cache or DB fallback)
     */
    public function getRunCabinets(?int $runId): array
    {
        // Use cache if available
        if ($this->hierarchyLoaded) {
            return $this->getCachedCabinets($runId);
        }

        if (!$runId) return [];

        return Cabinet::where('cabinet_run_id', $runId)
            ->orderBy('position_in_run')
            ->orderBy('id')
            ->get()
            ->map(fn($cab) => [
                'id' => $cab->id,
                'name' => $cab->cabinet_number ?: "Cabinet #{$cab->id}",
                'cabinet_type' => $cab->cabinet_type ?? 'base',
                'width' => $cab->width_inches,
                'linear_feet' => $cab->linear_feet,
                'quantity' => $cab->quantity,
                'price' => $cab->total_price,
                'child_count' => $cab->sections()->count(),
            ])
            ->toArray();
    }

    /**
     * Get sections for a specific cabinet (uses cache or DB fallback)
     */
    public function getCabinetSections(?int $cabinetId): array
    {
        // Use cache if available
        if ($this->hierarchyLoaded) {
            return $this->getCachedSections($cabinetId);
        }

        if (!$cabinetId) return [];

        return CabinetSection::where('cabinet_id', $cabinetId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn($section) => [
                'id' => $section->id,
                'name' => $section->name ?: $section->section_label ?: "Section #{$section->id}",
                'section_type' => $section->section_type ?? 'standard',
                'width' => $section->width_inches,
                'height' => $section->height_inches,
                'child_count' => $section->doors()->count() + $section->drawers()->count() + $section->shelves()->count() + $section->pullouts()->count(),
            ])
            ->toArray();
    }

    /**
     * Get the current level label for display
     */
    public function getHierarchyLevelLabel(): string
    {
        return match ($this->hierarchyLevel) {
            'rooms' => 'Rooms',
            'locations' => 'Locations in ' . ($this->selectedRoomName ?? 'Room'),
            'runs' => 'Cabinet Runs in ' . ($this->selectedLocationName ?? 'Location'),
            'cabinets' => 'Cabinets in ' . ($this->selectedRunName ?? 'Run'),
            'sections' => 'Sections in ' . ($this->selectedCabinetName ?? 'Cabinet'),
            'components' => 'Components in ' . ($this->selectedSectionName ?? 'Section'),
            default => 'Items'
        };
    }

    /**
     * Get the add button label for current level
     */
    public function getAddButtonLabel(): string
    {
        if ($this->hierarchyLevel === 'components') {
            return 'Add ' . ucfirst($this->getComponentTypeForCurrentSection());
        }

        return match ($this->hierarchyLevel) {
            'rooms' => 'Add Room',
            'locations' => 'Add Location',
            'runs' => 'Add Cabinet Run',
            'cabinets' => 'Add Cabinet',
            'sections' => 'Add Section',
            default => 'Add Item'
        };
    }

    /**
     * Get the entity type string for the current hierarchy level
     */
    public function getEntityTypeForLevel(): string
    {
        if ($this->hierarchyLevel === 'components') {
            return $this->getComponentTypeForCurrentSection();
        }

        return match ($this->hierarchyLevel) {
            'rooms' => 'room',
            'locations' => 'room_location',
            'runs' => 'cabinet_run',
            'cabinets' => 'cabinet',
            'sections' => 'section',
            default => 'room'
        };
    }

    /**
     * Get the component type (door/drawer) based on current section's section_type
     */
    protected function getComponentTypeForCurrentSection(): string
    {
        if (!$this->selectedSectionId) {
            return 'door'; // Default
        }

        $section = $this->hierarchyCache['sections'][$this->selectedSectionId] ?? null;
        if (!$section) {
            return 'door'; // Default
        }

        $sectionType = $section['section_type'] ?? 'door';

        // Map section type to component type
        return match ($sectionType) {
            'door' => 'door',
            'drawer_bank' => 'drawer',
            'open_shelf' => 'shelf',
            'appliance' => 'appliance',
            'pullout' => 'pullout',
            'mixed' => 'door', // Default to door for mixed, user can switch
            default => 'door'
        };
    }

    /**
     * Get the inline edit entity type for the current hierarchy level
     * Maps modal entity types to inline edit entity types
     */
    public function getInlineEditTypeForLevel(): string
    {
        if ($this->hierarchyLevel === 'components') {
            return $this->getComponentTypeForCurrentSection();
        }

        return match ($this->hierarchyLevel) {
            'rooms' => 'room',
            'locations' => 'location',
            'runs' => 'run',
            'cabinets' => 'cabinet',
            'sections' => 'section',
            default => 'room'
        };
    }

    /**
     * Get the drill down action string for an item
     */
    public function getDrillDownAction(array $item): string
    {
        $id = $item['id'] ?? 0;

        return match ($this->hierarchyLevel) {
            'rooms' => "drillDownToRoom({$id})",
            'locations' => "drillDownToLocation({$id})",
            'runs' => "drillDownToRun({$id})",
            'cabinets' => "drillDownToCabinet({$id})",
            'sections' => "drillDownToSection({$id})",
            'components' => '', // Components have no children
            default => ''
        };
    }

    /**
     * Open entity creator for the current hierarchy level
     */
    public function openEntityCreatorForCurrentLevel(): void
    {
        $parentId = match ($this->hierarchyLevel) {
            'rooms' => null,
            'locations' => $this->selectedRoomId,
            'runs' => $this->selectedLocationId,
            'cabinets' => $this->selectedRunId,
            'sections' => $this->selectedCabinetId,
            'components' => $this->selectedSectionId,
            default => null
        };

        $entityType = $this->getEntityTypeForLevel();
        $this->openEntityCreator($entityType, $parentId);
    }

    /**
     * Open entity creator for a specific component type
     * Used when at components level to allow adding any component type
     */
    public function openComponentCreator(string $componentType): void
    {
        if (!in_array($componentType, ['door', 'drawer', 'shelf', 'pullout'])) {
            return;
        }

        $this->openEntityCreator($componentType, $this->selectedSectionId);
    }

    /**
     * Check if we're at the components level (for blade template conditionals)
     */
    public function isAtComponentsLevel(): bool
    {
        return $this->hierarchyLevel === 'components';
    }

    /**
     * Get all available component types for sections
     * Returns array with type key and display label
     */
    public function getAvailableComponentTypes(): array
    {
        return [
            'door' => 'Door',
            'drawer' => 'Drawer',
            'shelf' => 'Shelf',
            'pullout' => 'Pullout',
        ];
    }

    /**
     * Get room type options
     */
    public function getRoomTypeOptions(): array
    {
        return [
            'kitchen' => 'Kitchen',
            'bathroom' => 'Bathroom',
            'laundry' => 'Laundry',
            'pantry' => 'Pantry',
            'closet' => 'Closet',
            'mudroom' => 'Mudroom',
            'office' => 'Office',
            'bedroom' => 'Bedroom',
            'living_room' => 'Living Room',
            'dining_room' => 'Dining Room',
            'garage' => 'Garage',
            'basement' => 'Basement',
            'other' => 'Other',
        ];
    }

    /**
     * Get location type options
     */
    public function getLocationTypeOptions(): array
    {
        return [
            'wall' => 'Wall',
            'island' => 'Island',
            'peninsula' => 'Peninsula',
            'corner' => 'Corner',
            'alcove' => 'Alcove',
            'sink_wall' => 'Sink Wall',
            'range_wall' => 'Range Wall',
            'refrigerator_wall' => 'Refrigerator Wall',
        ];
    }

    /**
     * Get cabinet run type options
     */
    public function getRunTypeOptions(): array
    {
        return [
            'base' => 'Base Cabinets',
            'wall' => 'Wall Cabinets',
            'tall' => 'Tall Cabinets',
            'island' => 'Island',
        ];
    }

    /**
     * Get cabinet type options
     */
    public function getCabinetTypeOptions(): array
    {
        return [
            'base' => 'Base',
            'wall' => 'Wall',
            'tall' => 'Tall',
            'vanity' => 'Vanity',
            'specialty' => 'Specialty',
        ];
    }

    /**
     * Get pricing tier options from Products database
     * Pulls from products_attribute_options for "Pricing Level" attribute
     */
    public function getPricingTierOptions(): array
    {
        try {
            $options = \DB::table('products_attribute_options as o')
                ->join('products_attributes as a', 'o.attribute_id', '=', 'a.id')
                ->where('a.name', 'Pricing Level')
                ->orderBy('o.sort')
                ->select('o.name', 'o.extra_price')
                ->get();

            if ($options->isNotEmpty()) {
                $result = [];
                foreach ($options as $opt) {
                    // Extract level number from name like "Level 3 - Enhanced ($192/LF)"
                    if (preg_match('/Level\s*(\d+)/i', $opt->name, $matches)) {
                        $level = $matches[1];
                        $result[$level] = $opt->name;
                    }
                }
                if (!empty($result)) {
                    return $result;
                }
            }
        } catch (\Exception $e) {
            // Fall through to defaults
        }

        // Fallback to hardcoded defaults
        return [
            '1' => 'Level 1 - Basic ($138/LF)',
            '2' => 'Level 2 - Standard ($168/LF)',
            '3' => 'Level 3 - Enhanced ($192/LF)',
            '4' => 'Level 4 - Premium ($210/LF)',
            '5' => 'Level 5 - Custom ($225/LF)',
        ];
    }

    /**
     * Get material category options from Products database
     * Pulls from products_attribute_options for "Material Category" attribute
     */
    public function getMaterialCategoryOptions(): array
    {
        try {
            $options = \DB::table('products_attribute_options as o')
                ->join('products_attributes as a', 'o.attribute_id', '=', 'a.id')
                ->where('a.name', 'Material Category')
                ->orderBy('o.sort')
                ->select('o.name', 'o.extra_price')
                ->get();

            if ($options->isNotEmpty()) {
                $result = [];
                foreach ($options as $opt) {
                    // Create slug key from name
                    $slug = \Illuminate\Support\Str::slug($opt->name, '_');
                    $price = number_format($opt->extra_price ?? 0, 0);
                    $result[$slug] = $opt->name . ($opt->extra_price > 0 ? " (\${$price}/LF)" : '');
                }
                return $result;
            }
        } catch (\Exception $e) {
            // Fall through to defaults
        }

        // Fallback to hardcoded defaults
        return [
            'paint_grade' => 'Paint Grade (Hard Maple/Poplar)',
            'stain_grade' => 'Stain Grade (Oak/Maple)',
            'premium' => 'Premium (Rifted White Oak/Black Walnut)',
            'custom' => 'Custom/Exotic (Price TBD)',
        ];
    }

    /**
     * Get finish options from Products database
     * Pulls from products_attribute_options for "Finish Option" attribute
     */
    public function getFinishOptions(): array
    {
        try {
            $options = \DB::table('products_attribute_options as o')
                ->join('products_attributes as a', 'o.attribute_id', '=', 'a.id')
                ->where('a.name', 'Finish Option')
                ->orderBy('o.sort')
                ->select('o.name', 'o.extra_price')
                ->get();

            if ($options->isNotEmpty()) {
                $result = [];
                foreach ($options as $opt) {
                    // Create slug key from name
                    $slug = \Illuminate\Support\Str::slug($opt->name, '_');
                    $price = number_format($opt->extra_price ?? 0, 0);
                    $result[$slug] = $opt->name . ($opt->extra_price > 0 ? " (+\${$price}/LF)" : '');
                }
                return $result;
            }
        } catch (\Exception $e) {
            // Fall through to defaults
        }

        // Fallback to hardcoded defaults
        return [
            'unfinished' => 'Unfinished',
            'prime_only' => 'Prime Only (+$60/LF)',
            'prime_paint' => 'Prime + Paint (+$118/LF)',
            'clear_coat' => 'Clear Coat (+$95/LF)',
            'stain_clear' => 'Stain + Clear (+$213/LF)',
        ];
    }

    /**
     * Get the Cabinet product ID for linking
     */
    public function getCabinetProductId(): ?int
    {
        try {
            $product = \DB::table('products_products')
                ->where('reference', 'CABINET')
                ->first(['id']);
            return $product?->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get hinge products from inventory for selection
     * Returns products with 'hinge' in name or reference
     */
    public function getHingeProducts(): array
    {
        try {
            $products = \DB::table('products_products')
                ->where(function ($query) {
                    $query->where('name', 'like', '%hinge%')
                        ->orWhere('reference', 'like', '%HINGE%');
                })
                ->orderBy('name')
                ->select('id', 'name', 'reference')
                ->get();

            $result = ['' => '-- Select Hinge --'];
            foreach ($products as $product) {
                $result[$product->id] = $product->name;
            }
            return $result;
        } catch (\Exception $e) {
            return ['' => '-- No hinges available --'];
        }
    }

    /**
     * Get drawer slide products from inventory for selection
     * Returns products with 'slide' in name or reference
     */
    public function getSlideProducts(): array
    {
        try {
            $products = \DB::table('products_products')
                ->where(function ($query) {
                    $query->where('name', 'like', '%slide%')
                        ->orWhere('reference', 'like', '%SLIDE%');
                })
                ->orderBy('name')
                ->select('id', 'name', 'reference')
                ->get();

            $result = ['' => '-- Select Slide --'];
            foreach ($products as $product) {
                $result[$product->id] = $product->name;
            }
            return $result;
        } catch (\Exception $e) {
            return ['' => '-- No slides available --'];
        }
    }

    /**
     * Get all hardware products from inventory for selection
     * Pulls from Cabinet Hardware category (id: 58)
     */
    public function getHardwareProducts(): array
    {
        try {
            $products = \DB::table('products_products')
                ->where('category_id', 58) // Cabinet Hardware category
                ->orderBy('name')
                ->select('id', 'name', 'reference')
                ->get();

            $result = ['' => '-- Select Hardware --'];
            foreach ($products as $product) {
                $result[$product->id] = $product->name;
            }
            return $result;
        } catch (\Exception $e) {
            return ['' => '-- No hardware available --'];
        }
    }

    /**
     * Get decorative hardware products (knobs, pulls, handles)
     * Returns products with 'knob', 'pull', or 'handle' in name/reference
     */
    public function getDecorativeHardwareProducts(): array
    {
        try {
            $products = \DB::table('products_products')
                ->where(function ($query) {
                    $query->where('name', 'like', '%knob%')
                        ->orWhere('name', 'like', '%pull%')
                        ->orWhere('name', 'like', '%handle%')
                        ->orWhere('reference', 'like', '%KNOB%')
                        ->orWhere('reference', 'like', '%PULL%')
                        ->orWhere('reference', 'like', '%HANDLE%');
                })
                ->orderBy('name')
                ->select('id', 'name', 'reference')
                ->get();

            $result = ['' => '-- Select Decorative Hardware --'];
            foreach ($products as $product) {
                $result[$product->id] = $product->name;
            }
            return $result;
        } catch (\Exception $e) {
            return ['' => '-- No decorative hardware available --'];
        }
    }

    /**
     * Get pullout/accessory products (Rev-A-Shelf, trash pullouts, etc.)
     * Returns products with 'pullout', 'rev-a-shelf', 'trash', 'accessory' in name/reference
     */
    public function getPulloutProducts(): array
    {
        try {
            $products = \DB::table('products_products')
                ->where(function ($query) {
                    $query->where('name', 'like', '%pullout%')
                        ->orWhere('name', 'like', '%pull-out%')
                        ->orWhere('name', 'like', '%rev-a-shelf%')
                        ->orWhere('name', 'like', '%trash%')
                        ->orWhere('name', 'like', '%recycl%')
                        ->orWhere('name', 'like', '%lazy susan%')
                        ->orWhere('name', 'like', '%spice%')
                        ->orWhere('reference', 'like', '%PULLOUT%')
                        ->orWhere('reference', 'like', '%REVASHELF%');
                })
                ->orderBy('name')
                ->select('id', 'name', 'reference')
                ->get();

            $result = ['' => '-- Select Pullout Accessory --'];
            foreach ($products as $product) {
                $result[$product->id] = $product->name;
            }
            return $result;
        } catch (\Exception $e) {
            return ['' => '-- No pullout products available --'];
        }
    }

    /**
     * Get door style options for cabinets
     */
    public function getDoorStyleOptions(): array
    {
        return [
            '' => '-- Select Door Style --',
            'shaker' => 'Shaker',
            'raised_panel' => 'Raised Panel',
            'flat_panel' => 'Flat Panel',
            'slab' => 'Slab',
            'beadboard' => 'Beadboard',
            'glass' => 'Glass Front',
            'open' => 'Open (No Door)',
        ];
    }

    /**
     * Get door mounting options
     */
    public function getDoorMountingOptions(): array
    {
        return [
            '' => '-- Select Mounting --',
            'inset' => 'Inset',
            'full_overlay' => 'Full Overlay',
            'half_overlay' => 'Half Overlay',
        ];
    }

    // =====================================================
    // ENTITY DETAILS PANEL METHODS
    // For the third column in Edit Details modal
    // =====================================================

    /**
     * Select/highlight an entity to show in details panel (single-click)
     * This does NOT change navigation - just highlights for details view
     * Auto-loads entity into edit mode for immediate editing
     */
    public function highlightEntity(string $entityType, int $entityId): void
    {
        $this->highlightedEntityType = $entityType;
        $this->highlightedEntityId = $entityId;

        // Auto-load into edit mode for immediate editing
        $this->editHighlightedEntity();
    }

    /**
     * Clear the highlighted entity
     */
    public function clearHighlightedEntity(): void
    {
        $this->highlightedEntityType = null;
        $this->highlightedEntityId = null;
    }

    /**
     * Get details for the currently highlighted entity
     * Returns structured data for the details panel
     */
    public function getSelectedEntityDetails(): ?array
    {
        if (!$this->highlightedEntityType || !$this->highlightedEntityId) {
            return null;
        }

        $entity = match ($this->highlightedEntityType) {
            'room' => Room::with(['locations.cabinetRuns.cabinets'])->find($this->highlightedEntityId),
            'location' => RoomLocation::with(['room', 'cabinetRuns.cabinets'])->find($this->highlightedEntityId),
            'run' => CabinetRun::with(['roomLocation.room', 'cabinets'])->find($this->highlightedEntityId),
            'cabinet' => Cabinet::with(['cabinetRun.roomLocation.room', 'sections'])->find($this->highlightedEntityId),
            'section' => CabinetSection::with(['cabinet.cabinetRun.roomLocation.room'])->find($this->highlightedEntityId),
            'door' => Door::with(['section.cabinet.cabinetRun.roomLocation.room'])->find($this->highlightedEntityId),
            'drawer' => Drawer::with(['section.cabinet.cabinetRun.roomLocation.room'])->find($this->highlightedEntityId),
            'shelf' => Shelf::with(['section.cabinet.cabinetRun.roomLocation.room'])->find($this->highlightedEntityId),
            'pullout' => Pullout::with(['section.cabinet.cabinetRun.roomLocation.room'])->find($this->highlightedEntityId),
            default => null,
        };

        if (!$entity) {
            return null;
        }

        return match ($this->highlightedEntityType) {
            'room' => $this->formatRoomDetails($entity),
            'location' => $this->formatLocationDetails($entity),
            'run' => $this->formatRunDetails($entity),
            'cabinet' => $this->formatCabinetDetails($entity),
            'section' => $this->formatSectionDetails($entity),
            'door' => $this->formatDoorDetails($entity),
            'drawer' => $this->formatDrawerDetails($entity),
            'shelf' => $this->formatShelfDetails($entity),
            'pullout' => $this->formatPulloutDetails($entity),
            default => null,
        };
    }

    /**
     * Format room entity details for display
     */
    protected function formatRoomDetails(Room $room): array
    {
        // Calculate aggregate stats from all cabinets (LF trickles up from cabinet level)
        $totalLinearFeet = 0;
        $totalCabinets = 0;
        $locationCount = $room->locations->count();

        foreach ($room->locations as $location) {
            foreach ($location->cabinetRuns as $run) {
                foreach ($run->cabinets as $cabinet) {
                    $totalLinearFeet += (float) ($cabinet->linear_feet ?? 0);
                    $totalCabinets++;
                }
            }
        }

        // Calculate production days from linear feet
        $productionDays = $this->calculateProductionDays($totalLinearFeet);

        return [
            'id' => $room->id,
            'type' => 'room',
            'name' => $room->name,
            'stats' => [
                'cabinets' => $totalCabinets,
                'linear_feet' => round($totalLinearFeet, 1),
                'production_days' => $productionDays,
            ],
            'fields' => [
                'room_type' => $room->room_type ?? '-',
                'floor_number' => $room->floor_number ?? '-',
                'cabinet_level' => $room->cabinet_level ?? '-',
                'material_category' => $room->material_category ?? '-',
                'finish_option' => $room->finish_option ?? '-',
                'pdf_page_number' => $room->pdf_page_number ?? '-',
                'pdf_room_label' => $room->pdf_room_label ?? '-',
                'pdf_detail_number' => $room->pdf_detail_number ?? '-',
                'sort_order' => $room->sort_order ?? 0,
                'quoted_price' => $room->quoted_price ? '$' . number_format($room->quoted_price, 2) : '-',
                'notes' => $room->notes ?? '-',
            ],
            'children' => [
                'count' => $locationCount,
                'label' => 'Locations',
            ],
            'created_at' => $room->created_at?->format('M d, Y H:i'),
            'updated_at' => $room->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format location entity details for display
     */
    protected function formatLocationDetails(RoomLocation $location): array
    {
        // Calculate stats from cabinets (LF trickles up from cabinet level)
        $totalLinearFeet = 0;
        $totalCabinets = 0;
        $runCount = $location->cabinetRuns->count();

        foreach ($location->cabinetRuns as $run) {
            foreach ($run->cabinets as $cabinet) {
                $totalLinearFeet += (float) ($cabinet->linear_feet ?? 0);
                $totalCabinets++;
            }
        }

        // Calculate production days from linear feet
        $productionDays = $this->calculateProductionDays($totalLinearFeet);

        return [
            'id' => $location->id,
            'type' => 'location',
            'name' => $location->name,
            'parent' => [
                'type' => 'room',
                'id' => $location->room_id,
                'name' => $location->room?->name ?? 'Unknown',
            ],
            'stats' => [
                'cabinets' => $totalCabinets,
                'linear_feet' => round($totalLinearFeet, 1),
                'production_days' => $productionDays,
            ],
            'fields' => [
                'location_type' => $location->location_type ?? '-',
                'sequence' => $location->sequence ?? '-',
                'elevation_reference' => $location->elevation_reference ?? '-',
                'cabinet_level' => $location->cabinet_level ?? '-',
                'material_category' => $location->material_category ?? '-',
                'finish_option' => $location->finish_option ?? '-',
                'sort_order' => $location->sort_order ?? 0,
                'notes' => $location->notes ?? '-',
            ],
            'children' => [
                'count' => $runCount,
                'label' => 'Cabinet Runs',
            ],
            'created_at' => $location->created_at?->format('M d, Y H:i'),
            'updated_at' => $location->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format cabinet run entity details for display
     */
    protected function formatRunDetails(CabinetRun $run): array
    {
        // Calculate stats from cabinets (LF trickles up from cabinet level)
        $totalLinearFeet = 0;
        $cabinetCount = 0;

        foreach ($run->cabinets as $cabinet) {
            $totalLinearFeet += (float) ($cabinet->linear_feet ?? 0);
            $cabinetCount++;
        }

        // Calculate production days from linear feet
        $productionDays = $this->calculateProductionDays($totalLinearFeet);

        return [
            'id' => $run->id,
            'type' => 'run',
            'name' => $run->name,
            'parent' => [
                'type' => 'location',
                'id' => $run->room_location_id,
                'name' => $run->roomLocation?->name ?? 'Unknown',
            ],
            'stats' => [
                'cabinets' => $cabinetCount,
                'linear_feet' => round($totalLinearFeet, 1),
                'production_days' => $productionDays,
            ],
            'fields' => [
                'run_type' => $run->run_type ?? '-',
                'total_linear_feet' => $run->total_linear_feet ? $run->total_linear_feet . ' LF' : '-',
                'start_wall_measurement' => $run->start_wall_measurement ? $run->start_wall_measurement . '"' : '-',
                'end_wall_measurement' => $run->end_wall_measurement ? $run->end_wall_measurement . '"' : '-',
                'cabinet_level' => $run->cabinet_level ?? '-',
                'material_category' => $run->material_category ?? '-',
                'finish_option' => $run->finish_option ?? '-',
                'hinges_count' => $run->hinges_count ?? 0,
                'slides_count' => $run->slides_count ?? 0,
                'sort_order' => $run->sort_order ?? 0,
                'notes' => $run->notes ?? '-',
            ],
            'children' => [
                'count' => $cabinetCount,
                'label' => 'Cabinets',
            ],
            'created_at' => $run->created_at?->format('M d, Y H:i'),
            'updated_at' => $run->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format cabinet entity details for display
     */
    protected function formatCabinetDetails(Cabinet $cabinet): array
    {
        // Use the stored linear_feet value from cabinet (source of truth for LF)
        $linearFeet = round((float) ($cabinet->linear_feet ?? 0), 2);

        // Calculate production days from linear feet
        $productionDays = $this->calculateProductionDays($linearFeet);

        return [
            'id' => $cabinet->id,
            'type' => 'cabinet',
            'name' => $cabinet->name,
            'parent' => [
                'type' => 'run',
                'id' => $cabinet->cabinet_run_id,
                'name' => $cabinet->cabinetRun?->name ?? 'Unknown',
            ],
            'stats' => [
                'linear_feet' => $linearFeet,
                'production_days' => $productionDays,
            ],
            'fields' => [
                'cabinet_number' => $cabinet->cabinet_number ?? '-',
                'position_in_run' => $cabinet->position_in_run ?? '-',
                'wall_position_start' => $cabinet->wall_position_start_inches ? $cabinet->wall_position_start_inches . '"' : '-',
                'length_inches' => $cabinet->length_inches ? $cabinet->length_inches . '"' : '-',
                'width_inches' => $cabinet->width_inches ? $cabinet->width_inches . '"' : '-',
                'depth_inches' => $cabinet->depth_inches ? $cabinet->depth_inches . '"' : '-',
                'height_inches' => $cabinet->height_inches ? $cabinet->height_inches . '"' : '-',
                'linear_feet' => $cabinet->linear_feet ? $cabinet->linear_feet . ' LF' : '-',
                'quantity' => $cabinet->quantity ?? 1,
                'cabinet_level' => $cabinet->cabinet_level ?? '-',
                'material_category' => $cabinet->material_category ?? '-',
                'finish_option' => $cabinet->finish_option ?? '-',
                'unit_price_per_lf' => $cabinet->unit_price_per_lf ? '$' . number_format($cabinet->unit_price_per_lf, 2) : '-',
                'total_price' => $cabinet->total_price ? '$' . number_format($cabinet->total_price, 2) : '-',
                'hardware_notes' => $cabinet->hardware_notes ?? '-',
                'custom_modifications' => $cabinet->custom_modifications ?? '-',
                'shop_notes' => $cabinet->shop_notes ?? '-',
            ],
            'children' => [
                'count' => 0, // Cabinets don't have sections/components in current implementation
                'label' => 'Components',
            ],
            'created_at' => $cabinet->created_at?->format('M d, Y H:i'),
            'updated_at' => $cabinet->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format section entity details for display
     */
    protected function formatSectionDetails(CabinetSection $section): array
    {
        return [
            'id' => $section->id,
            'type' => 'section',
            'name' => $section->name ?? 'Section ' . $section->section_number,
            'parent' => [
                'type' => 'cabinet',
                'id' => $section->cabinet_id,
                'name' => $section->cabinet?->name ?? 'Unknown Cabinet',
            ],
            'stats' => [],
            'fields' => [
                'section_type' => ucfirst($section->section_type ?? '-'),
                'section_number' => $section->section_number ?? '-',
                'width' => $section->width_inches ? $section->width_inches . '"' : '-',
                'height' => $section->height_inches ? $section->height_inches . '"' : '-',
                'position_from_left' => $section->position_from_left_inches ? $section->position_from_left_inches . '"' : '-',
                'position_from_bottom' => $section->position_from_bottom_inches ? $section->position_from_bottom_inches . '"' : '-',
                'component_count' => $section->component_count ?? '-',
                'notes' => $section->notes ?? '-',
            ],
            'children' => [
                'count' => $section->doors()->count() + $section->drawers()->count() + $section->shelves()->count() + $section->pullouts()->count(),
                'label' => 'Components',
            ],
            'created_at' => $section->created_at?->format('M d, Y H:i'),
            'updated_at' => $section->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format door entity details for display
     */
    protected function formatDoorDetails(Door $door): array
    {
        return [
            'id' => $door->id,
            'type' => 'door',
            'name' => $door->door_name ?? 'Door ' . $door->door_number,
            'parent' => [
                'type' => 'section',
                'id' => $door->section_id,
                'name' => $door->section?->name ?? 'Unknown Section',
            ],
            'stats' => [],
            'fields' => [
                'door_number' => $door->door_number ?? '-',
                'width' => $door->width_inches ? $door->width_inches . '"' : '-',
                'height' => $door->height_inches ? $door->height_inches . '"' : '-',
                'hinge_side' => ucfirst($door->hinge_side ?? '-'),
                'has_glass' => $door->has_glass ? 'Yes' : 'No',
                'finish_type' => $door->finish_type ?? '-',
                'notes' => $door->notes ?? '-',
            ],
            'children' => [
                'count' => 0,
                'label' => 'None',
            ],
            'created_at' => $door->created_at?->format('M d, Y H:i'),
            'updated_at' => $door->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format drawer entity details for display
     */
    protected function formatDrawerDetails(Drawer $drawer): array
    {
        return [
            'id' => $drawer->id,
            'type' => 'drawer',
            'name' => $drawer->drawer_name ?? 'Drawer ' . $drawer->drawer_number,
            'parent' => [
                'type' => 'section',
                'id' => $drawer->section_id,
                'name' => $drawer->section?->name ?? 'Unknown Section',
            ],
            'stats' => [],
            'fields' => [
                'drawer_number' => $drawer->drawer_number ?? '-',
                'width' => $drawer->width_inches ? $drawer->width_inches . '"' : '-',
                'height' => $drawer->height_inches ? $drawer->height_inches . '"' : '-',
                'depth' => $drawer->depth_inches ? $drawer->depth_inches . '"' : '-',
                'drawer_box_type' => $drawer->drawer_box_type ?? '-',
                'soft_close' => $drawer->soft_close ? 'Yes' : 'No',
                'notes' => $drawer->notes ?? '-',
            ],
            'children' => [
                'count' => 0,
                'label' => 'None',
            ],
            'created_at' => $drawer->created_at?->format('M d, Y H:i'),
            'updated_at' => $drawer->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format shelf entity details for display
     */
    protected function formatShelfDetails(Shelf $shelf): array
    {
        return [
            'id' => $shelf->id,
            'type' => 'shelf',
            'name' => $shelf->shelf_name ?? 'Shelf ' . $shelf->shelf_number,
            'parent' => [
                'type' => 'section',
                'id' => $shelf->section_id,
                'name' => $shelf->section?->name ?? 'Unknown Section',
            ],
            'stats' => [],
            'fields' => [
                'shelf_number' => $shelf->shelf_number ?? '-',
                'width' => $shelf->width_inches ? $shelf->width_inches . '"' : '-',
                'depth' => $shelf->depth_inches ? $shelf->depth_inches . '"' : '-',
                'thickness' => $shelf->thickness_inches ? $shelf->thickness_inches . '"' : '-',
                'is_adjustable' => $shelf->is_adjustable ? 'Yes' : 'No',
                'position_from_bottom' => $shelf->position_from_bottom_inches ? $shelf->position_from_bottom_inches . '"' : '-',
                'notes' => $shelf->notes ?? '-',
            ],
            'children' => [
                'count' => 0,
                'label' => 'None',
            ],
            'created_at' => $shelf->created_at?->format('M d, Y H:i'),
            'updated_at' => $shelf->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Format pullout entity details for display
     */
    protected function formatPulloutDetails(Pullout $pullout): array
    {
        return [
            'id' => $pullout->id,
            'type' => 'pullout',
            'name' => $pullout->pullout_name ?? 'Pullout ' . $pullout->pullout_number,
            'parent' => [
                'type' => 'section',
                'id' => $pullout->section_id,
                'name' => $pullout->section?->name ?? 'Unknown Section',
            ],
            'stats' => [],
            'fields' => [
                'pullout_number' => $pullout->pullout_number ?? '-',
                'pullout_type' => ucfirst($pullout->pullout_type ?? '-'),
                'width' => $pullout->width_inches ? $pullout->width_inches . '"' : '-',
                'height' => $pullout->height_inches ? $pullout->height_inches . '"' : '-',
                'depth' => $pullout->depth_inches ? $pullout->depth_inches . '"' : '-',
                'load_capacity_lbs' => $pullout->load_capacity_lbs ? $pullout->load_capacity_lbs . ' lbs' : '-',
                'notes' => $pullout->notes ?? '-',
            ],
            'children' => [
                'count' => 0,
                'label' => 'None',
            ],
            'created_at' => $pullout->created_at?->format('M d, Y H:i'),
            'updated_at' => $pullout->updated_at?->format('M d, Y H:i'),
        ];
    }

    /**
     * Get the default company's shop capacity per day
     *
     * @return float The shop capacity in linear feet per day (defaults to 20 if not set)
     */
    protected function getShopCapacityPerDay(): float
    {
        $company = Company::where('is_default', true)->first();

        if ($company && $company->shop_capacity_per_day > 0) {
            return (float) $company->shop_capacity_per_day;
        }

        // Default to 20 LF per day if not configured
        return 20.0;
    }

    /**
     * Calculate production days from linear feet based on shop capacity
     *
     * @param float $linearFeet Total linear feet
     * @return float Production days (rounded to 1 decimal place)
     */
    protected function calculateProductionDays(float $linearFeet): float
    {
        $capacity = $this->getShopCapacityPerDay();

        if ($capacity <= 0) {
            return 0;
        }

        return round($linearFeet / $capacity, 1);
    }

    /**
     * Delete an entity from the details panel
     */
    public function deleteHighlightedEntity(): void
    {
        if (!$this->highlightedEntityType || !$this->highlightedEntityId) {
            Notification::make()
                ->title('No Entity Selected')
                ->body('Please select an entity to delete.')
                ->warning()
                ->send();
            return;
        }

        $entityType = $this->highlightedEntityType;
        $entityId = $this->highlightedEntityId;

        // Delete the entity
        $deleted = match ($entityType) {
            'room' => Room::find($entityId)?->delete(),
            'location' => RoomLocation::find($entityId)?->delete(),
            'run' => CabinetRun::find($entityId)?->delete(),
            'cabinet' => Cabinet::find($entityId)?->delete(),
            default => false,
        };

        if ($deleted) {
            Notification::make()
                ->title('Entity Deleted')
                ->body(ucfirst($entityType) . ' has been deleted successfully.')
                ->success()
                ->send();

            // Clear selection
            $this->clearHighlightedEntity();
        } else {
            Notification::make()
                ->title('Delete Failed')
                ->body('Could not delete the entity.')
                ->danger()
                ->send();
        }
    }

    /**
     * Edit the highlighted entity (switches to inline edit mode)
     */
    public function editHighlightedEntity(): void
    {
        if (!$this->highlightedEntityType || !$this->highlightedEntityId) {
            Notification::make()
                ->title('No Entity Selected')
                ->body('Please select an entity to edit.')
                ->warning()
                ->send();
            return;
        }

        // Load entity data into inline edit form
        $entity = match ($this->highlightedEntityType) {
            'room' => Room::find($this->highlightedEntityId),
            'location' => RoomLocation::find($this->highlightedEntityId),
            'run' => CabinetRun::find($this->highlightedEntityId),
            'cabinet' => Cabinet::find($this->highlightedEntityId),
            'section' => CabinetSection::find($this->highlightedEntityId),
            'door' => Door::find($this->highlightedEntityId),
            'drawer' => Drawer::find($this->highlightedEntityId),
            'shelf' => Shelf::find($this->highlightedEntityId),
            'pullout' => Pullout::find($this->highlightedEntityId),
            default => null,
        };

        if (!$entity) {
            Notification::make()
                ->title('Entity Not Found')
                ->danger()
                ->send();
            return;
        }

        // Populate inline edit data based on entity type
        $this->inlineEditData = match ($this->highlightedEntityType) {
            'room' => [
                'name' => $entity->name,
                'room_type' => $entity->room_type,
                'floor_number' => $entity->floor_number ?? 1,
                'pdf_page_number' => $entity->pdf_page_number,
                'pdf_room_label' => $entity->pdf_room_label,
                'pdf_detail_number' => $entity->pdf_detail_number,
                'sort_order' => $entity->sort_order ?? 0,
                'cabinet_level' => $entity->cabinet_level,
                'material_category' => $entity->material_category,
                'finish_option' => $entity->finish_option,
                'quoted_price' => $entity->quoted_price,
                'notes' => $entity->notes ?? '',
            ],
            'location' => [
                'name' => $entity->name,
                'location_type' => $entity->location_type ?? 'wall',
                'sequence' => $entity->sequence,
                'sort_order' => $entity->sort_order ?? 0,
                'elevation_reference' => $entity->elevation_reference,
                'cabinet_level' => $entity->cabinet_level,
                'material_category' => $entity->material_category,
                'finish_option' => $entity->finish_option,
                'notes' => $entity->notes ?? '',
            ],
            'run' => [
                'name' => $entity->name,
                'run_type' => $entity->run_type ?? 'base',
                'total_linear_feet' => $entity->total_linear_feet,
                'start_wall_measurement' => $entity->start_wall_measurement,
                'end_wall_measurement' => $entity->end_wall_measurement,
                'cabinet_level' => $entity->cabinet_level,
                'sort_order' => $entity->sort_order ?? 0,
                'hinges_count' => $entity->hinges_count ?? 0,
                'material_category' => $entity->material_category,
                'finish_option' => $entity->finish_option,
                'notes' => $entity->notes ?? '',
            ],
            'cabinet' => [
                'cabinet_number' => $entity->cabinet_number,
                'position_in_run' => $entity->position_in_run,
                'length_inches' => $entity->length_inches,
                'height_inches' => $entity->height_inches,
                'depth_inches' => $entity->depth_inches,
                'quantity' => $entity->quantity ?? 1,
                'wall_position_start_inches' => $entity->wall_position_start_inches,
                'cabinet_level' => $entity->cabinet_level,
                'material_category' => $entity->material_category,
                'finish_option' => $entity->finish_option,
                'unit_price_per_lf' => $entity->unit_price_per_lf,
                'total_price' => $entity->total_price,
                // Door/Drawer Configuration
                'door_style' => $entity->door_style ?? '',
                'door_mounting' => $entity->door_mounting ?? '',
                'door_count' => $entity->door_count ?? 0,
                'drawer_count' => $entity->drawer_count ?? 0,
                // Hardware from Products
                'hinge_product_id' => $entity->hinge_product_id ?? '',
                'hinge_quantity' => $entity->hinge_quantity ?? 0,
                'slide_product_id' => $entity->slide_product_id ?? '',
                'slide_quantity' => $entity->slide_quantity ?? 0,
                // Notes
                'hardware_notes' => $entity->hardware_notes ?? '',
                'shop_notes' => $entity->shop_notes ?? '',
            ],
            'section' => [
                'name' => $entity->name ?? '',
                'section_type' => $entity->section_type ?? 'door',
                'width_inches' => $entity->width_inches,
                'height_inches' => $entity->height_inches,
                'position_from_left_inches' => $entity->position_from_left_inches,
                'position_from_bottom_inches' => $entity->position_from_bottom_inches,
                'component_count' => $entity->component_count,
                'notes' => $entity->notes ?? '',
            ],
            'door' => [
                'door_number' => $entity->door_number ?? '',
                'door_name' => $entity->door_name ?? '',
                'width_inches' => $entity->width_inches,
                'height_inches' => $entity->height_inches,
                'hinge_side' => $entity->hinge_side ?? 'left',
                'has_glass' => $entity->has_glass ?? false,
                'finish_type' => $entity->finish_type ?? '',
                'notes' => $entity->notes ?? '',
                'hinge_product_id' => $entity->hinge_product_id ?? '',
                'decorative_hardware_product_id' => $entity->decorative_hardware_product_id ?? '',
            ],
            'drawer' => [
                'drawer_number' => $entity->drawer_number ?? '',
                'drawer_name' => $entity->drawer_name ?? '',
                'front_width_inches' => $entity->front_width_inches,
                'front_height_inches' => $entity->front_height_inches,
                'drawer_position' => $entity->drawer_position ?? 1,
                'slide_type' => $entity->slide_type ?? '',
                'soft_close' => $entity->soft_close ?? true,
                'finish_type' => $entity->finish_type ?? '',
                'notes' => $entity->notes ?? '',
                'slide_product_id' => $entity->slide_product_id ?? '',
                'decorative_hardware_product_id' => $entity->decorative_hardware_product_id ?? '',
                'drawer_box_width_inches' => $entity->drawer_box_width_inches,
                'drawer_box_height_inches' => $entity->drawer_box_height_inches,
                'drawer_box_depth_inches' => $entity->drawer_box_depth_inches,
            ],
            'shelf' => [
                'shelf_number' => $entity->shelf_number ?? '',
                'shelf_name' => $entity->shelf_name ?? '',
                'width_inches' => $entity->width_inches,
                'depth_inches' => $entity->depth_inches,
                'thickness_inches' => $entity->thickness_inches,
                'shelf_type' => $entity->shelf_type ?? 'adjustable',
                'material' => $entity->material ?? 'plywood',
                'edge_treatment' => $entity->edge_treatment ?? '',
                'finish_type' => $entity->finish_type ?? '',
                'notes' => $entity->notes ?? '',
                'slide_product_id' => $entity->slide_product_id ?? '',
            ],
            'pullout' => [
                'pullout_number' => $entity->pullout_number ?? '',
                'pullout_name' => $entity->pullout_name ?? '',
                'pullout_type' => $entity->pullout_type ?? 'roll_out_tray',
                'manufacturer' => $entity->manufacturer ?? '',
                'model_number' => $entity->model_number ?? '',
                'width_inches' => $entity->width_inches,
                'height_inches' => $entity->height_inches,
                'depth_inches' => $entity->depth_inches,
                'soft_close' => $entity->soft_close ?? true,
                'quantity' => $entity->quantity ?? 1,
                'notes' => $entity->notes ?? '',
                'product_id' => $entity->product_id ?? '',
                'slide_product_id' => $entity->slide_product_id ?? '',
            ],
            default => [],
        };

        $this->isEditingInline = true;
    }

    /**
     * Cancel inline editing
     */
    public function cancelInlineEdit(): void
    {
        $this->isEditingInline = false;
        $this->inlineEditData = [];
    }

    /**
     * Save inline edits
     */
    public function saveInlineEdit(): void
    {
        if (!$this->highlightedEntityType || !$this->highlightedEntityId) {
            return;
        }

        $entity = match ($this->highlightedEntityType) {
            'room' => Room::find($this->highlightedEntityId),
            'location' => RoomLocation::find($this->highlightedEntityId),
            'run' => CabinetRun::find($this->highlightedEntityId),
            'cabinet' => Cabinet::find($this->highlightedEntityId),
            'section' => CabinetSection::find($this->highlightedEntityId),
            'door' => Door::find($this->highlightedEntityId),
            'drawer' => Drawer::find($this->highlightedEntityId),
            'shelf' => Shelf::find($this->highlightedEntityId),
            'pullout' => Pullout::find($this->highlightedEntityId),
            default => null,
        };

        if (!$entity) {
            Notification::make()
                ->title('Entity Not Found')
                ->danger()
                ->send();
            return;
        }

        // Update entity with inline edit data
        $entity->fill($this->inlineEditData);
        $entity->save();

        // Refresh hierarchy cache after inline edit
        $this->refreshHierarchyCache();

        // Exit edit mode
        $this->isEditingInline = false;
        $this->inlineEditData = [];

        // Refresh the entity tree by rebuilding rooms data
        $existingRooms = $this->buildExistingRoomsData();
        $this->data['rooms'] = $existingRooms;

        Notification::make()
            ->title('Saved')
            ->body(ucfirst($this->highlightedEntityType) . ' updated successfully.')
            ->success()
            ->send();
    }
}
