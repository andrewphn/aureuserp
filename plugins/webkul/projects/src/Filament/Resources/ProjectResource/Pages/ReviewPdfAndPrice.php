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
use Webkul\Project\Models\ProjectDraft;

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

    public $pdfDocument;

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
        ])->toArray();
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
                                        ->live(),

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

        // Open the modal using Filament's dispatch method
        $this->dispatch('open-modal', id: 'edit-details-modal');
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

        // CRITICAL: Also update the form state to prevent saveDraft() from overwriting
        // When Livewire triggers saveDraft() elsewhere, it uses $this->form->getState()
        // which would return the old state without our changes
        $this->form->fill($formData);

        // Save the draft with updated data directly
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
                    'country_id' => 233, // USA
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
            if (! empty($doorData['door_id'])) {
                $door = \DB::table('projects_doors')->where('id', $doorData['door_id'])->first();
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
                \DB::table('projects_doors')->insert(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'door_number' => \DB::table('projects_doors')->where('cabinet_id', $cabinet->id)->count() + 1,
                    'sort_order' => \DB::table('projects_doors')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['doors']++;
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
            if (! empty($drawerData['drawer_id'])) {
                $drawer = \DB::table('projects_drawers')->where('id', $drawerData['drawer_id'])->first();
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
                \DB::table('projects_drawers')->insert(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'drawer_number' => \DB::table('projects_drawers')->where('cabinet_id', $cabinet->id)->count() + 1,
                    'sort_order' => \DB::table('projects_drawers')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['drawers']++;
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
            if (! empty($pulloutData['pullout_id'])) {
                $pullout = \DB::table('projects_pullouts')->where('id', $pulloutData['pullout_id'])->first();
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
                \DB::table('projects_pullouts')->insert(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'sort_order' => \DB::table('projects_pullouts')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['pullouts']++;
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
            if (! empty($shelfData['shelf_id'])) {
                $shelf = \DB::table('projects_shelves')->where('id', $shelfData['shelf_id'])->first();
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
                \DB::table('projects_shelves')->insert(array_merge($data, [
                    'cabinet_id' => $cabinet->id,
                    'section_id' => $section->id,
                    'sort_order' => \DB::table('projects_shelves')->where('section_id', $section->id)->count(),
                    'created_at' => now(),
                ]));
                $stats['shelves']++;
            }
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
}
