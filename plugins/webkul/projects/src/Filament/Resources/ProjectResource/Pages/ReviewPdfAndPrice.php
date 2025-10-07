<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use App\Models\PdfDocument;
use App\Services\PdfParsingService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
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

        $this->form->fill([
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
                    Step::make('Assign Pages to Rooms')
                        ->description('Review PDF pages and organize them by room')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('pdf_info')
                                ->label('Document Information')
                                ->content(fn () => "**{$this->pdfDocument->file_name}** — Total Pages: {$this->getTotalPages()}")
                                ->columnSpanFull(),

                            Repeater::make('page_assignments')
                                ->label('Page Assignments')
                                ->schema([
                                    TextInput::make('room_name')
                                        ->label('Room Name')
                                        ->required()
                                        ->placeholder('e.g., Kitchen, Pantry, Master Bathroom')
                                        ->live()
                                        ->columnSpan(1),

                                    Select::make('pages')
                                        ->label('PDF Pages')
                                        ->multiple()
                                        ->options(function () {
                                            $pages = [];
                                            for ($i = 1; $i <= $this->getTotalPages(); $i++) {
                                                $pages[$i] = "Page {$i}";
                                            }
                                            return $pages;
                                        })
                                        ->placeholder('Select which pages show this room')
                                        ->required()
                                        ->columnSpan(1),

                                    \Filament\Forms\Components\Textarea::make('notes')
                                        ->label('Room Notes')
                                        ->placeholder('Any special details about this room...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(1)
                                ->reorderable()
                                ->collapsible()
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

        // Step 1: Save page assignments as Room records
        if (!empty($data['page_assignments'])) {
            $sortOrder = 1;
            foreach ($data['page_assignments'] as $assignment) {
                // Create room record for each page assignment
                Room::create([
                    'project_id' => $this->record->id,
                    'name' => $assignment['room_name'],
                    'pdf_page_number' => !empty($assignment['pages']) ? min($assignment['pages']) : null,
                    'pdf_notes' => !empty($assignment['notes']) ? $assignment['notes'] : null,
                    'notes' => !empty($assignment['notes']) ? $assignment['notes'] : null,
                    'sort_order' => $sortOrder++,
                    'creator_id' => Auth::id(),
                ]);
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
