<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use App\Forms\Components\AddressAutocomplete;
use App\Forms\Components\TagSelectorPanel;
use App\Services\ProductionEstimatorService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Webkul\Project\Services\TcsPricingService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Webkul\Partner\Enums\AccountType;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Enums\BudgetRange;
use Webkul\Project\Enums\LeadSource;
use Webkul\Project\Enums\ProjectVisibility;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectDraft;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Settings\TaskSettings;
use Webkul\Project\Settings\TimeSettings;
use Webkul\Support\Models\Company;

/**
 * Create Project Wizard
 *
 * 5-step wizard optimized for Bryan's workflow:
 * - Step 1: Quick Capture (60 seconds) - Customer, Project Type, Address, Lead Source
 * - Step 2: Scope & Budget (2-3 minutes) - Linear Feet, Budget Range, Complexity Score
 * - Step 3: Timeline (Skippable) - Start Date, Completion Date, Project Manager
 * - Step 4: Documents & Tags (Skippable) - PDFs, Tags, Description
 * - Step 5: Review & Create - Summary, Stage, Settings
 */
class CreateProject extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    protected static ?string $title = 'Create Project';

    protected string $view = 'webkul-project::filament.pages.create-project-wizard';

    public ?array $data = [];

    public ?ProjectDraft $draft = null;

    /**
     * Last saved timestamp for auto-save indicator
     * Format: "X minutes ago" or "just now"
     */
    public ?string $lastSavedAt = null;

    /**
     * Active specification path for accordion/breadcrumb navigation
     * Format: ['spec_rooms.0', 'spec_rooms.0.locations.1', ...]
     */
    public array $activeSpecPath = [];

    /**
     * Breadcrumb items for current active path
     * Format: [['label' => 'Kitchen', 'key' => 'spec_rooms.0'], ...]
     */
    public array $specBreadcrumbs = [];

    /**
     * Mount the wizard
     */
    public function mount(): void
    {
        // Check for existing draft
        $this->draft = ProjectDraft::where('user_id', Auth::id())
            ->active()
            ->latest()
            ->first();

        $defaultCompany = Company::where('is_default', true)->first();
        $initialStage = ProjectStage::orderBy('sort')->first();

        // Default form data
        $defaults = [
            'company_id' => $defaultCompany?->id,
            'stage_id' => $initialStage?->id,
            'visibility' => 'internal',
            'allow_milestones' => true,
            'allow_timesheets' => true,
            'use_customer_address' => true,
            'user_id' => Auth::id(),
        ];

        // Merge with draft data if resuming
        if ($this->draft) {
            $defaults = array_merge($defaults, $this->draft->form_data ?? []);
        }

        $this->form->fill($defaults);
    }

    /**
     * Get header actions - includes "Create Now" button for early project creation
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createNow')
                ->label('Create Now')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->size('lg')
                ->action(fn () => $this->create())
                ->tooltip('Create project with current data'),
        ];
    }

    /**
     * Define the wizard form schema
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    // Step 1: Quick Capture (Required - Target 60 seconds)
                    Step::make('1. Quick Capture')
                        ->description('Customer, address & project type')
                        ->icon('heroicon-o-bolt')
                        ->schema($this->getStep1Schema())
                        ->afterValidation(fn () => $this->saveDraft(1)),

                    // Step 2: Scope & Budget (Required - Target 2-3 minutes)
                    Step::make('2. Scope & Budget')
                        ->description('Linear feet & budget estimate')
                        ->icon('heroicon-o-calculator')
                        ->schema($this->getStep2Schema())
                        ->afterValidation(fn () => $this->saveDraft(2)),

                    // Step 3: Timeline (Skippable)
                    Step::make('3. Timeline')
                        ->description('Dates & project manager')
                        ->icon('heroicon-o-calendar')
                        ->schema($this->getStep3Schema())
                        ->afterValidation(fn () => $this->saveDraft(3)),

                    // Step 4: Documents & Tags (Skippable)
                    Step::make('4. Documents')
                        ->description('PDFs, tags & notes')
                        ->icon('heroicon-o-document-text')
                        ->schema($this->getStep4Schema())
                        ->afterValidation(fn () => $this->saveDraft(4)),

                    // Step 5: Review & Create
                    Step::make('5. Review & Create')
                        ->description('Confirm & create project')
                        ->icon('heroicon-o-check-circle')
                        ->schema($this->getStep5Schema()),
                ])
                    ->submitAction(view('webkul-project::filament.components.wizard-submit-button'))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * Step 1: Quick Capture - Customer, Project Type, Address, Lead Source
     */
    protected function getStep1Schema(): array
    {
        return [
            // ========================================
            // YOUR BUSINESS - Internal settings (auto-collapsed if company selected)
            // ========================================
            Section::make('Your Business')
                ->description('Internal settings - where this project is managed from')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(fn () => Company::whereNull('parent_id')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => Company::where('is_default', true)->value('id'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $this->updateProjectNumberPreview($state, $get, $set);
                                $this->calculateEstimatedProductionTime($get('estimated_linear_feet'), $get, $set);
                            }),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return [];
                                }
                                return Company::where('parent_id', $companyId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $this->updateProjectNumberPreview($get('company_id'), $get, $set);
                            })
                            ->visible(fn (callable $get) => $get('company_id') !== null)
                            ->helperText('Optional: Select a specific branch'),
                    ]),

                    Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->options(function (callable $get) {
                            $companyId = $get('company_id');
                            if (!$companyId) {
                                return \Webkul\Inventory\Models\Warehouse::pluck('name', 'id');
                            }
                            return \Webkul\Inventory\Models\Warehouse::where('company_id', $companyId)->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->live(onBlur: true)
                        ->visible(fn (callable $get) => $get('company_id') !== null)
                        ->helperText('Where materials will be allocated from'),
                ])
                ->collapsible()
                ->collapsed(fn (callable $get) => filled($get('company_id')))
                ->compact(),

            // ========================================
            // CLIENT INFORMATION - Who you're working for
            // ========================================
            Section::make('Client Information')
                ->description('Who is this project for?')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('partner_id')
                            ->label('Customer')
                            ->searchable()
                            ->required()
                            ->live(onBlur: true)
                            ->getSearchResultsUsing(function (string $search): array {
                                // Search existing customers
                                return Partner::where('sub_type', 'customer')
                                    ->where('name', 'like', "%{$search}%")
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => Partner::find($value)?->name)
                            // Enable inline "Create new customer" slide-over using FilamentPHP's built-in pattern
                            // Reference: https://github.com/filamentphp/filament/discussions/5379
                            ->createOptionForm($this->getCustomerCreationFormComponentsSimplified())
                            ->createOptionUsing(function (array $data): int {
                                // Set required fields for customer creation
                                $data['sub_type'] = 'customer';
                                $data['creator_id'] = Auth::id();

                                // Create the customer
                                $partner = Partner::create($data);

                                // Show success notification
                                Notification::make()
                                    ->success()
                                    ->title('Customer Created')
                                    ->body("Customer '{$partner->name}' has been created and selected.")
                                    ->send();

                                return $partner->getKey();
                            })
                            ->createOptionAction(
                                fn (Action $action) => $action
                                    ->slideOver()
                                    ->modalWidth('lg')
                                    ->modalHeading('Add New Customer')
                                    ->modalDescription('Quick customer entry - you can add more details later.')
                            )
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $get('use_customer_address')) {
                                    $partner = Partner::with(['state', 'country'])->find($state);
                                    if ($partner) {
                                        $set('project_address.street1', $partner->street1);
                                        $set('project_address.street2', $partner->street2);
                                        $set('project_address.city', $partner->city);
                                        $set('project_address.zip', $partner->zip);
                                        $set('project_address.country_id', $partner->country_id);
                                        $set('project_address.state_id', $partner->state_id);
                                        $this->updateProjectNumberPreview($get('company_id'), $get, $set);
                                        $this->updateProjectName($get, $set);
                                    }
                                }
                            }),

                        Select::make('lead_source')
                            ->label('Lead Source')
                            ->options(LeadSource::options())
                            ->required()
                            ->native(false)
                            ->helperText('How did the customer find us?'),
                    ]),
                ])
                ->compact(),

            Section::make('Project Type')
                ->schema([
                    Select::make('project_type')
                        ->label('Project Type')
                        ->options([
                            'residential' => 'Residential',
                            'commercial' => 'Commercial',
                            'furniture' => 'Furniture',
                            'millwork' => 'Millwork',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $this->updateProjectName($get, $set);
                        })
                        ->native(false)
                        ->columnSpanFull(),
                ])
                ->compact(),

            Section::make('Project Location')
                ->description('Where will the work be done?')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Toggle::make('use_customer_address')
                        ->label('Same as customer address')
                        ->helperText(fn (callable $get) => $get('use_customer_address')
                            ? 'Project location will use the customer\'s address'
                            : 'Enter a different location for this project')
                        ->default(true)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state) {
                                $partnerId = $get('partner_id');
                                if ($partnerId) {
                                    $partner = Partner::with(['state', 'country'])->find($partnerId);
                                    if ($partner) {
                                        $set('project_address.street1', $partner->street1);
                                        $set('project_address.street2', $partner->street2);
                                        $set('project_address.city', $partner->city);
                                        $set('project_address.zip', $partner->zip);
                                        $set('project_address.country_id', $partner->country_id);
                                        $set('project_address.state_id', $partner->state_id);
                                    }
                                }
                            } else {
                                $set('project_address.street1', null);
                                $set('project_address.street2', null);
                                $set('project_address.city', null);
                                $set('project_address.zip', null);
                                $set('project_address.country_id', null);
                                $set('project_address.state_id', null);
                            }
                        })
                        ->inline()
                        ->columnSpanFull(),

                    AddressAutocomplete::make('project_address.street1')
                        ->label('Street Address')
                        ->cityField('project_address.city')
                        ->stateField('project_address.state_id')
                        ->zipField('project_address.zip')
                        ->countryField('project_address.country_id')
                        ->disabled(fn (callable $get) => $get('use_customer_address'))
                        ->dehydrated()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $this->updateProjectNumberPreview($get('company_id'), $get, $set);
                            $this->updateProjectName($get, $set);
                        })
                        ->columnSpanFull(),

                    // Hidden country field - populated by AddressAutocomplete, triggers state options refresh
                    Select::make('project_address.country_id')
                        ->label('Country')
                        ->options(\Webkul\Support\Models\Country::whereIn('code', ['US', 'CA'])->pluck('name', 'id'))
                        ->default(fn () => \Webkul\Support\Models\Country::where('code', 'US')->first()?->id)
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('project_address.state_id', null))
                        ->disabled(fn (callable $get) => $get('use_customer_address'))
                        ->dehydrated()
                        ->hidden(),

                    Grid::make(3)->schema([
                        TextInput::make('project_address.city')
                            ->label('City')
                            ->disabled(fn (callable $get) => $get('use_customer_address'))
                            ->dehydrated(),
                        Select::make('project_address.state_id')
                            ->label('State')
                            ->options(function (callable $get) {
                                $countryId = $get('project_address.country_id');
                                if (!$countryId) {
                                    // Default to US states if no country selected
                                    $usCountryId = \Webkul\Support\Models\Country::where('code', 'US')->first()?->id;
                                    return \Webkul\Support\Models\State::where('country_id', $usCountryId)->pluck('name', 'id');
                                }
                                return \Webkul\Support\Models\State::where('country_id', $countryId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => $get('use_customer_address'))
                            ->dehydrated(),
                        TextInput::make('project_address.zip')
                            ->label('Zip')
                            ->disabled(fn (callable $get) => $get('use_customer_address'))
                            ->dehydrated(),
                    ]),
                ])
                ->compact(),

            // Hidden fields for project number/name (displayed in sidebar instead)
            TextInput::make('project_number')
                ->label('Project Number (override)')
                ->placeholder('Leave blank to auto-generate')
                ->maxLength(255)
                ->hidden(),
            TextInput::make('name')
                ->label('Project Name (override)')
                ->placeholder('Leave blank to auto-generate')
                ->maxLength(255)
                ->hidden(),
        ];
    }

    /**
     * Step 2: Scope & Budget - Quick estimate, room-by-room, or detailed spec
     */
    protected function getStep2Schema(): array
    {
        $pricingService = app(TcsPricingService::class);

        return [
            // Pricing Mode Toggle
            Radio::make('pricing_mode')
                ->label('Pricing Mode')
                ->options([
                    'quick' => 'Quick Estimate (total linear feet)',
                    'rooms' => 'Room-by-Room (rooms with pricing)',
                    'detailed' => 'Detailed Spec (Room → Location → Run → Cabinet)',
                ])
                ->default('quick')
                ->inline()
                ->reactive()
                ->columnSpanFull(),

            // ============================================
            // QUICK ESTIMATE MODE
            // ============================================
            Grid::make(2)
                ->schema([
                    TextInput::make('estimated_linear_feet')
                        ->label('Total Linear Feet')
                        ->suffix('LF')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $this->calculateEstimatedProductionTime($state, $get, $set);
                            if ($state && $get('company_id')) {
                                $estimate = ProductionEstimatorService::calculate($state, $get('company_id'));
                                if ($estimate) {
                                    $set('allocated_hours', $estimate['hours']);
                                }
                            }
                        })
                        ->helperText('Enter total linear feet for quick estimate'),

                    Select::make('budget_range')
                        ->label('Budget Range')
                        ->options(BudgetRange::options())
                        ->native(false)
                        ->helperText('Approximate project budget'),
                ])
                ->visible(fn (callable $get) => $get('pricing_mode') === 'quick'),

            // Quick mode pricing options (applies to entire project)
            Grid::make(3)
                ->schema([
                    Select::make('default_cabinet_level')
                        ->label('Cabinet Level')
                        ->options(fn () => $pricingService->getCabinetLevelOptions())
                        ->default('3')
                        ->native(false)
                        ->live(),

                    Select::make('default_material_category')
                        ->label('Material')
                        ->options(fn () => $pricingService->getMaterialCategoryOptions())
                        ->default('stain_grade')
                        ->native(false)
                        ->live(),

                    Select::make('default_finish_option')
                        ->label('Finish')
                        ->options(fn () => $pricingService->getFinishOptions())
                        ->default('unfinished')
                        ->native(false)
                        ->live(),
                ])
                ->visible(fn (callable $get) => $get('pricing_mode') === 'quick'),

            // ============================================
            // ROOM-BY-ROOM MODE (Simple)
            // ============================================
            Section::make('Rooms')
                ->description('Add rooms with linear feet and pricing options')
                ->icon('heroicon-o-home')
                ->schema([
                    Repeater::make('rooms')
                        ->label('')
                        ->schema([
                            Grid::make(6)->schema([
                                Select::make('room_type')
                                    ->label('Room')
                                    ->options($this->getRoomTypeOptions())
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('name')
                                    ->label('Name')
                                    ->placeholder('e.g. Master Bath')
                                    ->columnSpan(1),

                                TextInput::make('linear_feet')
                                    ->label('LF')
                                    ->numeric()
                                    ->step(0.5)
                                    ->suffix('LF')
                                    ->required()
                                    ->live(debounce: 500)
                                    ->columnSpan(1),

                                Select::make('cabinet_level')
                                    ->label('Level')
                                    ->options(fn () => $pricingService->getCabinetLevelOptions())
                                    ->default('3')
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(1),

                                Select::make('material_category')
                                    ->label('Material')
                                    ->options(fn () => $pricingService->getMaterialCategoryOptions())
                                    ->default('stain_grade')
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(1),

                                Select::make('finish_option')
                                    ->label('Finish')
                                    ->options(fn () => $pricingService->getFinishOptions())
                                    ->default('unfinished')
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(1),
                            ]),
                        ])
                        ->addActionLabel('+ Add Room')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(function (array $state) use ($pricingService): string {
                            $roomType = $state['room_type'] ?? 'Room';
                            $name = $state['name'] ?? '';
                            $lf = $state['linear_feet'] ?? 0;

                            // Calculate price for this room
                            $level = $state['cabinet_level'] ?? '3';
                            $material = $state['material_category'] ?? 'stain_grade';
                            $finish = $state['finish_option'] ?? 'unfinished';
                            $unitPrice = $pricingService->calculateUnitPrice($level, $material, $finish);
                            $roomTotal = $lf * $unitPrice;

                            $label = ucfirst(str_replace('_', ' ', $roomType));
                            if ($name) {
                                $label .= " - {$name}";
                            }
                            if ($lf > 0) {
                                $label .= " | {$lf} LF × \${$unitPrice}/LF = \$" . number_format($roomTotal, 0);
                            }
                            return $label;
                        })
                        ->defaultItems(0)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Cascade: Room-by-Room → Quick Estimate
                            $totalLf = 0;
                            foreach ($state ?? [] as $room) {
                                $totalLf += (float) ($room['linear_feet'] ?? 0);
                            }
                            $set('estimated_linear_feet', round($totalLf, 2));
                        }),
                ])
                ->compact()
                ->visible(fn (callable $get) => $get('pricing_mode') === 'rooms'),

            // ============================================
            // DETAILED SPEC MODE (Full Hierarchy)
            // Room → Location → Run → Cabinet with Missing LF tracking
            // ============================================
            Section::make('Cabinet Specifications')
                ->description('Build detailed specs: Room → Location → Run → Cabinet → Section → Component')
                ->icon('heroicon-o-square-3-stack-3d')
                ->schema([
                    // Breadcrumb navigation
                    View::make('webkul-project::filament.components.spec-breadcrumb')
                        ->viewData(['path' => $this->specBreadcrumbs]),

                    // Hierarchical spec builder
                    Repeater::make('spec_rooms')
                        ->label('Rooms')
                        ->schema($this->getDetailedRoomSchema($pricingService))
                        ->addActionLabel('+ Add Room')
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->cloneable()
                        ->itemLabel(fn (array $state) => $this->getRoomLabel($state, $pricingService))
                        ->defaultItems(0)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Cascade: Detailed Spec → Quick Estimate
                            $totalLf = $this->calculateDetailedSpecTotalLf($state ?? []);
                            $set('estimated_linear_feet', round($totalLf, 2));
                        }),
                ])
                ->compact()
                ->visible(fn (callable $get) => $get('pricing_mode') === 'detailed'),

            // ============================================
            // ESTIMATE SUMMARY (all modes - cascading updates)
            // ============================================
            Section::make('Estimate Summary')
                ->schema([
                    Placeholder::make('estimate_summary')
                        ->label('')
                        ->content(function (callable $get) use ($pricingService) {
                            $mode = $get('pricing_mode') ?? 'quick';
                            $companyId = $get('company_id');

                            $linearFeet = 0;
                            $totalEstimate = 0;
                            $roomCount = null;

                            if ($mode === 'quick') {
                                // Quick Estimate: use direct linear feet input
                                $linearFeet = (float) ($get('estimated_linear_feet') ?: 0);
                                $level = $get('default_cabinet_level') ?? '3';
                                $material = $get('default_material_category') ?? 'stain_grade';
                                $finish = $get('default_finish_option') ?? 'unfinished';

                                if (!$linearFeet) {
                                    return 'Enter linear feet to see estimate';
                                }

                                $unitPrice = $pricingService->calculateUnitPrice($level, $material, $finish);
                                $totalEstimate = $linearFeet * $unitPrice;

                            } elseif ($mode === 'rooms') {
                                // Room-by-room mode: sum from rooms
                                $rooms = $get('rooms') ?? [];
                                if (empty($rooms)) {
                                    return 'Add rooms to see estimate';
                                }

                                foreach ($rooms as $room) {
                                    $lf = (float) ($room['linear_feet'] ?? 0);
                                    $level = $room['cabinet_level'] ?? '3';
                                    $material = $room['material_category'] ?? 'stain_grade';
                                    $finish = $room['finish_option'] ?? 'unfinished';

                                    $unitPrice = $pricingService->calculateUnitPrice($level, $material, $finish);
                                    $linearFeet += $lf;
                                    $totalEstimate += ($lf * $unitPrice);
                                }
                                $roomCount = count($rooms);

                            } else {
                                // Detailed spec mode: calculate from hierarchy
                                $specRooms = $get('spec_rooms') ?? [];
                                if (empty($specRooms)) {
                                    return 'Add rooms to see estimate';
                                }

                                $linearFeet = $this->calculateDetailedSpecTotalLf($specRooms);
                                $roomCount = count($specRooms);

                                // Calculate estimate using room-level cabinet levels
                                foreach ($specRooms as $roomData) {
                                    $roomLf = (float) ($roomData['estimated_lf'] ?? 0);
                                    $level = $roomData['cabinet_level'] ?? '3';

                                    // If no room estimate, calculate from children
                                    if ($roomLf <= 0) {
                                        foreach ($roomData['locations'] ?? [] as $loc) {
                                            $locLf = (float) ($loc['estimated_lf'] ?? 0);
                                            if ($locLf > 0) {
                                                $roomLf += $locLf;
                                            } else {
                                                foreach ($loc['runs'] ?? [] as $run) {
                                                    $runLf = (float) ($run['total_lf'] ?? 0);
                                                    if ($runLf > 0) {
                                                        $roomLf += $runLf;
                                                    } else {
                                                        foreach ($run['cabinets'] ?? [] as $cab) {
                                                            $width = (float) ($cab['width_inches'] ?? 0);
                                                            $qty = (int) ($cab['quantity'] ?? 1);
                                                            $roomLf += ($width / 12) * $qty;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $unitPrice = $pricingService->calculateUnitPrice($level, 'stain_grade', 'unfinished');
                                    $totalEstimate += $roomLf * $unitPrice;
                                }
                            }

                            $productionTime = 'N/A';
                            if ($companyId && $linearFeet > 0) {
                                $estimate = ProductionEstimatorService::calculate($linearFeet, $companyId);
                                $productionTime = $estimate ? $estimate['formatted'] : 'N/A';
                            }

                            return view('webkul-project::filament.components.quick-estimate-panel', [
                                'linearFeet' => $linearFeet,
                                'baseRate' => round($totalEstimate / max($linearFeet, 1), 2),
                                'quickEstimate' => $totalEstimate,
                                'productionTime' => $productionTime,
                                'roomCount' => $roomCount,
                            ]);
                        }),
                ])
                ->compact(),

            // Complexity & allocated hours
            Grid::make(2)->schema([
                TextInput::make('complexity_score')
                    ->label('Complexity Score')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->step(1)
                    ->placeholder('1-10')
                    ->helperText('1 = Simple, 10 = Highly complex'),

                TextInput::make('allocated_hours')
                    ->label('Allocated Hours')
                    ->suffixIcon('heroicon-o-clock')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Auto-calculated from linear feet')
                    ->visible(app(TimeSettings::class)->enable_timesheets),
            ]),

            // Hidden fields for cabinet spec data (legacy support)
            Hidden::make('cabinet_spec_data')
                ->default('[]'),

            // Customer History Panel (when customer selected)
            Section::make('Customer History')
                ->schema([
                    Placeholder::make('customer_history')
                        ->label('')
                        ->content(function (callable $get) {
                            $partnerId = $get('partner_id');
                            if (!$partnerId) {
                                return 'Select a customer to see history';
                            }

                            $partner = Partner::find($partnerId);
                            if (!$partner) {
                                return 'Customer not found';
                            }

                            // Get customer project history
                            $projects = Project::where('partner_id', $partnerId)->get();
                            $totalProjects = $projects->count();

                            return view('webkul-project::filament.components.customer-history-panel', [
                                'partner' => $partner,
                                'totalProjects' => $totalProjects,
                            ]);
                        }),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(fn (callable $get) => !$get('partner_id')),
        ];
    }

    /**
     * Step 3: Timeline - Start Date, Completion Date, Project Manager (Skippable)
     */
    protected function getStep3Schema(): array
    {
        return [
            Placeholder::make('skip_notice')
                ->label('')
                ->content('This step is optional. You can skip it and set dates later.')
                ->extraAttributes(['class' => 'text-sm text-gray-500 italic']),

            Grid::make(2)->schema([
                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->native(false)
                    ->suffixIcon('heroicon-o-calendar')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state && $get('estimated_linear_feet') && $get('company_id')) {
                            $estimate = ProductionEstimatorService::calculate($get('estimated_linear_feet'), $get('company_id'));
                            if ($estimate) {
                                $daysNeeded = ceil($estimate['days']);
                                $startDate = \Carbon\Carbon::parse($state);
                                $completionDate = $startDate->copy()->addWeekdays($daysNeeded);
                                $set('desired_completion_date', $completionDate->format('Y-m-d'));
                            }
                        }
                    }),

                DatePicker::make('desired_completion_date')
                    ->label('Desired Completion Date')
                    ->native(false)
                    ->suffixIcon('heroicon-o-calendar')
                    ->reactive()
                    ->helperText(function (callable $get) {
                        $linearFeet = $get('estimated_linear_feet');
                        $companyId = $get('company_id');

                        if (!$linearFeet || !$companyId) {
                            return null;
                        }

                        $estimate = ProductionEstimatorService::calculate($linearFeet, $companyId);
                        if (!$estimate) {
                            return null;
                        }

                        return "Production time needed: {$estimate['formatted']}";
                    }),
            ]),

            Select::make('user_id')
                ->label('Project Manager')
                ->options(fn () => \Webkul\Security\Models\User::pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->default(Auth::id()),

            // Capacity Warning Panel
            Section::make('Capacity Check')
                ->schema([
                    Placeholder::make('capacity_warning')
                        ->label('')
                        ->content(function (callable $get) {
                            $startDate = $get('start_date');
                            $completionDate = $get('desired_completion_date');
                            $linearFeet = $get('estimated_linear_feet');
                            $companyId = $get('company_id');

                            if (!$startDate || !$completionDate || !$linearFeet || !$companyId) {
                                return 'Set dates and linear feet to check capacity';
                            }

                            return view('webkul-project::filament.components.capacity-warning-panel', [
                                'startDate' => $startDate,
                                'completionDate' => $completionDate,
                                'linearFeet' => $linearFeet,
                                'companyId' => $companyId,
                            ]);
                        }),
                ])
                ->compact()
                ->collapsible(),
        ];
    }

    /**
     * Step 4: Documents & Tags (Skippable)
     */
    protected function getStep4Schema(): array
    {
        return [
            Placeholder::make('skip_notice')
                ->label('')
                ->content('This step is optional. You can add documents and tags later.')
                ->extraAttributes(['class' => 'text-sm text-gray-500 italic']),

            FileUpload::make('architectural_pdfs')
                ->label('Architectural PDFs')
                ->multiple()
                ->acceptedFileTypes(['application/pdf'])
                ->directory('project-pdfs')
                ->maxSize(50 * 1024) // 50MB
                ->helperText('Upload plans, blueprints, drawings (PDF only)')
                ->columnSpanFull(),

            TagSelectorPanel::make('tags')
                ->label('Project Tags')
                ->columnSpanFull(),

            RichEditor::make('description')
                ->label('Project Notes')
                ->placeholder('Add any notes or special instructions...')
                ->columnSpanFull(),
        ];
    }

    /**
     * Step 5: Review & Create
     */
    protected function getStep5Schema(): array
    {
        return [
            // Summary Card
            Section::make('Project Summary')
                ->schema([
                    Placeholder::make('summary')
                        ->label('')
                        ->content(function (callable $get) {
                            return view('webkul-project::filament.components.wizard-summary-card', [
                                'data' => $get,
                            ]);
                        }),
                ])
                ->compact(),

            // Stage Selection
            ProgressStepper::make('stage_id')
                ->label('Initial Stage')
                ->inline()
                ->required()
                ->visible(app(TaskSettings::class)->enable_project_stages)
                ->options(fn () => ProjectStage::orderBy('sort')->get()->mapWithKeys(fn ($stage) => [$stage->id => $stage->name]))
                ->colors(fn () => ProjectStage::orderBy('sort')->get()->mapWithKeys(fn ($stage) => [
                    $stage->id => $stage->color ? \Filament\Support\Colors\Color::generateV3Palette($stage->color) : 'gray'
                ]))
                ->default(ProjectStage::first()?->id),

            // Settings
            Section::make('Settings')
                ->schema([
                    Grid::make(2)->schema([
                        Radio::make('visibility')
                            ->label('Visibility')
                            ->default('internal')
                            ->options(ProjectVisibility::options())
                            ->descriptions([
                                'private' => 'Only you can see this project',
                                'internal' => 'All team members can see this project',
                                'public' => 'Everyone including customers can see',
                            ]),

                        Grid::make(1)->schema([
                            Toggle::make('allow_timesheets')
                                ->label('Allow Timesheets')
                                ->default(true)
                                ->visible(app(TimeSettings::class)->enable_timesheets),
                            Toggle::make('allow_milestones')
                                ->label('Allow Milestones')
                                ->default(true)
                                ->visible(app(TaskSettings::class)->enable_milestones),
                        ]),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),
        ];
    }

    /**
     * Create the project
     */
    public function create(): void
    {
        $data = $this->form->getState();

        // Generate project number if not set
        if (empty($data['project_number'])) {
            $data['project_number'] = $this->generateProjectNumber($data);
        }

        // Generate project name if not set
        if (empty($data['name'])) {
            $data['name'] = $this->generateProjectName($data);
        }

        // Calculate total linear feet based on pricing mode
        $pricingMode = $data['pricing_mode'] ?? 'quick';

        if ($pricingMode === 'rooms' && !empty($data['rooms'])) {
            // Room-by-room mode: sum linear feet from rooms
            $totalLf = 0;
            foreach ($data['rooms'] as $room) {
                $totalLf += (float) ($room['linear_feet'] ?? 0);
            }
            $data['estimated_linear_feet'] = $totalLf;
        } elseif ($pricingMode === 'detailed' && !empty($data['spec_rooms'])) {
            // Detailed spec mode: calculate from hierarchy (rooms → locations → runs → cabinets)
            $totalLf = 0;
            foreach ($data['spec_rooms'] as $roomData) {
                $roomLf = (float) ($roomData['estimated_lf'] ?? 0);

                // If no room-level estimate, calculate from children
                if ($roomLf <= 0) {
                    foreach ($roomData['locations'] ?? [] as $loc) {
                        $locLf = (float) ($loc['estimated_lf'] ?? 0);
                        if ($locLf > 0) {
                            $roomLf += $locLf;
                        } else {
                            foreach ($loc['runs'] ?? [] as $run) {
                                $runLf = (float) ($run['total_lf'] ?? 0);
                                if ($runLf > 0) {
                                    $roomLf += $runLf;
                                } else {
                                    foreach ($run['cabinets'] ?? [] as $cab) {
                                        $width = (float) ($cab['width_inches'] ?? 0);
                                        $qty = (int) ($cab['quantity'] ?? 1);
                                        $roomLf += ($width / 12) * $qty;
                                    }
                                }
                            }
                        }
                    }
                }
                $totalLf += $roomLf;
            }
            $data['estimated_linear_feet'] = $totalLf;
        }

        // Set creator
        $data['creator_id'] = Auth::id();

        // Create the project
        $project = Project::create([
            'name' => $data['name'],
            'project_number' => $data['project_number'],
            'project_type' => $data['project_type'] ?? null,
            'lead_source' => $data['lead_source'] ?? null,
            'budget_range' => $data['budget_range'] ?? null,
            'complexity_score' => $data['complexity_score'] ?? null,
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'] ?? 'internal',
            'start_date' => $data['start_date'] ?? null,
            'desired_completion_date' => $data['desired_completion_date'] ?? null,
            'allocated_hours' => $data['allocated_hours'] ?? null,
            'estimated_linear_feet' => $data['estimated_linear_feet'] ?? null,
            'allow_timesheets' => $data['allow_timesheets'] ?? true,
            'allow_milestones' => $data['allow_milestones'] ?? true,
            'is_active' => true,
            'stage_id' => $data['stage_id'] ?? ProjectStage::orderBy('sort')->value('id'),
            'partner_id' => $data['partner_id'],
            'use_customer_address' => $data['use_customer_address'] ?? true,
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'user_id' => $data['user_id'] ?? Auth::id(),
            'creator_id' => Auth::id(),
        ]);

        // Save project address if provided
        if (!empty($data['project_address']['street1']) || !empty($data['project_address']['city'])) {
            $project->addresses()->create([
                'type' => 'project',
                'street1' => $data['project_address']['street1'] ?? null,
                'street2' => $data['project_address']['street2'] ?? null,
                'city' => $data['project_address']['city'] ?? null,
                'zip' => $data['project_address']['zip'] ?? null,
                'country_id' => $data['project_address']['country_id'] ?? null,
                'state_id' => $data['project_address']['state_id'] ?? null,
                'is_primary' => true,
            ]);
        }

        // Save production estimate if linear feet provided
        if (!empty($data['estimated_linear_feet']) && !empty($data['company_id'])) {
            $estimate = ProductionEstimatorService::calculate(
                $data['estimated_linear_feet'],
                $data['company_id']
            );

            if ($estimate) {
                \App\Models\ProductionEstimate::createFromEstimate(
                    $project->id,
                    $data['company_id'],
                    $data['estimated_linear_feet'],
                    $estimate
                );
            }
        }

        // Handle PDF uploads
        if (!empty($data['architectural_pdfs'])) {
            $this->handlePdfUploads($project, $data['architectural_pdfs']);
        }

        // Sync tags
        if (!empty($data['tags'])) {
            $project->tags()->sync($data['tags']);
            Cache::forget('project_tags_most_used');
        }

        // Create entities from cabinet spec data
        if (!empty($data['cabinet_spec_data'])) {
            $specData = json_decode($data['cabinet_spec_data'], true);
            if (!empty($specData)) {
                $this->createEntitiesFromSpec($project, $specData);
            }
        }

        // Create rooms based on pricing mode
        if ($pricingMode === 'rooms' && !empty($data['rooms'])) {
            // Simple room-by-room mode
            $this->createRoomsFromWizard($project, $data['rooms']);
        } elseif ($pricingMode === 'detailed' && !empty($data['spec_rooms'])) {
            // Detailed spec mode: create full hierarchy
            $this->createEntitiesFromDetailedSpec($project, $data['spec_rooms']);
        }

        // Delete the draft
        if ($this->draft) {
            $this->draft->delete();
        }

        Notification::make()
            ->success()
            ->title('Project Created')
            ->body("Project '{$project->name}' has been created successfully.")
            ->send();

        $this->redirect(ProjectResource::getUrl('view', ['record' => $project->id]));
    }

    /**
     * Handle spec data updates from CabinetSpecBuilder component
     */
    #[On('spec-data-updated')]
    public function handleSpecDataUpdate(array $data): void
    {
        $this->data['cabinet_spec_data'] = json_encode($data);
        $this->saveDraft();
    }

    /**
     * Save draft at current step (called by auto-save and step navigation)
     * Public so it can be triggered from Alpine.js auto-save interval
     */
    public function saveDraft(?int $step = null): void
    {
        $data = $this->form->getState();
        $currentStep = $step ?? $this->draft?->current_step ?? 1;

        if (!$this->draft) {
            $this->draft = ProjectDraft::create([
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'current_step' => $currentStep,
                'form_data' => $data,
                'expires_at' => now()->addDays(7),
            ]);
        } else {
            $this->draft->update([
                'current_step' => $currentStep,
                'form_data' => $data,
            ]);
        }

        // Update the last saved indicator
        $this->lastSavedAt = 'just now';
    }

    /**
     * Save draft and exit
     */
    public function saveAndExit(): void
    {
        $data = $this->form->getState();

        if (!$this->draft) {
            $this->draft = ProjectDraft::create([
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'current_step' => 1,
                'form_data' => $data,
                'expires_at' => now()->addDays(7),
            ]);
        } else {
            $this->draft->update([
                'form_data' => $data,
            ]);
        }

        Notification::make()
            ->success()
            ->title('Draft Saved')
            ->body('Your project draft has been saved. You can resume later.')
            ->send();

        $this->redirect(ProjectResource::getUrl('index'));
    }

    /**
     * Discard draft
     */
    public function discardDraft(): void
    {
        if ($this->draft) {
            $this->draft->delete();
            $this->draft = null;
        }

        $this->redirect(ProjectResource::getUrl('index'));
    }

    /**
     * Handle PDF uploads
     */
    protected function handlePdfUploads(Project $project, array $pdfs): void
    {
        $revisionNumber = 1;

        foreach ($pdfs as $pdfPath) {
            $originalFilename = basename($pdfPath);
            $fileSize = \Storage::disk('public')->size($pdfPath);

            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $originalName = pathinfo($originalFilename, PATHINFO_FILENAME);
            $cleanOriginalName = preg_replace('/^[0-9A-Z]{26}_/', '', $originalName);

            $newFilename = sprintf(
                '%s-Rev%d-%s.%s',
                $project->project_number,
                $revisionNumber,
                $cleanOriginalName ?: 'Drawing',
                $extension
            );

            $directory = dirname($pdfPath);
            $newPath = $directory . '/' . $newFilename;

            \Storage::disk('public')->move($pdfPath, $newPath);

            $pageCount = null;
            try {
                $fullPath = \Storage::disk('public')->path($newPath);
                if (file_exists($fullPath)) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($fullPath);
                    $pages = $pdf->getPages();
                    $pageCount = count($pages);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not extract page count from PDF: ' . $e->getMessage());
            }

            $project->pdfDocuments()->create([
                'file_path' => $newPath,
                'file_name' => $newFilename,
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
                'document_type' => 'drawing',
                'page_count' => $pageCount,
                'uploaded_by' => Auth::id(),
                'metadata' => json_encode([
                    'revision' => $revisionNumber,
                    'original_filename' => $originalFilename,
                ]),
            ]);

            $revisionNumber++;
        }
    }

    /**
     * Create Room, RoomLocation, CabinetRun, and Cabinet entities from spec data
     *
     * @param Project $project The project to attach entities to
     * @param array $specData The hierarchical spec data from CabinetSpecBuilder
     */
    protected function createEntitiesFromSpec(Project $project, array $specData): void
    {
        $sortOrder = 1;

        foreach ($specData as $roomData) {
            // Create Room
            $room = $project->rooms()->create([
                'name' => $roomData['name'] ?? 'Unnamed Room',
                'room_type' => $roomData['room_type'] ?? 'other',
                'floor_number' => $roomData['floor_number'] ?? 1,
                'sort_order' => $sortOrder++,
            ]);

            $locationSort = 1;
            foreach ($roomData['children'] ?? [] as $locationData) {
                // Create Room Location
                $location = $room->roomLocations()->create([
                    'project_id' => $project->id,
                    'name' => $locationData['name'] ?? 'Unnamed Location',
                    'location_type' => $locationData['location_type'] ?? 'wall',
                    'cabinet_level' => $locationData['cabinet_level'] ?? 2,
                    'sort_order' => $locationSort++,
                ]);

                $runSort = 1;
                foreach ($locationData['children'] ?? [] as $runData) {
                    // Create Cabinet Run
                    $run = $location->cabinetRuns()->create([
                        'project_id' => $project->id,
                        'room_id' => $room->id,
                        'name' => $runData['name'] ?? 'Unnamed Run',
                        'run_type' => $runData['run_type'] ?? 'base',
                        'sort_order' => $runSort++,
                    ]);

                    $cabinetSort = 1;
                    foreach ($runData['children'] ?? [] as $cabinetData) {
                        // Create Cabinet Specification
                        $run->cabinetSpecifications()->create([
                            'project_id' => $project->id,
                            'room_id' => $room->id,
                            'room_location_id' => $location->id,
                            'name' => $cabinetData['name'] ?? null,
                            'cabinet_type' => $cabinetData['cabinet_type'] ?? 'base',
                            'length_inches' => $cabinetData['length_inches'] ?? null,
                            'depth_inches' => $cabinetData['depth_inches'] ?? null,
                            'height_inches' => $cabinetData['height_inches'] ?? null,
                            'quantity' => $cabinetData['quantity'] ?? 1,
                            'sort_order' => $cabinetSort++,
                        ]);
                    }
                }
            }
        }

        \Log::info('CabinetSpecBuilder: Created entities from spec', [
            'project_id' => $project->id,
            'rooms_count' => count($specData),
        ]);
    }

    /**
     * Create rooms from the wizard room-by-room pricing mode
     *
     * @param Project $project The project to attach rooms to
     * @param array $rooms The rooms data from the repeater
     */
    protected function createRoomsFromWizard(Project $project, array $rooms): void
    {
        $sortOrder = 1;
        $pricingService = app(TcsPricingService::class);

        foreach ($rooms as $roomData) {
            $linearFeet = (float) ($roomData['linear_feet'] ?? 0);
            $cabinetLevel = $roomData['cabinet_level'] ?? '3';
            $materialCategory = $roomData['material_category'] ?? 'stain_grade';
            $finishOption = $roomData['finish_option'] ?? 'unfinished';

            // Calculate the estimated value for this room
            $unitPrice = $pricingService->calculateUnitPrice($cabinetLevel, $materialCategory, $finishOption);
            $estimatedValue = $linearFeet * $unitPrice;

            // Map cabinet level to the appropriate tier column
            $tierColumn = "total_linear_feet_tier_{$cabinetLevel}";

            // Create the room with pricing info
            $project->rooms()->create([
                'name' => $roomData['name'] ?? null,
                'room_type' => $roomData['room_type'] ?? 'other',
                'sort_order' => $sortOrder++,
                'cabinet_level' => $cabinetLevel,
                'material_category' => $materialCategory,
                'finish_option' => $finishOption,
                $tierColumn => $linearFeet,
                'estimated_cabinet_value' => $estimatedValue,
                'creator_id' => Auth::id(),
            ]);
        }

        \Log::info('CreateProject: Created rooms from wizard', [
            'project_id' => $project->id,
            'rooms_count' => count($rooms),
        ]);
    }

    /**
     * Generate project number
     */
    protected function generateProjectNumber(array $data): string
    {
        $companyAcronym = 'UNK';
        if (!empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'UNK', 0, 3));
        }

        $startNumber = 1;
        if (!empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            $startNumber = $company?->project_number_start ?? 1;
        }

        $lastProject = Project::where('company_id', $data['company_id'])
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = $startNumber;
        if ($lastProject && $lastProject->project_number) {
            preg_match('/-(\d+)-/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = max(intval($matches[1]) + 1, $startNumber);
            }
        }

        $streetAbbr = '';
        if (!empty($data['project_address']['street1'])) {
            $street = preg_replace('/[^a-zA-Z0-9]/', '', $data['project_address']['street1']);
            $streetAbbr = $street;
        }

        return sprintf(
            '%s-%03d%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr ? "-{$streetAbbr}" : ''
        );
    }

    /**
     * Generate project name
     */
    protected function generateProjectName(array $data): string
    {
        $street = $data['project_address']['street1'] ?? '';
        $projectType = $data['project_type'] ?? '';

        if (!$street) {
            $street = 'New Project';
        }

        $projectTypeLabels = [
            'residential' => 'Residential',
            'commercial' => 'Commercial',
            'furniture' => 'Furniture',
            'millwork' => 'Millwork',
            'other' => 'Other',
        ];

        $typeLabel = $projectTypeLabels[$projectType] ?? 'Project';

        return "{$street} - {$typeLabel}";
    }

    /**
     * Update project number preview
     */
    protected function updateProjectNumberPreview(?int $companyId, callable $get, callable $set): void
    {
        if (!$companyId || !$get('project_address.street1')) {
            return;
        }

        $branchId = $get('branch_id');
        $companyToUse = $branchId ? Company::find($branchId) : Company::find($companyId);

        if (!$companyToUse) {
            return;
        }

        $companyAcronym = $companyToUse->acronym ?? strtoupper(substr($companyToUse->name ?? 'UNK', 0, 3));

        $startNumber = $companyToUse->project_number_start ?? 1;

        $lastProject = Project::where('company_id', $companyId)
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = $startNumber;
        if ($lastProject && $lastProject->project_number) {
            preg_match('/-(\d+)-/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = max(intval($matches[1]) + 1, $startNumber);
            }
        }

        $street = $get('project_address.street1');
        $streetAbbr = preg_replace('/[^a-zA-Z0-9]/', '', $street);

        $projectNumber = sprintf(
            '%s-%03d-%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr
        );

        $set('project_number', $projectNumber);
    }

    /**
     * Update project name
     */
    protected function updateProjectName(callable $get, callable $set): void
    {
        $street = $get('project_address.street1');
        $projectType = $get('project_type');

        if (!$street || !$projectType) {
            return;
        }

        $projectTypeLabels = [
            'residential' => 'Residential',
            'commercial' => 'Commercial',
            'furniture' => 'Furniture',
            'millwork' => 'Millwork',
            'other' => 'Other',
        ];

        $typeLabel = $projectTypeLabels[$projectType] ?? $projectType;
        $projectName = "{$street} - {$typeLabel}";

        $set('name', $projectName);
    }

    /**
     * Calculate estimated production time
     */
    protected function calculateEstimatedProductionTime($linearFeet, callable $get, callable $set): void
    {
        $startDate = $get('start_date');
        $desiredCompletionDate = $get('desired_completion_date');
        $companyId = $get('company_id');

        if ($startDate && $desiredCompletionDate && $companyId && !$linearFeet) {
            try {
                $company = Company::find($companyId);

                if ($company && $company->shop_capacity_per_day) {
                    $start = new \DateTime($startDate);
                    $end = new \DateTime($desiredCompletionDate);
                    $calendarDays = $start->diff($end)->days;
                    $workingDaysPerWeek = 4;
                    $workingDays = ($calendarDays / 7) * $workingDaysPerWeek;
                    $estimatedLinearFeet = round($workingDays * $company->shop_capacity_per_day, 2);

                    $set('estimated_linear_feet', $estimatedLinearFeet);

                    if ($company->shop_capacity_per_hour) {
                        $allocatedHours = round($estimatedLinearFeet / $company->shop_capacity_per_hour, 2);
                        $set('allocated_hours', $allocatedHours);
                    }
                }
            } catch (\Exception $e) {
                // Silently fail
            }
        }
    }

    /**
     * Calculate total linear feet from detailed spec hierarchy
     * Traverses: Rooms → Locations → Runs → Cabinets
     */
    protected function calculateDetailedSpecTotalLf(array $specRooms): float
    {
        $totalLf = 0;

        foreach ($specRooms as $roomData) {
            $roomLf = (float) ($roomData['estimated_lf'] ?? 0);

            // If room has explicit estimate, use it
            if ($roomLf > 0) {
                $totalLf += $roomLf;
                continue;
            }

            // Otherwise, calculate from children (locations → runs → cabinets)
            foreach ($roomData['locations'] ?? [] as $loc) {
                $locLf = (float) ($loc['estimated_lf'] ?? 0);
                if ($locLf > 0) {
                    $totalLf += $locLf;
                } else {
                    foreach ($loc['runs'] ?? [] as $run) {
                        $runLf = (float) ($run['total_lf'] ?? 0);
                        if ($runLf > 0) {
                            $totalLf += $runLf;
                        } else {
                            // Sum from individual cabinets
                            foreach ($run['cabinets'] ?? [] as $cab) {
                                $width = (float) ($cab['width_inches'] ?? 0);
                                $qty = (int) ($cab['quantity'] ?? 1);
                                $totalLf += ($width / 12) * $qty;
                            }
                        }
                    }
                }
            }
        }

        return $totalLf;
    }

    /**
     * Simplified customer creation form for createOptionForm()
     *
     * This version avoids ->relationship() calls which don't work
     * in the Select::createOptionForm() context (no model instance).
     *
     * Organized hierarchically with:
     * - Essentials first (always visible)
     * - Collapsible sections for additional details
     *
     * Reference: https://github.com/filamentphp/filament/discussions/5379
     *
     * @return array
     */
    protected function getCustomerCreationFormComponentsSimplified(): array
    {
        return [
            // ========================================
            // ESSENTIALS (Always Visible)
            // ========================================

            // Customer Type Toggle - Affects form behavior
            Radio::make('account_type')
                ->label('Customer Type')
                ->inline()
                ->inlineLabel(false)
                ->options([
                    AccountType::INDIVIDUAL->value => 'Individual',
                    AccountType::COMPANY->value => 'Company',
                ])
                ->default(AccountType::INDIVIDUAL->value)
                ->live()
                ->columnSpanFull(),

            // Dynamic Name Field based on type
            TextInput::make('name')
                ->label(fn (Get $get): string => $get('account_type') === AccountType::COMPANY->value
                    ? 'Company Name'
                    : 'Full Name')
                ->required()
                ->maxLength(255)
                ->placeholder(fn (Get $get): string => $get('account_type') === AccountType::COMPANY->value
                    ? 'e.g. Acme Construction LLC'
                    : 'e.g. John Smith')
                ->autofocus()
                ->extraInputAttributes(['class' => 'text-lg font-medium'])
                ->columnSpanFull(),

            // Primary Contact Info (essential)
            Grid::make(2)->schema([
                TextInput::make('phone')
                    ->label('Phone')
                    ->tel()
                    ->maxLength(255)
                    ->placeholder('(508) 555-1234')
                    ->prefixIcon('heroicon-o-phone'),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->placeholder('john@example.com')
                    ->prefixIcon('heroicon-o-envelope'),
            ]),

            // ========================================
            // ACCORDION SECTIONS (Collapsible)
            // ========================================

            // Section 1: Address
            Section::make('Address')
                ->description('Billing and project location')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    AddressAutocomplete::make('street1')
                        ->label('Street Address')
                        ->cityField('city')
                        ->stateField('state_id')
                        ->zipField('zip')
                        ->countryField('country_id')
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-map-pin')
                        ->columnSpanFull(),

                    TextInput::make('street2')
                        ->label('Apt/Suite/Unit')
                        ->maxLength(255)
                        ->placeholder('Apt 2B, Suite 100, etc.')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),

                        TextInput::make('zip')
                            ->label('Zip')
                            ->maxLength(255),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('state_id')
                            ->label('State')
                            ->options(fn () => \Webkul\Support\Models\State::where('country_id', 233)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('country_id')
                            ->label('Country')
                            ->options(fn () => \Webkul\Support\Models\Country::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(233)
                            ->live(),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),

            // Section 2: Additional Contact (for individuals)
            Section::make('Additional Details')
                ->description('Job title, mobile, website')
                ->icon('heroicon-o-identification')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('mobile')
                            ->label('Mobile')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('(508) 555-5678'),

                        TextInput::make('job_title')
                            ->label('Job Title')
                            ->maxLength(255)
                            ->placeholder('Project Manager')
                            ->visible(fn (Get $get): bool => $get('account_type') === AccountType::INDIVIDUAL->value),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://example.com')
                            ->visible(fn (Get $get): bool => $get('account_type') === AccountType::COMPANY->value),
                    ]),

                    // Parent Company (only for individuals)
                    Select::make('parent_id')
                        ->label('Associated Company')
                        ->options(fn () => Partner::where('account_type', AccountType::COMPANY->value)
                            ->where('sub_type', 'customer')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountType::INDIVIDUAL->value)
                        ->helperText('Link this person to a company')
                        ->columnSpanFull(),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),

            // Section 3: Business Details (for companies only)
            Section::make('Business Details')
                ->description('Tax ID, company registry')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('tax_id')
                            ->label('Tax ID / EIN')
                            ->maxLength(255)
                            ->placeholder('12-3456789'),

                        TextInput::make('company_registry')
                            ->label('Company Registry / D-U-N-S')
                            ->maxLength(255)
                            ->placeholder('123456789'),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed()
                ->visible(fn (Get $get): bool => $get('account_type') === AccountType::COMPANY->value),

            // Section 4: Sales Settings
            Section::make('Sales Settings')
                ->description('Salesperson assignment')
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Select::make('user_id')
                        ->label('Sales Person')
                        ->options(fn () => \Webkul\Security\Models\User::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->default(fn () => Auth::id())
                        ->columnSpanFull(),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),
        ];
    }

    /**
     * Get comprehensive customer creation form components for the wizard modal
     *
     * Organized using "Don't Make Me Think" principles:
     * - Essential info always visible (name, type, contact)
     * - Progressive disclosure via collapsible sections
     * - Logical grouping with clear hierarchy
     *
     * @return array
     */
    protected function getCustomerCreationFormComponents(): array
    {
        return [
            // ========================================
            // SECTION 1: Essential Information (Always Visible)
            // The most critical fields for customer identification
            // ========================================
            Section::make('Essential Information')
                ->description('Required for customer identification')
                ->schema([
                    // Customer Type Toggle - Affects form behavior
                    Radio::make('account_type')
                        ->label('Customer Type')
                        ->inline()
                        ->options([
                            AccountType::INDIVIDUAL->value => 'Individual',
                            AccountType::COMPANY->value => 'Company',
                        ])
                        ->default(AccountType::INDIVIDUAL->value)
                        ->live()
                        ->columnSpanFull(),

                    // Dynamic Name Field based on type
                    TextInput::make('name')
                        ->label(fn (Get $get): string => $get('account_type') === AccountType::COMPANY->value
                            ? 'Company Name'
                            : 'Full Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder(fn (Get $get): string => $get('account_type') === AccountType::COMPANY->value
                            ? 'e.g. Acme Construction LLC'
                            : 'e.g. John Smith')
                        ->extraInputAttributes(['style' => 'font-size: 1.1rem;'])
                        ->columnSpanFull(),

                    // Parent Company (only for individuals)
                    Select::make('parent_id')
                        ->label('Parent Company')
                        ->options(fn () => Partner::where('account_type', AccountType::COMPANY->value)
                            ->where('sub_type', 'customer')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountType::INDIVIDUAL->value)
                        ->helperText('Associate this person with a company')
                        ->columnSpanFull(),

                    // Primary Contact Info
                    Grid::make(2)->schema([
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('e.g. (508) 555-1234'),

                        TextInput::make('mobile')
                            ->label('Mobile')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('e.g. (508) 555-5678'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('e.g. john@example.com'),

                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('e.g. https://example.com')
                            ->visible(fn (Get $get): bool => $get('account_type') === AccountType::COMPANY->value),
                    ]),
                ])
                ->compact()
                ->columns(1),

            // ========================================
            // SECTION 2: Address (Collapsible)
            // Important but can be added later
            // ========================================
            Section::make('Address')
                ->description('Billing and project location address')
                ->schema([
                    AddressAutocomplete::make('street1')
                        ->label('Street Address')
                        ->cityField('city')
                        ->stateField('state_id')
                        ->zipField('zip')
                        ->countryField('country_id')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('street2')
                        ->label('Street Address 2')
                        ->maxLength(255)
                        ->placeholder('Apt, Suite, Unit, etc.')
                        ->columnSpanFull(),

                    Grid::make(4)->schema([
                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),

                        Select::make('state_id')
                            ->label('State')
                            ->options(function (Get $get) {
                                $countryId = $get('country_id') ?: 233;
                                return \Webkul\Support\Models\State::where('country_id', $countryId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),

                        TextInput::make('zip')
                            ->label('Zip')
                            ->maxLength(255),

                        Select::make('country_id')
                            ->label('Country')
                            ->options(fn () => \Webkul\Support\Models\Country::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(233)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('state_id', null)),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),

            // ========================================
            // SECTION 3: Business Details (Collapsible)
            // For companies - Tax ID, Industry, etc.
            // ========================================
            Section::make('Business Details')
                ->description('Company information and identification')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('tax_id')
                            ->label('Tax ID / EIN')
                            ->maxLength(255)
                            ->placeholder('e.g. 12-3456789'),

                        TextInput::make('company_registry')
                            ->label('Company Registry / D-U-N-S')
                            ->maxLength(255)
                            ->placeholder('e.g. 123456789'),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('industry_id')
                            ->label('Industry')
                            ->relationship('industry', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select industry'),

                        TextInput::make('reference')
                            ->label('Internal Reference')
                            ->maxLength(255)
                            ->placeholder('Your internal code'),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed()
                ->visible(fn (Get $get): bool => $get('account_type') === AccountType::COMPANY->value),

            // ========================================
            // SECTION 4: Individual Details (Collapsible)
            // For individuals - Job title, Title (Mr/Mrs), etc.
            // ========================================
            Section::make('Personal Details')
                ->description('Additional information for this contact')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('title_id')
                            ->label('Title')
                            ->relationship('title', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Mr., Mrs., Dr., etc.'),

                        TextInput::make('job_title')
                            ->label('Job Title')
                            ->maxLength(255)
                            ->placeholder('e.g. Project Manager'),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed()
                ->visible(fn (Get $get): bool => $get('account_type') === AccountType::INDIVIDUAL->value),

            // ========================================
            // SECTION 5: Sales Settings (Collapsible)
            // Payment terms, salesperson - usually set later
            // ========================================
            Section::make('Sales Settings')
                ->description('Payment terms and sales configuration')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('user_id')
                            ->label('Sales Person')
                            ->options(fn () => \Webkul\Security\Models\User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id())
                            ->helperText('Responsible salesperson'),

                        Select::make('payment_term_id')
                            ->label('Payment Terms')
                            ->relationship('paymentTerm', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('e.g. Net 30'),
                    ]),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),

            // ========================================
            // SECTION 6: Tags (Collapsible)
            // Organization and categorization
            // ========================================
            Section::make('Tags & Notes')
                ->description('Categorize and add notes')
                ->schema([
                    Select::make('tags')
                        ->label('Tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Tag Name')
                                ->required()
                                ->maxLength(255),
                            \Filament\Forms\Components\ColorPicker::make('color')
                                ->label('Color'),
                        ])
                        ->columnSpanFull(),
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),

            // Hidden fields for proper customer creation
            Hidden::make('sub_type')
                ->default('customer'),
            Hidden::make('creator_id')
                ->default(fn () => Auth::id()),
        ];
    }

    // ========================================
    // HIERARCHICAL SPEC BUILDER HELPERS
    // ========================================

    /**
     * Get room type options
     */
    protected function getRoomTypeOptions(): array
    {
        return [
            'kitchen' => 'Kitchen',
            'bathroom' => 'Bathroom',
            'laundry' => 'Laundry',
            'office' => 'Office',
            'pantry' => 'Pantry',
            'mudroom' => 'Mudroom',
            'closet' => 'Closet',
            'bedroom' => 'Bedroom',
            'living_room' => 'Living Room',
            'dining_room' => 'Dining Room',
            'garage' => 'Garage',
            'basement' => 'Basement',
            'utility' => 'Utility',
            'other' => 'Other',
        ];
    }

    /**
     * Get location type options
     */
    protected function getLocationTypeOptions(): array
    {
        return [
            'wall' => 'Wall',
            'island' => 'Island',
            'peninsula' => 'Peninsula',
            'sink_wall' => 'Sink Wall',
            'range_wall' => 'Range Wall',
            'fridge_wall' => 'Fridge Wall',
            'pantry_wall' => 'Pantry Wall',
            'corner' => 'Corner',
        ];
    }

    /**
     * Get cabinet run type options
     */
    protected function getRunTypeOptions(): array
    {
        return [
            'base' => 'Base Cabinets',
            'wall' => 'Wall Cabinets',
            'tall' => 'Tall Cabinets',
            'vanity' => 'Vanity',
            'specialty' => 'Specialty',
        ];
    }

    /**
     * Navigate to a breadcrumb level
     * Collapses all items below that level
     */
    public function navigateToBreadcrumb(int $level): void
    {
        // Keep only the breadcrumbs up to the clicked level
        $this->specBreadcrumbs = array_slice($this->specBreadcrumbs, 0, $level + 1);
        $this->activeSpecPath = array_slice($this->activeSpecPath, 0, $level + 1);
    }

    /**
     * Set the active specification item at a given level
     * Used for accordion behavior - only one item expanded per level
     */
    public function setActiveSpecItem(string $path, string $label, int $level): void
    {
        // Set path at this level, clearing deeper levels
        $this->activeSpecPath = array_slice($this->activeSpecPath, 0, $level);
        $this->activeSpecPath[$level] = $path;

        // Update breadcrumbs
        $this->specBreadcrumbs = array_slice($this->specBreadcrumbs, 0, $level);
        $this->specBreadcrumbs[$level] = ['label' => $label, 'key' => $path];
    }

    /**
     * Check if a spec item is active (expanded)
     */
    public function isSpecItemActive(string $path): bool
    {
        return in_array($path, $this->activeSpecPath);
    }

    /**
     * Get compact adjustment schema - reusable across all hierarchy levels
     * Shows as a collapsible inline section
     */
    protected function getAdjustmentSchema(): array
    {
        return [
            Grid::make(6)->schema([
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_value')
                    ->label('Amount')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_reason')
                    ->label('Note')
                    ->placeholder('Reason...')
                    ->maxLength(50)
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),

                Placeholder::make('adjustment_preview')
                    ->label('')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none' && ($get('adjustment_value') ?? 0) > 0)
                    ->content(function (callable $get) {
                        $type = $get('adjustment_type') ?? 'none';
                        $value = (float) ($get('adjustment_value') ?? 0);

                        $label = match($type) {
                            'discount_fixed' => "-\${$value}",
                            'discount_percent' => "-{$value}%",
                            'markup_fixed' => "+\${$value}",
                            'markup_percent' => "+{$value}%",
                            default => '',
                        };

                        $color = str_starts_with($type, 'discount') ? 'text-success-600' : 'text-warning-600';

                        return new \Illuminate\Support\HtmlString(
                            "<span class=\"text-sm font-medium {$color}\">{$label}</span>"
                        );
                    })
                    ->columnSpan(2),
            ]),
        ];
    }

    /**
     * Get detailed room schema for hierarchical spec builder
     * Room level with estimated LF, pricing, and nested locations
     */
    protected function getDetailedRoomSchema(TcsPricingService $pricingService): array
    {
        return [
            // Room header row 1: Type, Name, Est LF, Level, Material, Finish
            Grid::make(6)->schema([
                Select::make('room_type')
                    ->label('Room Type')
                    ->options($this->getRoomTypeOptions())
                    ->native(false)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g. Master Bath')
                    ->columnSpan(1),

                TextInput::make('estimated_lf')
                    ->label('Est. LF')
                    ->numeric()
                    ->step(0.5)
                    ->suffix('LF')
                    ->reactive()
                    ->live(debounce: 500)
                    ->columnSpan(1),

                Select::make('cabinet_level')
                    ->label('Level')
                    ->options(fn () => $pricingService->getCabinetLevelOptions())
                    ->default('3')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                Select::make('material_category')
                    ->label('Material')
                    ->options(fn () => $pricingService->getMaterialCategoryOptions())
                    ->default('stain_grade')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                Select::make('finish_option')
                    ->label('Finish')
                    ->options(fn () => $pricingService->getFinishOptions())
                    ->default('unfinished')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),
            ]),

            // Room header row 2: Adjustment + Status
            Grid::make(6)->schema([
                // Compact adjustment inline
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_value')
                    ->label('Adj.')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_reason')
                    ->label('Reason')
                    ->placeholder('Why?')
                    ->maxLength(50)
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),

                // Missing LF indicator
                Placeholder::make('lf_status')
                    ->label('LF Status')
                    ->content(function (callable $get) {
                        $estimated = (float) ($get('estimated_lf') ?? 0);
                        $locations = $get('locations') ?? [];

                        // Calculate allocated LF from children
                        $allocated = 0;
                        foreach ($locations as $loc) {
                            $locLf = (float) ($loc['estimated_lf'] ?? 0);
                            if ($locLf > 0) {
                                $allocated += $locLf;
                            } else {
                                // Sum from runs if no location estimate
                                foreach ($loc['runs'] ?? [] as $run) {
                                    $runLf = (float) ($run['total_lf'] ?? 0);
                                    if ($runLf > 0) {
                                        $allocated += $runLf;
                                    } else {
                                        // Sum from cabinets
                                        foreach ($run['cabinets'] ?? [] as $cab) {
                                            $width = (float) ($cab['width_inches'] ?? 0);
                                            $qty = (int) ($cab['quantity'] ?? 1);
                                            $allocated += ($width / 12) * $qty;
                                        }
                                    }
                                }
                            }
                        }

                        if ($estimated <= 0) {
                            return view('webkul-project::filament.components.lf-status-badge', [
                                'status' => 'no-estimate',
                                'text' => 'No estimate',
                            ]);
                        }

                        $missing = $estimated - $allocated;
                        if (abs($missing) < 0.1) {
                            return view('webkul-project::filament.components.lf-status-badge', [
                                'status' => 'complete',
                                'text' => '✓ ' . number_format($allocated, 1) . ' LF',
                            ]);
                        } elseif ($missing > 0) {
                            return view('webkul-project::filament.components.lf-status-badge', [
                                'status' => 'missing',
                                'text' => number_format($missing, 1) . ' LF missing',
                                'allocated' => $allocated,
                                'estimated' => $estimated,
                            ]);
                        } else {
                            return view('webkul-project::filament.components.lf-status-badge', [
                                'status' => 'over',
                                'text' => number_format(abs($missing), 1) . ' LF over',
                            ]);
                        }
                    })
                    ->columnSpan(2),
            ]),

            // Nested locations - auto-calculates room's estimated_lf
            Repeater::make('locations')
                ->label('Locations')
                ->schema($this->getLocationSchema($pricingService))
                ->addActionLabel('+ Add Location')
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state) => $this->getLocationLabel($state))
                ->defaultItems(0)
                ->reactive()
                ->live(debounce: 300)
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    // Calculate total LF from all locations and update parent room
                    $totalLf = 0;
                    foreach ($state ?? [] as $location) {
                        $locLf = (float) ($location['estimated_lf'] ?? 0);
                        if ($locLf > 0) {
                            $totalLf += $locLf;
                        } else {
                            // Sum from runs if location estimate not set
                            foreach ($location['runs'] ?? [] as $run) {
                                $runLf = (float) ($run['total_lf'] ?? 0);
                                if ($runLf > 0) {
                                    $totalLf += $runLf;
                                } else {
                                    // Sum from cabinets
                                    foreach ($run['cabinets'] ?? [] as $cabinet) {
                                        $width = (float) ($cabinet['width_inches'] ?? 0);
                                        $qty = (int) ($cabinet['quantity'] ?? 1);
                                        $totalLf += ($width / 12) * $qty;
                                    }
                                }
                            }
                        }
                    }
                    // Only auto-populate if no estimate was previously set
                    $currentEstimate = (float) ($get('estimated_lf') ?? 0);
                    if ($totalLf > 0 && $currentEstimate <= 0) {
                        $set('estimated_lf', round($totalLf, 1));
                    }
                }),
        ];
    }

    /**
     * Get location schema with nested runs
     */
    protected function getLocationSchema(TcsPricingService $pricingService): array
    {
        return [
            Grid::make(5)->schema([
                Select::make('location_type')
                    ->label('Location')
                    ->options($this->getLocationTypeOptions())
                    ->native(false)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g. North Wall')
                    ->columnSpan(1),

                TextInput::make('estimated_lf')
                    ->label('Est. LF')
                    ->numeric()
                    ->step(0.5)
                    ->suffix('LF')
                    ->reactive()
                    ->live(debounce: 500)
                    ->columnSpan(1),

                // Compact adjustment
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                // LF Status for location
                Placeholder::make('lf_status')
                    ->label('Status')
                    ->content(function (callable $get) {
                        $estimated = (float) ($get('estimated_lf') ?? 0);
                        $runs = $get('runs') ?? [];

                        $allocated = 0;
                        foreach ($runs as $run) {
                            $runLf = (float) ($run['total_lf'] ?? 0);
                            if ($runLf > 0) {
                                $allocated += $runLf;
                            } else {
                                foreach ($run['cabinets'] ?? [] as $cab) {
                                    $width = (float) ($cab['width_inches'] ?? 0);
                                    $qty = (int) ($cab['quantity'] ?? 1);
                                    $allocated += ($width / 12) * $qty;
                                }
                            }
                        }

                        if ($estimated <= 0 && $allocated <= 0) {
                            return '—';
                        }

                        if ($estimated <= 0) {
                            return number_format($allocated, 1) . ' LF';
                        }

                        $missing = $estimated - $allocated;
                        if (abs($missing) < 0.1) {
                            return '✓ ' . number_format($allocated, 1) . ' LF';
                        } elseif ($missing > 0) {
                            return '⚠ ' . number_format($missing, 1) . ' missing';
                        } else {
                            return '⚠ ' . number_format(abs($missing), 1) . ' over';
                        }
                    })
                    ->columnSpan(1),
            ]),

            // Adjustment details (only when adjustment selected)
            Grid::make(5)->schema([
                Placeholder::make('loc_adj_spacer')
                    ->label('')
                    ->content('')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),

                TextInput::make('adjustment_value')
                    ->label('Adj. Amount')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0.00')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_reason')
                    ->label('Reason')
                    ->placeholder('Why?')
                    ->maxLength(50)
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),
            ]),

            // Nested cabinet runs - auto-calculates location's estimated_lf
            Repeater::make('runs')
                ->label('Cabinet Runs')
                ->schema($this->getCabinetRunSchema($pricingService))
                ->addActionLabel('+ Add Run')
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state) => $this->getRunLabel($state))
                ->defaultItems(0)
                ->reactive()
                ->live(debounce: 300)
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    // Calculate total LF from all runs and update parent location
                    $totalLf = 0;
                    foreach ($state ?? [] as $run) {
                        $runLf = (float) ($run['total_lf'] ?? 0);
                        if ($runLf > 0) {
                            $totalLf += $runLf;
                        } else {
                            // Sum from cabinets if run total not set
                            foreach ($run['cabinets'] ?? [] as $cabinet) {
                                $width = (float) ($cabinet['width_inches'] ?? 0);
                                $qty = (int) ($cabinet['quantity'] ?? 1);
                                $totalLf += ($width / 12) * $qty;
                            }
                        }
                    }
                    // Only auto-populate if no estimate was previously set
                    $currentEstimate = (float) ($get('estimated_lf') ?? 0);
                    if ($totalLf > 0 && $currentEstimate <= 0) {
                        $set('estimated_lf', round($totalLf, 1));
                    }
                }),
        ];
    }

    /**
     * Get cabinet run schema with nested cabinets
     */
    protected function getCabinetRunSchema(TcsPricingService $pricingService): array
    {
        return [
            Grid::make(5)->schema([
                Select::make('run_type')
                    ->label('Run Type')
                    ->options($this->getRunTypeOptions())
                    ->native(false)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g. Base Run 1')
                    ->columnSpan(1),

                TextInput::make('total_lf')
                    ->label('Total LF')
                    ->numeric()
                    ->step(0.5)
                    ->suffix('LF')
                    ->reactive()
                    ->live(debounce: 500)
                    ->columnSpan(1),

                // Compact adjustment
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                // Calculated LF from cabinets
                Placeholder::make('calc_lf')
                    ->label('Calc. LF')
                    ->content(function (callable $get) {
                        $totalLf = (float) ($get('total_lf') ?? 0);
                        $cabinets = $get('cabinets') ?? [];

                        $calcLf = 0;
                        foreach ($cabinets as $cab) {
                            $width = (float) ($cab['width_inches'] ?? 0);
                            $qty = (int) ($cab['quantity'] ?? 1);
                            $calcLf += ($width / 12) * $qty;
                        }

                        if (empty($cabinets)) {
                            return $totalLf > 0 ? number_format($totalLf, 1) . ' LF' : '—';
                        }

                        if ($totalLf > 0) {
                            $missing = $totalLf - $calcLf;
                            if (abs($missing) < 0.1) {
                                return '✓ ' . number_format($calcLf, 1) . ' LF';
                            } elseif ($missing > 0) {
                                return number_format($calcLf, 1) . ' (' . number_format($missing, 1) . ' missing)';
                            } else {
                                return number_format($calcLf, 1) . ' (' . number_format(abs($missing), 1) . ' over)';
                            }
                        }

                        return number_format($calcLf, 1) . ' LF';
                    })
                    ->columnSpan(1),
            ]),

            // Adjustment details (only when adjustment selected)
            Grid::make(5)->schema([
                Placeholder::make('run_adj_spacer')
                    ->label('')
                    ->content('')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),

                TextInput::make('adjustment_value')
                    ->label('Adj. Amount')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0.00')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_reason')
                    ->label('Reason')
                    ->placeholder('Why?')
                    ->maxLength(50)
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),
            ]),

            // Nested cabinets - auto-calculates run's total_lf
            Repeater::make('cabinets')
                ->label('Cabinets')
                ->schema($this->getCabinetSchema())
                ->addActionLabel('+ Add Cabinet')
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state) => $this->getCabinetLabel($state))
                ->defaultItems(0)
                ->reactive()
                ->live(debounce: 300)
                ->afterStateUpdated(function ($state, callable $set) {
                    // Calculate total LF from all cabinets and update parent run
                    $totalLf = 0;
                    foreach ($state ?? [] as $cabinet) {
                        $width = (float) ($cabinet['width_inches'] ?? 0);
                        $qty = (int) ($cabinet['quantity'] ?? 1);
                        $totalLf += ($width / 12) * $qty;
                    }
                    if ($totalLf > 0) {
                        $set('total_lf', round($totalLf, 1));
                    }
                })
                ->grid(2),
        ];
    }

    /**
     * Get cabinet schema with compact adjustment and nested sections/components
     */
    protected function getCabinetSchema(): array
    {
        return [
            // Row 1: Code, dimensions, quantity, adjustment
            Grid::make(6)->schema([
                TextInput::make('code')
                    ->label('Code')
                    ->placeholder('B24')
                    ->maxLength(20)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Parse cabinet code to extract width
                        if (preg_match('/(\d+)$/', $state ?? '', $matches)) {
                            $width = (int) $matches[1];
                            if ($width <= 48) {
                                $set('width_inches', $width);
                            }
                        }
                    })
                    ->columnSpan(1),

                TextInput::make('width_inches')
                    ->label('W')
                    ->numeric()
                    ->suffix('"')
                    ->step(0.5)
                    ->reactive()
                    ->live(debounce: 500)
                    ->columnSpan(1),

                TextInput::make('height_inches')
                    ->label('H')
                    ->numeric()
                    ->suffix('"')
                    ->step(0.5)
                    ->columnSpan(1),

                TextInput::make('depth_inches')
                    ->label('D')
                    ->numeric()
                    ->suffix('"')
                    ->step(0.5)
                    ->default(24)
                    ->columnSpan(1),

                TextInput::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->reactive()
                    ->live(debounce: 500)
                    ->columnSpan(1),

                // Compact adjustment
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),
            ]),

            // Row 2: Adjustment details (only when adjustment selected)
            Grid::make(6)->schema([
                Placeholder::make('cab_adj_spacer')
                    ->label('')
                    ->content('')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(3),

                TextInput::make('adjustment_value')
                    ->label('Amount')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_reason')
                    ->label('Reason')
                    ->placeholder('Why?')
                    ->maxLength(50)
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(2),
            ]),

            // Nested sections
            Repeater::make('sections')
                ->label('Sections')
                ->schema($this->getSectionSchema())
                ->addActionLabel('+ Section')
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state) => $this->getSectionLabel($state))
                ->defaultItems(0)
                ->reactive(),
        ];
    }

    /**
     * Get section schema (doors, drawers, shelves, etc.)
     */
    protected function getSectionSchema(): array
    {
        return [
            Grid::make(5)->schema([
                Select::make('section_type')
                    ->label('Type')
                    ->options([
                        'door' => 'Door',
                        'drawer' => 'Drawer',
                        'shelf' => 'Shelf',
                        'pullout' => 'Pull-out',
                        'panel' => 'Panel',
                        'appliance' => 'Appliance Opening',
                        'other' => 'Other',
                    ])
                    ->native(false)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g. Upper Door')
                    ->columnSpan(1),

                TextInput::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->columnSpan(1),

                // Compact adjustment
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),

                TextInput::make('adjustment_value')
                    ->label('Amt')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(1),
            ]),

            // Nested components
            Repeater::make('components')
                ->label('Components')
                ->schema($this->getComponentSchema())
                ->addActionLabel('+ Component')
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->itemLabel(fn (array $state) => $this->getComponentLabel($state))
                ->defaultItems(0)
                ->reactive(),
        ];
    }

    /**
     * Get component schema (hinges, slides, handles, etc.)
     */
    protected function getComponentSchema(): array
    {
        return [
            Grid::make(5)->schema([
                Select::make('component_type')
                    ->label('Type')
                    ->options([
                        'hinge' => 'Hinge',
                        'slide' => 'Drawer Slide',
                        'handle' => 'Handle/Pull',
                        'knob' => 'Knob',
                        'soft_close' => 'Soft Close',
                        'shelf_pin' => 'Shelf Pin',
                        'bracket' => 'Bracket',
                        'other' => 'Other',
                    ])
                    ->native(false)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Name/SKU')
                    ->placeholder('e.g. Blum 110°')
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->columnSpan(1),

                // Compact adjustment
                Select::make('adjustment_type')
                    ->label('±')
                    ->options([
                        'none' => '—',
                        'discount_fixed' => '-$',
                        'discount_percent' => '-%',
                        'markup_fixed' => '+$',
                        'markup_percent' => '+%',
                    ])
                    ->default('none')
                    ->native(false)
                    ->reactive()
                    ->columnSpan(1),
            ]),

            // Adjustment value (only when type selected)
            Grid::make(5)->schema([
                Placeholder::make('comp_adj_spacer')
                    ->label('')
                    ->content('')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(3),

                TextInput::make('adjustment_value')
                    ->label('Amount')
                    ->numeric()
                    ->step(0.01)
                    ->placeholder('0')
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(1),

                TextInput::make('adjustment_reason')
                    ->label('Note')
                    ->placeholder('Why?')
                    ->maxLength(30)
                    ->visible(fn (callable $get) => ($get('adjustment_type') ?? 'none') !== 'none')
                    ->columnSpan(1),
            ]),
        ];
    }

    /**
     * Get section label for display
     */
    protected function getSectionLabel(array $state): string
    {
        $type = $state['section_type'] ?? 'Section';
        $name = $state['name'] ?? '';
        $qty = (int) ($state['quantity'] ?? 1);

        $label = ucfirst(str_replace('_', ' ', $type));
        if ($name) {
            $label .= ": {$name}";
        }
        if ($qty > 1) {
            $label .= " (×{$qty})";
        }

        // Show adjustment indicator
        $adjType = $state['adjustment_type'] ?? 'none';
        $adjValue = (float) ($state['adjustment_value'] ?? 0);
        if ($adjType !== 'none' && $adjValue > 0) {
            $label .= match($adjType) {
                'discount_fixed' => " -\${$adjValue}",
                'discount_percent' => " -{$adjValue}%",
                'markup_fixed' => " +\${$adjValue}",
                'markup_percent' => " +{$adjValue}%",
                default => '',
            };
        }

        return $label;
    }

    /**
     * Get component label for display
     */
    protected function getComponentLabel(array $state): string
    {
        $type = $state['component_type'] ?? 'Component';
        $name = $state['name'] ?? '';
        $qty = (int) ($state['quantity'] ?? 1);

        $label = ucfirst(str_replace('_', ' ', $type));
        if ($name) {
            $label .= ": {$name}";
        }
        if ($qty > 1) {
            $label .= " (×{$qty})";
        }

        // Show adjustment indicator
        $adjType = $state['adjustment_type'] ?? 'none';
        $adjValue = (float) ($state['adjustment_value'] ?? 0);
        if ($adjType !== 'none' && $adjValue > 0) {
            $label .= match($adjType) {
                'discount_fixed' => " -\${$adjValue}",
                'discount_percent' => " -{$adjValue}%",
                'markup_fixed' => " +\${$adjValue}",
                'markup_percent' => " +{$adjValue}%",
                default => '',
            };
        }

        return $label;
    }

    /**
     * Get room label with LF tracking for detailed mode
     */
    protected function getRoomLabel(array $state, TcsPricingService $pricingService): string
    {
        $roomType = $state['room_type'] ?? 'Room';
        $name = $state['name'] ?? '';
        $estimatedLf = (float) ($state['estimated_lf'] ?? 0);

        // Calculate allocated LF from children
        $allocatedLf = 0;
        foreach ($state['locations'] ?? [] as $loc) {
            $locLf = (float) ($loc['estimated_lf'] ?? 0);
            if ($locLf > 0) {
                $allocatedLf += $locLf;
            } else {
                foreach ($loc['runs'] ?? [] as $run) {
                    $runLf = (float) ($run['total_lf'] ?? 0);
                    if ($runLf > 0) {
                        $allocatedLf += $runLf;
                    } else {
                        foreach ($run['cabinets'] ?? [] as $cab) {
                            $width = (float) ($cab['width_inches'] ?? 0);
                            $qty = (int) ($cab['quantity'] ?? 1);
                            $allocatedLf += ($width / 12) * $qty;
                        }
                    }
                }
            }
        }

        $label = ucfirst(str_replace('_', ' ', $roomType));
        if ($name) {
            $label .= " - {$name}";
        }

        if ($estimatedLf > 0) {
            $missing = $estimatedLf - $allocatedLf;
            if (abs($missing) < 0.1) {
                $label .= " | ✓ {$estimatedLf} LF";
            } elseif ($missing > 0) {
                $label .= " | {$allocatedLf}/{$estimatedLf} LF (" . number_format($missing, 1) . " missing)";
            } else {
                $label .= " | {$allocatedLf}/{$estimatedLf} LF (" . number_format(abs($missing), 1) . " over)";
            }
        } elseif ($allocatedLf > 0) {
            $label .= " | {$allocatedLf} LF";
        }

        return $label;
    }

    /**
     * Get location label
     */
    protected function getLocationLabel(array $state): string
    {
        $type = $state['location_type'] ?? 'location';
        $name = $state['name'] ?? '';
        $estimatedLf = (float) ($state['estimated_lf'] ?? 0);

        // Calculate from runs/cabinets
        $calcLf = 0;
        foreach ($state['runs'] ?? [] as $run) {
            $runLf = (float) ($run['total_lf'] ?? 0);
            if ($runLf > 0) {
                $calcLf += $runLf;
            } else {
                foreach ($run['cabinets'] ?? [] as $cab) {
                    $width = (float) ($cab['width_inches'] ?? 0);
                    $qty = (int) ($cab['quantity'] ?? 1);
                    $calcLf += ($width / 12) * $qty;
                }
            }
        }

        $label = ucfirst(str_replace('_', ' ', $type));
        if ($name) {
            $label .= " - {$name}";
        }

        if ($estimatedLf > 0 || $calcLf > 0) {
            $displayLf = $estimatedLf > 0 ? $estimatedLf : $calcLf;
            $label .= " | " . number_format($displayLf, 1) . " LF";

            if ($estimatedLf > 0 && $calcLf > 0) {
                $diff = $estimatedLf - $calcLf;
                if (abs($diff) >= 0.1) {
                    $label .= $diff > 0 ? " ({$diff} missing)" : " (" . abs($diff) . " over)";
                }
            }
        }

        return $label;
    }

    /**
     * Get run label
     */
    protected function getRunLabel(array $state): string
    {
        $type = $state['run_type'] ?? 'run';
        $name = $state['name'] ?? '';
        $totalLf = (float) ($state['total_lf'] ?? 0);

        // Calculate from cabinets
        $calcLf = 0;
        foreach ($state['cabinets'] ?? [] as $cab) {
            $width = (float) ($cab['width_inches'] ?? 0);
            $qty = (int) ($cab['quantity'] ?? 1);
            $calcLf += ($width / 12) * $qty;
        }

        $cabinetCount = count($state['cabinets'] ?? []);

        $typeLabels = [
            'base' => 'Base',
            'wall' => 'Wall',
            'tall' => 'Tall',
            'vanity' => 'Vanity',
            'specialty' => 'Specialty',
        ];

        $label = $typeLabels[$type] ?? ucfirst($type);
        if ($name) {
            $label .= " - {$name}";
        }

        $displayLf = $totalLf > 0 ? $totalLf : $calcLf;
        if ($displayLf > 0) {
            $label .= " | " . number_format($displayLf, 1) . " LF";
        }

        if ($cabinetCount > 0) {
            $label .= " ({$cabinetCount} cabinet" . ($cabinetCount > 1 ? 's' : '') . ")";
        }

        return $label;
    }

    /**
     * Get cabinet label
     */
    protected function getCabinetLabel(array $state): string
    {
        $code = $state['code'] ?? '';
        $width = $state['width_inches'] ?? 0;
        $qty = (int) ($state['quantity'] ?? 1);

        if ($code) {
            $label = $code;
        } elseif ($width) {
            $label = $width . '"';
        } else {
            $label = 'Cabinet';
        }

        if ($qty > 1) {
            $label .= " ×{$qty}";
        }

        if ($width) {
            $lf = ($width / 12) * $qty;
            $label .= " = " . number_format($lf, 2) . " LF";
        }

        // Show adjustment indicator
        $adjustmentType = $state['adjustment_type'] ?? 'none';
        $adjustmentValue = (float) ($state['adjustment_value'] ?? 0);

        if ($adjustmentType !== 'none' && $adjustmentValue > 0) {
            switch ($adjustmentType) {
                case 'discount_fixed':
                    $label .= " (-\${$adjustmentValue})";
                    break;
                case 'discount_percent':
                    $label .= " (-{$adjustmentValue}%)";
                    break;
                case 'markup_fixed':
                    $label .= " (+\${$adjustmentValue})";
                    break;
                case 'markup_percent':
                    $label .= " (+{$adjustmentValue}%)";
                    break;
            }
        }

        return $label;
    }

    /**
     * Create entities from detailed spec mode
     */
    protected function createEntitiesFromDetailedSpec(Project $project, array $specRooms): void
    {
        $pricingService = app(TcsPricingService::class);
        $roomSort = 1;

        foreach ($specRooms as $roomData) {
            // Calculate room's total LF
            $roomLf = (float) ($roomData['estimated_lf'] ?? 0);
            $cabinetLevel = $roomData['cabinet_level'] ?? '3';

            // If no estimate, calculate from children
            if ($roomLf <= 0) {
                foreach ($roomData['locations'] ?? [] as $loc) {
                    $locLf = (float) ($loc['estimated_lf'] ?? 0);
                    if ($locLf > 0) {
                        $roomLf += $locLf;
                    } else {
                        foreach ($loc['runs'] ?? [] as $run) {
                            $runLf = (float) ($run['total_lf'] ?? 0);
                            if ($runLf > 0) {
                                $roomLf += $runLf;
                            } else {
                                foreach ($run['cabinets'] ?? [] as $cab) {
                                    $width = (float) ($cab['width_inches'] ?? 0);
                                    $qty = (int) ($cab['quantity'] ?? 1);
                                    $roomLf += ($width / 12) * $qty;
                                }
                            }
                        }
                    }
                }
            }

            $tierColumn = "total_linear_feet_tier_{$cabinetLevel}";

            // Create Room
            $room = $project->rooms()->create([
                'name' => $roomData['name'] ?? null,
                'room_type' => $roomData['room_type'] ?? 'other',
                'cabinet_level' => $cabinetLevel,
                'sort_order' => $roomSort++,
                $tierColumn => $roomLf,
                'creator_id' => Auth::id(),
            ]);

            $locSort = 1;
            foreach ($roomData['locations'] ?? [] as $locData) {
                // Create Room Location
                $location = $room->locations()->create([
                    'name' => $locData['name'] ?? null,
                    'location_type' => $locData['location_type'] ?? 'wall',
                    'total_linear_feet' => (float) ($locData['estimated_lf'] ?? 0),
                    'sort_order' => $locSort++,
                    'creator_id' => Auth::id(),
                ]);

                $runSort = 1;
                foreach ($locData['runs'] ?? [] as $runData) {
                    // Create Cabinet Run
                    $run = $location->cabinetRuns()->create([
                        'name' => $runData['name'] ?? null,
                        'run_type' => $runData['run_type'] ?? 'base',
                        'total_linear_feet' => (float) ($runData['total_lf'] ?? 0),
                        'sort_order' => $runSort++,
                        'creator_id' => Auth::id(),
                    ]);

                    $cabSort = 1;
                    foreach ($runData['cabinets'] ?? [] as $cabData) {
                        // Create Cabinet
                        $width = (float) ($cabData['width_inches'] ?? 0);
                        $qty = (int) ($cabData['quantity'] ?? 1);
                        $lf = ($width / 12) * $qty;

                        // Calculate adjustment if present
                        $adjustmentType = $cabData['adjustment_type'] ?? 'none';
                        $adjustmentValue = (float) ($cabData['adjustment_value'] ?? 0);
                        $adjustmentReason = $cabData['adjustment_reason'] ?? null;
                        $adjustmentAmount = null;
                        $finalPrice = null;

                        // Get base pricing from room settings
                        $baseRate = $pricingService->getBaseRate($cabinetLevel) ?? 192;
                        $basePrice = $lf * $baseRate;

                        if ($adjustmentType !== 'none' && $adjustmentValue > 0) {
                            switch ($adjustmentType) {
                                case 'discount_fixed':
                                    $adjustmentAmount = -$adjustmentValue;
                                    break;
                                case 'discount_percent':
                                    $adjustmentAmount = -($basePrice * ($adjustmentValue / 100));
                                    break;
                                case 'markup_fixed':
                                    $adjustmentAmount = $adjustmentValue;
                                    break;
                                case 'markup_percent':
                                    $adjustmentAmount = $basePrice * ($adjustmentValue / 100);
                                    break;
                            }
                            $finalPrice = $basePrice + $adjustmentAmount;
                        }

                        $run->cabinets()->create([
                            'project_id' => $project->id,
                            'room_id' => $room->id,
                            'cabinet_number' => $cabData['code'] ?? null,
                            'length_inches' => $width,
                            'width_inches' => $width,
                            'height_inches' => $cabData['height_inches'] ?? 30,
                            'depth_inches' => $cabData['depth_inches'] ?? 24,
                            'linear_feet' => $width / 12,
                            'quantity' => $qty,
                            'position_in_run' => $cabSort++,
                            'unit_price_per_lf' => $baseRate,
                            'total_price' => $basePrice,
                            'adjustment_type' => $adjustmentType,
                            'adjustment_value' => $adjustmentValue > 0 ? $adjustmentValue : null,
                            'adjustment_reason' => $adjustmentReason,
                            'adjustment_amount' => $adjustmentAmount,
                            'final_price' => $finalPrice,
                            'creator_id' => Auth::id(),
                        ]);
                    }
                }
            }
        }

        \Log::info('CreateProject: Created entities from detailed spec', [
            'project_id' => $project->id,
            'rooms_count' => count($specRooms),
        ]);
    }
}
