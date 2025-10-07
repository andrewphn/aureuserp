<?php

namespace App\Filament\Resources\PdfDocumentResource\Pages;

use App\Filament\Resources\PdfDocumentResource;
use App\Models\PdfDocument;
use App\Models\Partner;
use App\Models\Project;
use App\Models\ProjectAddress;
use App\Models\State;
use App\Models\Country;
use App\Models\CabinetSpecification;
use App\Services\PdfDataExtractor;
use App\Services\ProductMatcher;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReviewExtractedData extends Page
{
    protected static string $resource = PdfDocumentResource::class;

    protected static string $view = 'filament.resources.pdf-document-resource.pages.review-extracted-data';

    public PdfDocument $record;
    public ?array $extractedData = null;
    public ?array $customerMatches = [];
    public ?int $selectedCustomerId = null;
    public bool $createNewCustomer = false;

    public function mount(PdfDocument $record): void
    {
        $this->record = $record;

        // Extract metadata
        $extractor = app(PdfDataExtractor::class);
        $this->extractedData = $extractor->extractMetadata($record);

        // Find potential customer matches
        $this->findCustomerMatches();
    }

    /**
     * Format phone number to (XXX) XXX-XXXX format
     */
    protected function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle 10-digit US numbers
        if (strlen($phone) == 10) {
            return sprintf("(%s) %s-%s",
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        }

        // Handle 11-digit numbers (with country code)
        if (strlen($phone) == 11 && $phone[0] == '1') {
            return sprintf("(%s) %s-%s",
                substr($phone, 1, 3),
                substr($phone, 4, 3),
                substr($phone, 7, 4)
            );
        }

        // Return as-is if not standard format
        return $phone;
    }

    /**
     * Parse address into street1 and street2
     */
    protected function parseStreetAddress(?string $fullAddress): array
    {
        if (empty($fullAddress)) {
            return ['street1' => null, 'street2' => null];
        }

        // Simple parsing - split on comma, newline, or "Unit"/"Apt"/"Suite"
        $parts = preg_split('/[,\n]|(?=Unit|Apt|Ste|Suite)/i', $fullAddress, 2);

        return [
            'street1' => trim($parts[0] ?? ''),
            'street2' => isset($parts[1]) ? trim($parts[1]) : null
        ];
    }

    /**
     * Find potential matching customers
     */
    protected function findCustomerMatches(): void
    {
        $clientData = $this->extractedData['client'] ?? [];

        if (empty($clientData)) {
            return;
        }

        $query = Partner::query()->where('sub_type', 'customer');

        $matches = [];

        // Search by email (exact match)
        if (!empty($clientData['email']['value'] ?? null)) {
            $emailMatches = (clone $query)
                ->where('email', $clientData['email']['value'])
                ->get();

            foreach ($emailMatches as $match) {
                $matches[$match->id] = [
                    'partner' => $match,
                    'match_type' => 'Email Match',
                    'confidence' => 100,
                ];
            }
        }

        // Search by phone (exact match)
        if (!empty($clientData['phone']['value'] ?? null)) {
            $phone = preg_replace('/[^0-9]/', '', $clientData['phone']['value']);
            $phoneMatches = (clone $query)
                ->where('phone', 'LIKE', '%' . $phone . '%')
                ->get();

            foreach ($phoneMatches as $match) {
                if (!isset($matches[$match->id])) {
                    $matches[$match->id] = [
                        'partner' => $match,
                        'match_type' => 'Phone Match',
                        'confidence' => 90,
                    ];
                }
            }
        }

        // Search by name or company (fuzzy match)
        $searchName = $clientData['name']['value'] ?? $clientData['company']['value'] ?? null;
        if (!empty($searchName)) {
            $nameMatches = (clone $query)
                ->where(function ($q) use ($searchName) {
                    $q->where('name', 'LIKE', '%' . $searchName . '%')
                      ->orWhere('company_registry', 'LIKE', '%' . $searchName . '%');
                })
                ->get();

            foreach ($nameMatches as $match) {
                if (!isset($matches[$match->id])) {
                    $matches[$match->id] = [
                        'partner' => $match,
                        'match_type' => 'Name Match',
                        'confidence' => 70,
                    ];
                }
            }
        }

        // Sort by confidence
        uasort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        $this->customerMatches = $matches;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Extracted Project Information')
                    ->description('Review and edit the automatically extracted data before saving')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('project_name')
                                    ->label('Project Name')
                                    ->default($this->getProjectName())
                                    ->required(),

                                Forms\Components\Select::make('project_type')
                                    ->label('Project Type')
                                    ->options([
                                        'Kitchen Cabinetry' => 'Kitchen Cabinetry',
                                        'Bathroom Cabinetry' => 'Bathroom Cabinetry',
                                        'Office Cabinetry' => 'Office Cabinetry',
                                        'Custom Millwork' => 'Custom Millwork',
                                    ])
                                    ->default($this->extractedData['project']['type'] ?? 'Kitchen Cabinetry')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('street1')
                                    ->label('Street Address Line 1')
                                    ->default($this->parseStreetAddress($this->extractedData['project']['street_address'] ?? null)['street1']),

                                Forms\Components\TextInput::make('street2')
                                    ->label('Street Address Line 2')
                                    ->default($this->parseStreetAddress($this->extractedData['project']['street_address'] ?? null)['street2']),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->default($this->extractedData['project']['city'] ?? null),

                                Forms\Components\Select::make('state_id')
                                    ->label('State')
                                    ->options(State::pluck('name', 'id'))
                                    ->searchable()
                                    ->default($this->getStateId()),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('zip')
                                    ->label('ZIP Code')
                                    ->default($this->extractedData['project']['zip'] ?? null),

                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Project Start Date')
                                    ->default($this->getRevisionDate()),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('tier_2_lf')
                                    ->label('Tier 2 Linear Feet')
                                    ->numeric()
                                    ->default($this->getTierLinearFeet(2))
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('tier_4_lf')
                                    ->label('Tier 4 Linear Feet')
                                    ->numeric()
                                    ->default($this->getTierLinearFeet(4))
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('estimated_linear_feet')
                                    ->label('Total Linear Feet')
                                    ->numeric()
                                    ->default($this->getTotalLinearFeet())
                                    ->suffix('LF'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Project Notes')
                            ->default($this->getProjectDescription())
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Customer Information')
                    ->description('Match with existing customer or create new')
                    ->schema([
                        Forms\Components\Radio::make('customer_action')
                            ->label('Customer')
                            ->options($this->getCustomerOptions())
                            ->default($this->getDefaultCustomerOption())
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state === 'new') {
                                    $this->createNewCustomer = true;
                                    $this->selectedCustomerId = null;
                                } else {
                                    $this->createNewCustomer = false;
                                    $this->selectedCustomerId = (int) $state;
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Name')
                                    ->default(
                                        $this->extractedData['client']['name']['value']
                                        ?? $this->extractedData['client']['company']['value']
                                        ?? null
                                    )
                                    ->required()
                                    ->visible(fn($get) => $get('customer_action') === 'new'),

                                Forms\Components\TextInput::make('customer_email')
                                    ->label('Email')
                                    ->email()
                                    ->default($this->extractedData['client']['email']['value'] ?? null)
                                    ->visible(fn($get) => $get('customer_action') === 'new'),

                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->default($this->formatPhoneNumber($this->extractedData['client']['phone']['value'] ?? null))
                                    ->visible(fn($get) => $get('customer_action') === 'new'),

                                Forms\Components\TextInput::make('customer_website')
                                    ->label('Website')
                                    ->url()
                                    ->default($this->extractedData['client']['website']['value'] ?? null)
                                    ->visible(fn($get) => $get('customer_action') === 'new'),

                                Forms\Components\TextInput::make('customer_company')
                                    ->label('Company')
                                    ->default($this->extractedData['client']['company']['value'] ?? null)
                                    ->visible(fn($get) => $get('customer_action') === 'new'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getCustomerOptions(): array
    {
        $options = [];

        foreach ($this->customerMatches as $id => $match) {
            $partner = $match['partner'];
            $matchType = $match['match_type'];
            $confidence = $match['confidence'];

            $label = "{$partner->name} ({$partner->email})";
            $label .= " - {$matchType} ({$confidence}% confidence)";

            $options[$id] = $label;
        }

        $options['new'] = '+ Create New Customer';

        return $options;
    }

    protected function getDefaultCustomerOption(): string
    {
        // If we have a high-confidence match (100%), auto-select it
        foreach ($this->customerMatches as $id => $match) {
            if ($match['confidence'] >= 100) {
                return (string) $id;
            }
        }

        // Otherwise default to creating new
        return 'new';
    }

    protected function getProjectName(): string
    {
        $parts = [];

        // Use client name, or company name as fallback
        $clientName = $this->extractedData['client']['name']['value']
            ?? $this->extractedData['client']['company']['value']
            ?? null;

        if (!empty($clientName)) {
            $parts[] = $clientName;
        }

        if (!empty($this->extractedData['project']['type'])) {
            $parts[] = $this->extractedData['project']['type'];
        }

        if (!empty($this->extractedData['project']['address'])) {
            $parts[] = 'at ' . $this->extractedData['project']['address'];
        }

        return !empty($parts) ? implode(' - ', $parts) : 'Untitled Project';
    }

    protected function getStateId(): ?int
    {
        if (!empty($this->extractedData['project']['state'])) {
            $state = State::where('code', $this->extractedData['project']['state'])->first();
            return $state?->id;
        }
        return null;
    }

    protected function getRevisionDate(): ?string
    {
        if (!empty($this->extractedData['document']['revisions'])) {
            $lastRevision = end($this->extractedData['document']['revisions']);
            if (!empty($lastRevision['date'])) {
                try {
                    return Carbon::createFromFormat('n/j/y', $lastRevision['date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
        }
        return null;
    }

    protected function getTierLinearFeet(int $tierNumber): ?float
    {
        $tiers = $this->extractedData['measurements']['tiers'] ?? [];
        foreach ($tiers as $tier) {
            $num = $tier['tier']['value'] ?? $tier['tier'];
            if ($num == $tierNumber) {
                return $tier['linear_feet']['value'] ?? $tier['linear_feet'];
            }
        }
        return null;
    }

    protected function getTotalLinearFeet(): ?float
    {
        $total = 0;
        $measurements = $this->extractedData['measurements'] ?? [];

        if (!empty($measurements['tiers'])) {
            foreach ($measurements['tiers'] as $tier) {
                $lf = $tier['linear_feet']['value'] ?? $tier['linear_feet'];
                $total += (float) $lf;
            }
        }

        if (!empty($measurements['floating_shelves_lf'])) {
            $lf = $measurements['floating_shelves_lf']['value'] ?? $measurements['floating_shelves_lf'];
            $total += (float) $lf;
        }

        return $total > 0 ? $total : null;
    }

    protected function getProjectDescription(): string
    {
        $parts = [];

        if (!empty($this->extractedData['document']['drawn_by'])) {
            $parts[] = "Drawn by: {$this->extractedData['document']['drawn_by']}";
        }

        if (!empty($this->extractedData['document']['revisions'])) {
            $revCount = count($this->extractedData['document']['revisions']);
            $parts[] = "Revision {$revCount}";
        }

        $tiers = $this->extractedData['measurements']['tiers'] ?? [];
        if (!empty($tiers)) {
            $tierInfo = [];
            foreach ($tiers as $tier) {
                $num = $tier['tier']['value'] ?? $tier['tier'];
                $lf = $tier['linear_feet']['value'] ?? $tier['linear_feet'];
                $tierInfo[] = "Tier {$num}: {$lf} LF";
            }
            if (!empty($tierInfo)) {
                $parts[] = implode(', ', $tierInfo);
            }
        }

        return !empty($parts) ? implode('. ', $parts) : '';
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(PdfDocumentResource::getUrl('index')),

            Actions\Action::make('save')
                ->label('Save to Database')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Save')
                ->modalDescription('This will create a new project and link it to the customer. Are you sure?')
                ->action('saveToDatabase'),
        ];
    }

    public function saveToDatabase(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            // 1. Get or create customer
            if ($this->createNewCustomer || empty($this->selectedCustomerId)) {
                $customer = Partner::create([
                    'name' => $data['customer_name'] ?? 'Unknown Customer',
                    'account_type' => 'individual',
                    'sub_type' => 'customer',
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['customer_phone'] ?? null,
                    'website' => $data['customer_website'] ?? null,
                    'company_registry' => $data['customer_company'] ?? null,
                    'street1' => $data['street1'] ?? null,
                    'street2' => $data['street2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country_id' => Country::where('code', 'US')->first()?->id ?? 1,
                    'is_active' => true,
                ]);
            } else {
                $customer = Partner::find($this->selectedCustomerId);
            }

            // 2. Create project
            $project = Project::create([
                'name' => $data['project_name'],
                'project_type' => $data['project_type'],
                'start_date' => $data['start_date'] ?? now(),
                'estimated_linear_feet' => $data['estimated_linear_feet'] ?? null,
                'description' => $data['description'] ?? null,
                'partner_id' => $customer->id,
                'creator_id' => auth()->id(),
                'is_active' => true,
            ]);

            // 3. Create project address
            if (!empty($data['street1'])) {
                ProjectAddress::create([
                    'project_id' => $project->id,
                    'type' => 'project',
                    'street1' => $data['street1'],
                    'street2' => $data['street2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country_id' => Country::where('code', 'US')->first()?->id ?? 1,
                    'is_primary' => true,
                ]);
            }

            // 4. Create cabinet specifications from extracted room equipment
            $this->createCabinetSpecifications($project);

            // 5. Link PDF to project
            $this->record->update([
                'module_type' => 'projects',
                'module_id' => $project->id,
                'extracted_metadata' => $this->extractedData,
                'processing_status' => 'completed',
                'metadata_reviewed' => true,
                'extracted_at' => now(),
            ]);
        });

        Notification::make()
            ->title('Project Created Successfully')
            ->success()
            ->body('Project and customer data saved to database.')
            ->send();

        $this->redirect(PdfDocumentResource::getUrl('index'));
    }

    /**
     * Create cabinet specifications from extracted room equipment
     *
     * @param Project $project
     * @return void
     */
    protected function createCabinetSpecifications(Project $project): void
    {
        // Get room data from extracted metadata
        $rooms = $this->extractedData['rooms'] ?? [];

        if (empty($rooms)) {
            return;
        }

        $matcher = app(ProductMatcher::class);
        $createdCount = 0;

        foreach ($rooms as $roomName => $roomData) {
            $equipment = $roomData['equipment'] ?? [];

            if (empty($equipment)) {
                continue;
            }

            foreach ($equipment as $item) {
                // Try to match equipment to existing product
                $product = $matcher->matchEquipment($item);

                // Format equipment description
                $equipmentString = $matcher->formatEquipmentString($item);

                // Create cabinet specification
                CabinetSpecification::create([
                    'project_id' => $project->id,
                    'product_variant_id' => $product?->id,
                    'hardware_notes' => $equipmentString,
                    'custom_modifications' => "Room: {$roomName}",
                    'shop_notes' => $product
                        ? "Auto-matched to product: {$product->name}"
                        : "No product match found - manual selection required",
                    'quantity' => 1,
                    'creator_id' => auth()->id(),
                ]);

                $createdCount++;
            }
        }

        if ($createdCount > 0) {
            \Log::info("Created {$createdCount} cabinet specifications for project {$project->id}");
        }
    }
}
