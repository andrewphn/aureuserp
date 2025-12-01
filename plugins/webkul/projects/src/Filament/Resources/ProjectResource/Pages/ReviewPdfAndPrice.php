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
use Filament\Notifications\Actions\Action;
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
            $this->form->fill($this->draft->form_data);
        } else {
            // Fresh start - build initial page metadata
            $coverPageData = $this->buildCoverPageData();

            $pageMetadata = [];
            for ($i = 1; $i <= $this->getTotalPages(); $i++) {
                $pageData = [
                    'page_number' => $i,
                    'page_type' => null, // User will classify each page
                    'rooms' => [], // User will assign rooms to pages
                    'detail_number' => '',
                    'notes' => '',
                ];

                // Add cover page data to all pages (so any can be set as cover)
                $pageData = array_merge($pageData, $coverPageData);
                $pageMetadata[] = $pageData;
            }

            // Start with empty rooms - user will extract/create them from PDF
            $this->form->fill([
                'page_metadata' => $pageMetadata,
                'rooms' => [],
            ]);
        }
    }

    /**
     * Build rooms data from existing project rooms
     *
     * @return array
     */
    protected function buildExistingRoomsData(): array
    {
        $rooms = Room::where('project_id', $this->record->id)
            ->with(['locations.cabinetRuns'])
            ->ordered()
            ->get();

        if ($rooms->isEmpty()) {
            return [];
        }

        $roomsData = [];
        foreach ($rooms as $room) {
            $cabinetRuns = [];

            // Collect cabinet runs from all locations in this room
            foreach ($room->locations as $location) {
                foreach ($location->cabinetRuns as $run) {
                    $cabinetRuns[] = [
                        'cabinet_run_id' => $run->id,
                        'run_name' => $run->name ?? $location->name . ' - ' . $run->run_type,
                        'cabinet_level' => $run->cabinet_level ?? '2',
                        'linear_feet' => $run->total_linear_feet ?? 0,
                        'notes' => $run->notes ?? '',
                    ];
                }
            }

            // If room has no cabinet runs yet, add one empty slot
            if (empty($cabinetRuns)) {
                $cabinetRuns[] = [
                    'cabinet_run_id' => null,
                    'run_name' => '',
                    'cabinet_level' => '2',
                    'linear_feet' => '',
                    'notes' => '',
                ];
            }

            $roomsData[] = [
                'room_id' => $room->id,
                'room_name' => $room->name ?? ucwords(str_replace('_', ' ', $room->room_type)),
                'room_type' => $room->room_type,
                'cabinet_runs' => $cabinetRuns,
            ];
        }

        return $roomsData;
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
     */
    protected function getStep1Schema(): array
    {
        return [
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
                })
                ->columnSpanFull(),

            Repeater::make('page_metadata')
                ->label('Pages')
                ->schema([
                    // PDF Thumbnail with annotation capability
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

                    // Page metadata section
                    \Filament\Schemas\Components\Section::make('Page Details')
                        ->schema([
                            TextInput::make('page_number')
                                ->label('Page')
                                ->disabled()
                                ->dehydrated()
                                ->prefix('Page'),

                            Select::make('page_type')
                                ->label('Page Type')
                                ->options([
                                    'cover_page'        => 'Cover Page',
                                    'plan_view'         => 'Plan View',
                                    'elevation'         => 'Elevation',
                                    'section'           => 'Section',
                                    'detail'            => 'Detail',
                                    'countertops'       => 'Countertops',
                                    'hardware_schedule' => 'Hardware Schedule',
                                    'schedule'          => 'Schedule',
                                    'site_plan'         => 'Site Plan',
                                    'rendering'         => 'Rendering',
                                    'other'             => 'Other',
                                ])
                                ->searchable()
                                ->placeholder('Select or type to create new')
                                ->helperText('Type to create a custom page type')
                                ->live(),

                            TextInput::make('detail_number')
                                ->label('Drawing Number')
                                ->placeholder('e.g., A-101, D-3'),

                            \Filament\Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Notes about this page...')
                                ->rows(2),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->collapsible()
                ->itemLabel(fn ($state) => 'Page '.($state['page_number'] ?? ''))
                ->columnSpanFull(),
        ];
    }

    /**
     * Step 2: Define Rooms & Cabinet Runs
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
                    return "➕ No rooms defined yet. Add rooms and cabinet runs below.";
                })
                ->columnSpanFull(),

            Repeater::make('rooms')
                ->label('Rooms & Cabinet Runs')
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

                    Repeater::make('cabinet_runs')
                        ->label('Cabinet Runs in this Room')
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('cabinet_run_id'),

                            TextInput::make('run_name')
                                ->label('Run Name')
                                ->required()
                                ->placeholder('e.g., Sink Wall, Pantry Wall, Island'),

                            Select::make('cabinet_level')
                                ->label('Cabinet Level')
                                ->options(fn () => $this->getPricingLevelOptions())
                                ->default('2')
                                ->required()
                                ->native(false),

                            TextInput::make('linear_feet')
                                ->label('Linear Feet')
                                ->numeric()
                                ->required()
                                ->step(0.25)
                                ->suffix('LF')
                                ->live(onBlur: true),

                            TextInput::make('notes')
                                ->label('Notes')
                                ->placeholder('Material, finish, special details...')
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

        // Step 1: Save page metadata to pdf_pages table
        // Page records should already exist from PDF upload
        // Room associations can be added later if needed via pdf_page_rooms table
        // Temporarily disabled this section - pdf_pages table structure simplified

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

        // Try to get cabinet products from database
        $products = \Webkul\Product\Models\Product::where('is_published', true)
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
     * Save draft state for the PDF review wizard
     *
     * @return void
     */
    public function saveDraft(): void
    {
        $sessionId = 'pdf-review-' . $this->record->id . '-' . $this->pdf;
        $formData = $this->form->getState();

        if ($this->draft) {
            $this->draft->update([
                'form_data' => $formData,
                'expires_at' => now()->addDays(7),
            ]);
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

        Notification::make()
            ->success()
            ->title('Draft Saved')
            ->body('Your progress has been saved.')
            ->duration(2000)
            ->send();
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
     * Save rooms and cabinet runs to database (without creating sales order)
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

        $savedRooms = 0;
        $savedRuns = 0;

        foreach ($data['rooms'] as $roomData) {
            // Check if room already exists
            $room = null;
            if (! empty($roomData['room_id'])) {
                $room = Room::find($roomData['room_id']);
            }

            if ($room) {
                // Update existing room
                $room->update([
                    'name' => $roomData['room_name'],
                    'room_type' => $roomData['room_type'] ?? $room->room_type,
                ]);
            } else {
                // Create new room
                $room = Room::create([
                    'project_id' => $this->record->id,
                    'name' => $roomData['room_name'],
                    'room_type' => $roomData['room_type'] ?? 'other',
                    'creator_id' => auth()->id(),
                ]);
                $savedRooms++;
            }

            // Create default location for room if none exists
            $location = $room->locations()->first();
            if (! $location) {
                $location = RoomLocation::create([
                    'room_id' => $room->id,
                    'name' => 'Main',
                    'location_type' => 'wall',
                    'sequence' => 1,
                    'creator_id' => auth()->id(),
                ]);
            }

            // Save cabinet runs
            foreach ($roomData['cabinet_runs'] ?? [] as $runData) {
                if (empty($runData['run_name']) && empty($runData['linear_feet'])) {
                    continue; // Skip empty runs
                }

                $run = null;
                if (! empty($runData['cabinet_run_id'])) {
                    $run = CabinetRun::find($runData['cabinet_run_id']);
                }

                if ($run) {
                    // Update existing run
                    $run->update([
                        'name' => $runData['run_name'],
                        'cabinet_level' => $runData['cabinet_level'] ?? '2',
                        'total_linear_feet' => (float) ($runData['linear_feet'] ?? 0),
                        'notes' => $runData['notes'] ?? null,
                    ]);
                } else {
                    // Create new run
                    CabinetRun::create([
                        'room_location_id' => $location->id,
                        'name' => $runData['run_name'],
                        'cabinet_level' => $runData['cabinet_level'] ?? '2',
                        'total_linear_feet' => (float) ($runData['linear_feet'] ?? 0),
                        'notes' => $runData['notes'] ?? null,
                        'creator_id' => auth()->id(),
                    ]);
                    $savedRuns++;
                }
            }
        }

        // Update project total linear feet
        $totalLf = CabinetRun::whereHas('roomLocation.room', fn ($q) => $q->where('project_id', $this->record->id))
            ->sum('total_linear_feet');

        $this->record->update([
            'estimated_linear_feet' => $totalLf,
        ]);

        Notification::make()
            ->success()
            ->title('Rooms & Cabinets Saved')
            ->body("Saved {$savedRooms} new room(s) and {$savedRuns} new cabinet run(s). Total: {$totalLf} LF")
            ->send();
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
}
