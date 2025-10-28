<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use App\Models\PdfDocument;
use App\Services\PdfParsingService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

        // Pre-fill page metadata for each page
        $pageMetadata = [];
        for ($i = 1; $i <= $this->getTotalPages(); $i++) {
            $pageMetadata[] = [
                'page_number' => $i,
                'rooms'       => [
                    [
                        'room_number' => '',
                        'room_type'   => '',
                        'room_id'     => null,
                    ],
                ],
                'detail_number' => '',
                'notes'         => '',
            ];
        }

        $this->form->fill([
            'page_metadata' => $pageMetadata,
            'rooms'         => [],
        ]);
    }

    public function getTotalPages(): int
    {
        return $this->pdfDocument->page_count ?? 1;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Page Metadata')
                        ->description('Fill in metadata for each PDF page')
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
                                })
                                ->columnSpanFull(),

                            Repeater::make('page_metadata')
                                ->label('Pages')
                                ->schema([
                                    \Filament\Schemas\Components\View::make('webkul-project::filament.components.pdf-page-thumbnail-pdfjs')
                                        ->viewData(fn ($get) => [
                                            'pdfId'       => $this->pdfDocument->id,
                                            'pdfDocument' => $this->pdfDocument,
                                            'pdfUrl'      => \Illuminate\Support\Facades\Storage::disk('public')->url($this->pdfDocument->file_path),
                                            'pageNumber'  => $get('page_number') ?? 1,
                                            'pdfPageId'   => $this->getPdfPageId($get('page_number') ?? 1),
                                            'pdfPage'     => $this->getPdfPageId($get('page_number') ?? 1) ? \App\Models\PdfPage::find($this->getPdfPageId($get('page_number') ?? 1)) : null,
                                            'itemKey'     => 'page-'.($get('page_number') ?? 1),
                                        ])
                                        ->key(fn ($get) => 'thumbnail-page-'.($get('page_number') ?? 1))
                                        ->columnSpan(1),

                                    \Filament\Schemas\Components\Section::make()
                                        ->schema([
                                            \Filament\Forms\Components\TextInput::make('page_number')
                                                ->label('Page')
                                                ->disabled()
                                                ->dehydrated()
                                                ->prefix('Page'),

                                            Select::make('page_type')
                                                ->label('Page Type')
                                                ->options([
                                                    'cover_page' => 'Cover Page',
                                                    'floor_plan' => 'Floor Plan',
                                                    'elevation'  => 'Elevation',
                                                    'section'    => 'Section',
                                                    'detail'     => 'Detail',
                                                    'schedule'   => 'Schedule',
                                                    'site_plan'  => 'Site Plan',
                                                    'rendering'  => 'Rendering',
                                                    'other'      => 'Other',
                                                ])
                                                ->searchable()
                                                ->allowHtml()
                                                ->getSearchResultsUsing(fn (string $search) => [
                                                    $search => "Create: {$search}",
                                                ])
                                                ->getOptionLabelUsing(fn ($value): string => ucwords(str_replace('_', ' ', $value)))
                                                ->placeholder('Select or type to create new')
                                                ->helperText('Type to create a custom page type')
                                                ->live(),

                                            \Filament\Schemas\Components\Section::make('Cover Page Information')
                                                ->description('Edit the information that will appear on the cover page')
                                                ->schema([
                                                    \Filament\Schemas\Components\Section::make('Customer Details')
                                                        ->schema([
                                                            TextInput::make('cover_customer_name')
                                                                ->label('Customer Name')
                                                                ->default(fn () => $this->record->partner->name ?? '')
                                                                ->placeholder('Customer name')
                                                                ->columnSpanFull(),

                                                            TextInput::make('cover_customer_address.street1')
                                                                ->label('Street Address')
                                                                ->default(fn () => $this->record->partner->street1 ?? '')
                                                                ->placeholder('Street address')
                                                                ->columnSpanFull(),

                                                            TextInput::make('cover_customer_address.street2')
                                                                ->label('Street Address Line 2')
                                                                ->default(fn () => $this->record->partner->street2 ?? '')
                                                                ->placeholder('Apt, suite, etc.')
                                                                ->columnSpanFull(),

                                                            TextInput::make('cover_customer_address.city')
                                                                ->label('City')
                                                                ->default(fn () => $this->record->partner->city ?? '')
                                                                ->placeholder('City'),

                                                            Select::make('cover_customer_address.country_id')
                                                                ->label('Country')
                                                                ->options(\Webkul\Support\Models\Country::pluck('name', 'id'))
                                                                ->default(fn () => $this->record->partner->country_id ?? 1)
                                                                ->searchable()
                                                                ->preload()
                                                                ->live()
                                                                ->afterStateUpdated(fn (callable $set) => $set('cover_customer_address.state_id', null)),

                                                            Select::make('cover_customer_address.state_id')
                                                                ->label('State')
                                                                ->options(function (callable $get) {
                                                                    $countryId = $get('cover_customer_address.country_id');
                                                                    if (! $countryId) {
                                                                        return [];
                                                                    }

                                                                    return \Webkul\Support\Models\State::where('country_id', $countryId)
                                                                        ->pluck('name', 'id');
                                                                })
                                                                ->default(fn () => $this->record->partner->state_id ?? null)
                                                                ->searchable()
                                                                ->preload(),

                                                            TextInput::make('cover_customer_address.zip')
                                                                ->label('Zip Code')
                                                                ->default(fn () => $this->record->partner->zip ?? '')
                                                                ->placeholder('Zip code'),

                                                            TextInput::make('cover_customer_phone')
                                                                ->label('Phone')
                                                                ->default(fn () => $this->record->partner->phone ?? '')
                                                                ->placeholder('Phone number')
                                                                ->tel(),

                                                            TextInput::make('cover_customer_email')
                                                                ->label('Email')
                                                                ->default(fn () => $this->record->partner->email ?? '')
                                                                ->placeholder('Email address')
                                                                ->email(),
                                                        ])
                                                        ->columns(2),

                                                    \Filament\Schemas\Components\Section::make('Project Details')
                                                        ->schema([
                                                            TextInput::make('cover_project_number')
                                                                ->label('Project Number')
                                                                ->default(fn () => $this->record->project_number ?? '')
                                                                ->placeholder('Project number'),

                                                            TextInput::make('cover_project_name')
                                                                ->label('Project Name')
                                                                ->default(fn () => $this->record->name ?? '')
                                                                ->placeholder('Project name'),

                                                            TextInput::make('cover_project_address')
                                                                ->label('Project Address')
                                                                ->default(function () {
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

                                                                        return ! empty($parts) ? implode(', ', $parts) : '';
                                                                    }

                                                                    return '';
                                                                })
                                                                ->placeholder('Project address')
                                                                ->columnSpanFull(),

                                                            TextInput::make('cover_project_date')
                                                                ->label('Date')
                                                                ->default(fn () => $this->record->created_at?->format('F d, Y') ?? now()->format('F d, Y'))
                                                                ->placeholder('Date'),
                                                        ])
                                                        ->columns(2),
                                                ])
                                                ->collapsed()
                                                ->columnSpanFull(),

                                            Repeater::make('rooms')
                                                ->label('Rooms on this Page')
                                                // Temporarily always visible - page_type field disabled
                                                ->schema([
                                                    Select::make('room_type')
                                                        ->label('Room Type')
                                                        ->required()
                                                        ->options([
                                                            'kitchen'     => 'Kitchen',
                                                            'bathroom'    => 'Bathroom',
                                                            'bedroom'     => 'Bedroom',
                                                            'pantry'      => 'Pantry',
                                                            'laundry'     => 'Laundry',
                                                            'office'      => 'Office',
                                                            'closet'      => 'Closet',
                                                            'mudroom'     => 'Mudroom',
                                                            'dining_room' => 'Dining Room',
                                                            'living_room' => 'Living Room',
                                                            'family_room' => 'Family Room',
                                                            'entryway'    => 'Entryway',
                                                            'hallway'     => 'Hallway',
                                                            'other'       => 'Other',
                                                        ])
                                                        ->native(false)
                                                        ->placeholder('Select room type')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            // Check if this room type already exists in other pages
                                                            $allPages = $this->form->getState()['page_metadata'] ?? [];
                                                            $currentPageNum = $get('../../page_number');

                                                            $existingRoomTypes = [];
                                                            foreach ($allPages as $page) {
                                                                if ($page['page_number'] != $currentPageNum && ! empty($page['rooms'])) {
                                                                    foreach ($page['rooms'] as $room) {
                                                                        if (! empty($room['room_type'])) {
                                                                            $existingRoomTypes[] = $room['room_type'];
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            // Room number will be auto-calculated later
                                                        }),

                                                    TextInput::make('room_number')
                                                        ->label('Room Number')
                                                        ->placeholder('Auto-calculated or enter manually')
                                                        ->helperText('Will be auto-numbered based on room occurrences'),

                                                    Select::make('room_id')
                                                        ->label('Link to Project Room')
                                                        ->options(function () {
                                                            return Room::where('project_id', $this->record->id)
                                                                ->get()
                                                                ->mapWithKeys(fn ($room) => [
                                                                    $room->id => ($room->room_number ?? $room->name).' - '.($room->room_type ?? 'Unknown Type'),
                                                                ])
                                                                ->toArray();
                                                        })
                                                        ->native(false)
                                                        ->placeholder('Optional: Link to existing project room')
                                                        ->helperText('Leave blank to create new room later'),
                                                ])
                                                ->columns(3)
                                                ->defaultItems(1)
                                                ->collapsible()
                                                ->itemLabel(fn ($state) => ucwords(str_replace('_', ' ', $state['room_type'] ?? 'New Room')))
                                                ->addActionLabel('Add Another Room')
                                                ->columnSpanFull(),

                                            TextInput::make('detail_number')
                                                ->label('Detail/Drawing Number')
                                                ->placeholder('e.g., A-101, D-3'),

                                            \Filament\Forms\Components\Textarea::make('notes')
                                                ->label('Notes')
                                                ->placeholder('Special details about this page...')
                                                ->rows(2)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2)
                                        ->columnSpan(1),
                                ])
                                ->columns(2)
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false)
                                ->collapsible()
                                ->itemLabel(fn ($state) => 'Page '.($state['page_number'] ?? ''))
                                ->columnSpanFull(),
                        ]),

                    Step::make('Enter Pricing Details')
                        ->description('Add cabinet runs and linear feet for each room')
                        ->schema([
                            Repeater::make('rooms')
                                ->label('Rooms & Cabinet Runs')
                                ->schema([
                                    TextInput::make('room_name')
                                        ->label('Room Name')
                                        ->required()
                                        ->placeholder('e.g., Kitchen, Pantry, Bathroom'),

                                    Repeater::make('cabinet_runs')
                                        ->label('Cabinet Runs in this Room')
                                        ->schema([
                                            TextInput::make('run_name')
                                                ->label('Run Name')
                                                ->required()
                                                ->placeholder('e.g., Sink Wall, Pantry Wall, Island'),

                                            Select::make('cabinet_level')
                                                ->label('Cabinet Level')
                                                ->options([
                                                    '1' => 'Level 1 - Basic ($138/LF)',
                                                    '2' => 'Level 2 - Standard ($168/LF)',
                                                    '3' => 'Level 3 - Enhanced ($192/LF)',
                                                    '4' => 'Level 4 - Premium ($210/LF)',
                                                    '5' => 'Level 5 - Custom ($225/LF)',
                                                ])
                                                ->default('2')
                                                ->required(),

                                            TextInput::make('linear_feet')
                                                ->label('Linear Feet')
                                                ->numeric()
                                                ->required()
                                                ->step(0.25)
                                                ->suffix('LF'),

                                            TextInput::make('notes')
                                                ->label('Notes')
                                                ->placeholder('Material, finish, special details...')
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(3)
                                        ->defaultItems(1)
                                        ->reorderable()
                                        ->collapsible(),
                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->reorderable()
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                    Step::make('Additional Items')
                        ->description('Add countertops, shelves, or other non-cabinet items')
                        ->schema([
                            Repeater::make('additional_items')
                                ->label('Additional Items')
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
                        ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function getPdfUrl(): string
    {
        return Storage::disk('public')->url($this->pdfDocument->file_path);
    }

    public function nextPage(): void
    {
        $this->currentPage++;
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

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
    public function useAnnotationSystemV2(): bool
    {
        return $this->annotationSystemVersion() === 'v2';
    }
}
