<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use App\Forms\Components\AddressAutocomplete;
use App\Forms\Components\TagSelectorPanel;
use App\Services\ProductionEstimatorService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
     * Define the wizard form schema
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    // Step 1: Quick Capture (Required - Target 60 seconds)
                    Step::make('Quick Capture')
                        ->description('Customer, address & project type')
                        ->icon('heroicon-o-bolt')
                        ->schema($this->getStep1Schema())
                        ->afterValidation(fn () => $this->saveDraft(1)),

                    // Step 2: Scope & Budget (Required - Target 2-3 minutes)
                    Step::make('Scope & Budget')
                        ->description('Linear feet & budget estimate')
                        ->icon('heroicon-o-calculator')
                        ->schema($this->getStep2Schema())
                        ->afterValidation(fn () => $this->saveDraft(2)),

                    // Step 3: Timeline (Skippable)
                    Step::make('Timeline')
                        ->description('Dates & project manager')
                        ->icon('heroicon-o-calendar')
                        ->schema($this->getStep3Schema())
                        ->afterValidation(fn () => $this->saveDraft(3)),

                    // Step 4: Documents & Tags (Skippable)
                    Step::make('Documents')
                        ->description('PDFs, tags & notes')
                        ->icon('heroicon-o-document-text')
                        ->schema($this->getStep4Schema())
                        ->afterValidation(fn () => $this->saveDraft(4)),

                    // Step 5: Review & Create
                    Step::make('Review & Create')
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

            Grid::make(2)->schema([
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
                    ->helperText('Warehouse for material allocation'),

                Select::make('partner_id')
                    ->label('Customer')
                    ->options(fn () => Partner::where('sub_type', 'customer')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(onBlur: true)
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
                ->schema([
                    Toggle::make('use_customer_address')
                        ->label('Use customer address')
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
                                    return [];
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

            // Project Preview
            Section::make('Project Preview')
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('project_number_preview')
                            ->label('Project Number')
                            ->content(fn (callable $get) => $get('project_number') ?: 'Auto-generated'),
                        Placeholder::make('project_name_preview')
                            ->label('Project Name')
                            ->content(fn (callable $get) => $get('name') ?: 'Auto-generated from address & type'),
                    ]),
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
                ])
                ->compact()
                ->collapsible()
                ->collapsed(),
        ];
    }

    /**
     * Step 2: Scope & Budget - Linear Feet, Budget Range, Complexity Score
     */
    protected function getStep2Schema(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('estimated_linear_feet')
                    ->label('Estimated Linear Feet')
                    ->suffix('LF')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $this->calculateEstimatedProductionTime($state, $get, $set);
                        if ($state && $get('company_id')) {
                            $estimate = ProductionEstimatorService::calculate($state, $get('company_id'));
                            if ($estimate) {
                                $set('allocated_hours', $estimate['hours']);
                            }
                        }
                    })
                    ->helperText('Enter the estimated total linear feet for this project'),

                Select::make('budget_range')
                    ->label('Budget Range')
                    ->options(BudgetRange::options())
                    ->native(false)
                    ->helperText('Approximate project budget'),
            ]),

            Grid::make(2)->schema([
                TextInput::make('complexity_score')
                    ->label('Complexity Score')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10)
                    ->step(1)
                    ->placeholder('1-10')
                    ->helperText('1 = Simple, 10 = Highly complex (affects production time)'),

                TextInput::make('allocated_hours')
                    ->label('Allocated Hours')
                    ->suffixIcon('heroicon-o-clock')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Auto-calculated from linear feet')
                    ->visible(app(TimeSettings::class)->enable_timesheets),
            ]),

            // Quick Estimate Panel
            Section::make('Quick Estimate')
                ->schema([
                    Placeholder::make('quick_estimate')
                        ->label('')
                        ->content(function (callable $get) {
                            $linearFeet = $get('estimated_linear_feet');
                            $companyId = $get('company_id');

                            if (!$linearFeet || !$companyId) {
                                return 'Enter linear feet to see estimate';
                            }

                            // Calculate estimate using TcsPricingService defaults ($348/LF)
                            $baseRate = 348; // Level 3 + Stain Grade
                            $quickEstimate = $linearFeet * $baseRate;

                            $estimate = ProductionEstimatorService::calculate($linearFeet, $companyId);
                            $productionTime = $estimate ? $estimate['formatted'] : 'N/A';

                            return view('webkul-project::filament.components.quick-estimate-panel', [
                                'linearFeet' => $linearFeet,
                                'baseRate' => $baseRate,
                                'quickEstimate' => $quickEstimate,
                                'productionTime' => $productionTime,
                            ]);
                        }),
                ])
                ->compact(),

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
     * Save draft at current step
     */
    protected function saveDraft(int $step): void
    {
        $data = $this->form->getState();

        if (!$this->draft) {
            $this->draft = ProjectDraft::create([
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'current_step' => $step,
                'form_data' => $data,
                'expires_at' => now()->addDays(7),
            ]);
        } else {
            $this->draft->update([
                'current_step' => $step,
                'form_data' => $data,
            ]);
        }
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
}
