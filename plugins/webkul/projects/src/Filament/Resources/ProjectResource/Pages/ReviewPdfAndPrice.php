<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use App\Models\PdfDocument;
use App\Services\PdfParsingService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public function mount(int|string $record): void
    {
        // Resolve the Project model using InteractsWithRecord trait
        $this->record = $this->resolveRecord($record);

        $pdfId = request()->get('pdf');
        $this->pdfDocument = PdfDocument::findOrFail($pdfId);

        // Check if PDF file actually exists
        if (!Storage::disk('public')->exists($this->pdfDocument->file_path)) {
            Notification::make()
                ->title('PDF File Not Found')
                ->body('The PDF file "' . $this->pdfDocument->file_name . '" is missing from storage. Please re-upload it.')
                ->danger()
                ->persistent()
                ->send();
        }

        // Pre-fill page metadata for each page
        $pageMetadata = [];
        for ($i = 1; $i <= $this->getTotalPages(); $i++) {
            $pageMetadata[] = [
                'page_number' => $i,
                'rooms' => [
                    [
                        'room_number' => '',
                        'room_type' => '',
                        'room_id' => null,
                    ]
                ],
                'detail_number' => '',
                'notes' => '',
            ];
        }

        $this->form->fill([
            'page_metadata' => $pageMetadata,
            'rooms' => [],
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
                                ->content(fn () => "**{$this->pdfDocument->file_name}** — Total Pages: {$this->getTotalPages()}")
                                ->columnSpanFull(),

                            Repeater::make('page_metadata')
                                ->label('Pages')
                                ->schema([
                                    \Filament\Schemas\Components\View::make('webkul-project::filament.components.pdf-page-thumbnail-serverside')
                                        ->viewData(fn ($get) => [
                                            'pdfId' => $this->pdfDocument->id,
                                            'pdfDocument' => $this->pdfDocument,
                                            'pageNumber' => $get('page_number') ?? 1,
                                            'pdfPageId' => \App\Models\PdfPage::where('pdf_document_id', $this->pdfDocument->id)
                                                ->where('page_number', $get('page_number') ?? 1)
                                                ->first()->id ?? null,
                                            'itemKey' => 'page-' . ($get('page_number') ?? 1),
                                        ])
                                        ->key(fn ($get) => 'thumbnail-page-' . ($get('page_number') ?? 1))
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
                                                ->options(function () {
                                                    // Get predefined options
                                                    $predefined = [
                                                        'cover_page' => 'Cover Page',
                                                        'floor_plan' => 'Floor Plan',
                                                        'elevation' => 'Elevation',
                                                        'section' => 'Section',
                                                        'detail' => 'Detail',
                                                        'schedule' => 'Schedule',
                                                        'site_plan' => 'Site Plan',
                                                        'rendering' => 'Rendering',
                                                        'other' => 'Other',
                                                    ];

                                                    // Get unique page types from database
                                                    $existingTypes = \App\Models\PdfPage::whereNotNull('page_type')
                                                        ->distinct()
                                                        ->pluck('page_type', 'page_type')
                                                        ->toArray();

                                                    return array_merge($predefined, $existingTypes);
                                                })
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

                                                            TextInput::make('cover_customer_address')
                                                                ->label('Customer Address')
                                                                ->default(function () {
                                                                    $partner = $this->record->partner;
                                                                    if (!$partner) return '';
                                                                    $address = collect([
                                                                        $partner->street1,
                                                                        $partner->street2,
                                                                        $partner->city,
                                                                        $partner->state?->name,
                                                                        $partner->zip,
                                                                    ])->filter()->implode(', ');
                                                                    return $address;
                                                                })
                                                                ->placeholder('Customer address')
                                                                ->columnSpanFull(),

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

                                                                        return !empty($parts) ? implode(', ', $parts) : '';
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
                                                ->visible(fn ($get) => $get('page_type') === 'cover_page')
                                                ->columnSpanFull(),

                                            Repeater::make('rooms')
                                                ->label('Rooms on this Page')
                                                ->visible(fn ($get) => $get('page_type') !== 'cover_page')
                                                ->schema([
                                                    Select::make('room_type')
                                                        ->label('Room Type')
                                                        ->required()
                                                        ->options([
                                                            'kitchen' => 'Kitchen',
                                                            'bathroom' => 'Bathroom',
                                                            'bedroom' => 'Bedroom',
                                                            'pantry' => 'Pantry',
                                                            'laundry' => 'Laundry',
                                                            'office' => 'Office',
                                                            'closet' => 'Closet',
                                                            'mudroom' => 'Mudroom',
                                                            'dining_room' => 'Dining Room',
                                                            'living_room' => 'Living Room',
                                                            'family_room' => 'Family Room',
                                                            'entryway' => 'Entryway',
                                                            'hallway' => 'Hallway',
                                                            'other' => 'Other',
                                                        ])
                                                        ->searchable()
                                                        ->placeholder('Select room type')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            // Check if this room type already exists in other pages
                                                            $allPages = $this->form->getState()['page_metadata'] ?? [];
                                                            $currentPageNum = $get('../../page_number');

                                                            $existingRoomTypes = [];
                                                            foreach ($allPages as $page) {
                                                                if ($page['page_number'] != $currentPageNum && !empty($page['rooms'])) {
                                                                    foreach ($page['rooms'] as $room) {
                                                                        if (!empty($room['room_type'])) {
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
                                                                    $room->id => ($room->room_number ?? $room->name) . ' - ' . ($room->room_type ?? 'Unknown Type')
                                                                ])
                                                                ->toArray();
                                                        })
                                                        ->searchable()
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
                                ->itemLabel(fn ($state) => 'Page ' . ($state['page_number'] ?? ''))
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
                                        ->searchable()
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
        if (!$this->record->partner_id) {
            Notification::make()
                ->title('No Customer Assigned')
                ->body('Please assign a customer to this project before creating a sales order.')
                ->danger()
                ->send();
            return;
        }

        $now = now();

        // Step 1: Save page metadata to pdf_pages table
        if (!empty($data['page_metadata'])) {
            foreach ($data['page_metadata'] as $pageMeta) {
                // Create page record for each page (even if metadata is minimal)
                $pageRecord = \App\Models\PdfPage::create([
                    'pdf_document_id' => $this->pdfDocument->id,
                    'page_number' => $pageMeta['page_number'] ?? null,
                    'page_type' => $pageMeta['page_type'] ?? null,
                    'detail_number' => $pageMeta['detail_number'] ?? null,
                    'notes' => $pageMeta['notes'] ?? null,
                    'creator_id' => Auth::id(),
                ]);

                // Create room associations for this page
                if (!empty($pageMeta['rooms'])) {
                    foreach ($pageMeta['rooms'] as $roomData) {
                        \App\Models\PdfPageRoom::create([
                            'pdf_page_id' => $pageRecord->id,
                            'room_id' => $roomData['room_id'] ?? null,
                            'room_number' => $roomData['room_number'] ?? null,
                            'room_type' => $roomData['room_type'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Create sales order
        $salesOrderId = \DB::table('sales_orders')->insertGetId([
            'project_id' => $this->record->id,
            'partner_id' => $this->record->partner_id,
            'partner_invoice_id' => $this->record->partner_id,
            'partner_shipping_id' => $this->record->partner_id,
            'company_id' => $this->record->company_id ?? 1,
            'state' => 'draft',
            'invoice_status' => 'no',
            'date_order' => $now,
            'currency_id' => 1,
            'creator_id' => auth()->id() ?? 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $subtotal = 0;
        $lineNumber = 1;

        // Process rooms and cabinet runs
        if (!empty($data['rooms'])) {
            foreach ($data['rooms'] as $room) {
                $roomName = $room['room_name'];

                if (!empty($room['cabinet_runs'])) {
                    foreach ($room['cabinet_runs'] as $run) {
                        $level = (int) $run['cabinet_level'];
                        $linearFeet = (float) $run['linear_feet'];

                        // Get Cabinet product with pricing level
                        $cabinetProduct = $this->getCabinetProduct($level);

                        if ($cabinetProduct) {
                            $lineTotal = $linearFeet * $cabinetProduct['unit_price'];
                            $subtotal += $lineTotal;

                            $lineName = "Cabinet - {$roomName} - {$run['run_name']} (Level {$level})";
                            if (!empty($run['notes'])) {
                                $lineName .= "\nNotes: {$run['notes']}";
                            }

                            \DB::table('sales_order_lines')->insert([
                                'order_id' => $salesOrderId,
                                'product_id' => $cabinetProduct['product_id'],
                                'name' => $lineName,
                                'sort' => $lineNumber++,
                                'product_uom_qty' => $linearFeet,
                                'price_unit' => $cabinetProduct['unit_price'],
                                'price_subtotal' => $lineTotal,
                                'qty_delivered' => 0,
                                'qty_to_invoice' => $linearFeet,
                                'qty_invoiced' => 0,
                                'creator_id' => auth()->id() ?? 1,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            }
        }

        // Process additional items
        if (!empty($data['additional_items'])) {
            foreach ($data['additional_items'] as $item) {
                $product = \DB::table('products_products')
                    ->where('id', $item['product_id'])
                    ->first(['id', 'name', 'price']);

                if ($product) {
                    $quantity = (float) $item['quantity'];
                    $lineTotal = $quantity * $product->price;
                    $subtotal += $lineTotal;

                    $lineName = $product->name;
                    if (!empty($item['notes'])) {
                        $lineName .= "\nNotes: {$item['notes']}";
                    }

                    \DB::table('sales_order_lines')->insert([
                        'order_id' => $salesOrderId,
                        'product_id' => $product->id,
                        'name' => $lineName,
                        'sort' => $lineNumber++,
                        'product_uom_qty' => $quantity,
                        'price_unit' => $product->price,
                        'price_subtotal' => $lineTotal,
                        'qty_delivered' => 0,
                        'qty_to_invoice' => $quantity,
                        'qty_invoiced' => 0,
                        'creator_id' => auth()->id() ?? 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Update sales order totals
        \DB::table('sales_orders')
            ->where('id', $salesOrderId)
            ->update([
                'amount_untaxed' => $subtotal,
                'amount_total' => $subtotal,
                'updated_at' => $now,
            ]);

        Notification::make()
            ->title('Sales Order Created')
            ->body("Sales order created successfully with " . ($lineNumber - 1) . " line items. Total: $" . number_format($subtotal, 2))
            ->success()
            ->send();

        $this->redirect(ProjectResource::getUrl('view', ['record' => $this->record]));
    }

    protected function getCabinetProduct(int $level = 2): ?array
    {
        $product = \DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first(['id', 'name', 'price']);

        if (!$product) {
            return null;
        }

        $pricingLevelAttr = \DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first(['id']);

        if (!$pricingLevelAttr) {
            return null;
        }

        $levelOption = \DB::table('products_attribute_options')
            ->where('attribute_id', $pricingLevelAttr->id)
            ->where('name', 'LIKE', "Level {$level}%")
            ->first(['id', 'name', 'extra_price']);

        if (!$levelOption) {
            return null;
        }

        $unitPrice = floatval($product->price) + floatval($levelOption->extra_price);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $unitPrice,
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
                if (!$lineItem['product_id']) {
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
                    if (!empty($lineItem['attribute_selections'])) {
                        foreach ($lineItem['attribute_selections'] as $attr) {
                            if ($attr['attribute_name'] === 'Pricing Level') {
                                preg_match('/Level (\d)/', $attr['option_name'], $matches);
                                if (!empty($matches[1])) {
                                    $level = (int) $matches[1];
                                }
                            }
                        }
                    }

                    // Add as cabinet run in "Auto-Parsed" room
                    if (!isset($rooms['Auto-Parsed'])) {
                        $rooms['Auto-Parsed'] = [
                            'room_name' => 'Auto-Parsed Items',
                            'cabinet_runs' => [],
                        ];
                    }

                    $rooms['Auto-Parsed']['cabinet_runs'][] = [
                        'run_name' => $lineItem['raw_name'],
                        'cabinet_level' => (string) $level,
                        'linear_feet' => $lineItem['quantity'],
                        'notes' => 'Automatically extracted from PDF',
                    ];
                } else {
                    // Non-cabinet items go to additional_items
                    $additionalItems[] = [
                        'product_id' => $lineItem['product_id'],
                        'quantity' => $lineItem['quantity'],
                        'notes' => 'Automatically extracted from PDF: ' . $lineItem['raw_name'],
                    ];
                }
            }

            // Convert rooms array to form format
            $formRooms = array_values($rooms);

            $this->form->fill([
                'rooms' => $formRooms,
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
}
