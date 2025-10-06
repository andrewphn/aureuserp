<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Forms\Components\TagSelectorPanel;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductionEstimatorService;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Partner\Filament\Resources\PartnerResource;
use Webkul\Project\Enums\ProjectVisibility;
use Webkul\Project\Filament\Clusters\Configurations\Resources\TagResource;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\CreateProject;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\EditProject;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ListProjects;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ManageMilestones;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ManageTasks;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ViewProject;
use Webkul\Project\Filament\Resources\ProjectResource\RelationManagers\MilestonesRelationManager;
use Webkul\Project\Filament\Resources\ProjectResource\RelationManagers\TaskStagesRelationManager;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Settings\TaskSettings;
use Webkul\Project\Settings\TimeSettings;
use Webkul\Security\Filament\Resources\CompanyResource;
use Webkul\Security\Filament\Resources\UserResource;

class ProjectResource extends Resource
{
    use HasCustomFields;

    protected static ?string $model = Project::class;

    protected static ?string $slug = 'project/projects';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('projects::filament/resources/project.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('projects::filament/resources/project.navigation.group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'user.name', 'partner.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('projects::filament/resources/project.global-search.project-manager') => $record->user?->name ?? '—',
            __('projects::filament/resources/project.global-search.customer')        => $record->partner?->name ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        ProgressStepper::make('stage_id')
                            ->hiddenLabel()
                            ->inline()
                            ->required()
                            ->visible(static::getTaskSettings()->enable_project_stages)
                            ->options(fn () => ProjectStage::orderBy('sort')->get()->mapWithKeys(fn ($stage) => [$stage->id => $stage->name]))
                            ->colors(fn () => ProjectStage::orderBy('sort')->get()->mapWithKeys(fn ($stage) => [
                                $stage->id => $stage->color ? Color::generateV3Palette($stage->color) : 'gray'
                            ]))
                            ->default(ProjectStage::first()?->id),
                        Section::make('Project Details')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('company_id')
                                        ->relationship('company', 'name', fn ($query) => $query->whereNull('parent_id'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Clear branch selection when company changes
                                            $set('branch_id', null);
                                            // Update project number preview when company changes
                                            static::updateProjectNumberPreview($state, $get, $set);
                                            // Trigger production time recalculation
                                            static::calculateEstimatedProductionTime($get('estimated_linear_feet'), $get, $set);
                                        })
                                        ->label(__('projects::filament/resources/project.form.sections.additional.fields.company'))
                                        ->createOptionForm(fn (Schema $schema) => CompanyResource::form($schema)),
                                    Select::make('branch_id')
                                        ->label('Branch')
                                        ->options(function (callable $get) {
                                            $companyId = $get('company_id');
                                            if (!$companyId) {
                                                return [];
                                            }
                                            return \Webkul\Support\Models\Company::where('parent_id', $companyId)
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->reactive()
                                        ->visible(fn (callable $get) => $get('company_id') !== null)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Update project number preview when branch changes
                                            static::updateProjectNumberPreview($get('company_id'), $get, $set);
                                            // Update project name when branch changes
                                            static::updateProjectName($get, $set);
                                            // Trigger production time recalculation using branch capacity if selected
                                            static::calculateEstimatedProductionTime($get('estimated_linear_feet'), $get, $set);
                                        })
                                        ->helperText('Optional: Select a specific branch if applicable'),
                                    Select::make('partner_id')
                                        ->label(__('projects::filament/resources/project.form.sections.additional.fields.customer'))
                                        ->relationship('partner', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if ($state && $get('use_customer_address')) {
                                                $partner = \Webkul\Partner\Models\Partner::with(['state', 'country'])->find($state);
                                                if ($partner) {
                                                    $set('project_address.street1', $partner->street1);
                                                    $set('project_address.street2', $partner->street2);
                                                    $set('project_address.city', $partner->city);
                                                    $set('project_address.zip', $partner->zip);
                                                    $set('project_address.country_id', $partner->country_id);
                                                    $set('project_address.state_id', $partner->state_id);

                                                    // Update project number preview
                                                    static::updateProjectNumberPreview($get('company_id'), $get, $set);
                                                    // Update project name
                                                    static::updateProjectName($get, $set);
                                                }
                                            }
                                        })
                                        ->createOptionForm(fn (Schema $schema) => PartnerResource::form($schema))
                                        ->editOptionForm(fn (Schema $schema) => PartnerResource::form($schema)),
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
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Update project name when project type changes
                                            static::updateProjectName($get, $set);
                                        })
                                        ->native(false),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('project_number')
                                        ->label('Project Number')
                                        ->placeholder('Auto-generated if left blank')
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    TextInput::make('name')
                                        ->label(__('projects::filament/resources/project.form.sections.general.fields.name'))
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder(__('projects::filament/resources/project.form.sections.general.fields.name-placeholder')),
                                ]),
                                RichEditor::make('description')
                                    ->label(__('projects::filament/resources/project.form.sections.general.fields.description')),
                            ]),

                        Section::make('Project Location')
                            ->schema([
                                Toggle::make('use_customer_address')
                                    ->label('Use customer address for project location')
                                    ->default(true)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        // Only auto-populate if this is NOT an edit page with existing address
                                        $isEditPage = $livewire instanceof \Filament\Resources\Pages\EditRecord;
                                        $hasExistingAddress = false;

                                        if ($isEditPage && $livewire->record) {
                                            $hasExistingAddress = $livewire->record->addresses()->count() > 0;
                                        }

                                        // Don't override if editing and address exists in database
                                        if ($isEditPage && $hasExistingAddress) {
                                            return;
                                        }

                                        if ($state) {
                                            // Populate from customer
                                            $partnerId = $get('partner_id');
                                            if ($partnerId) {
                                                $partner = \Webkul\Partner\Models\Partner::with(['state', 'country'])->find($partnerId);
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
                                            // Clear fields only if not editing with existing address
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
                                TextInput::make('project_address.street1')
                                    ->label('Street Address Line 1')
                                    ->disabled(fn (callable $get) => $get('use_customer_address'))
                                    ->dehydrated()
                                    ->columnSpanFull(),
                                TextInput::make('project_address.street2')
                                    ->label('Street Address Line 2')
                                    ->disabled(fn (callable $get) => $get('use_customer_address'))
                                    ->dehydrated()
                                    ->columnSpanFull(),
                                Grid::make(3)->schema([
                                    TextInput::make('project_address.city')
                                        ->label('City')
                                        ->disabled(fn (callable $get) => $get('use_customer_address'))
                                        ->dehydrated(),
                                    Select::make('project_address.country_id')
                                        ->label('Country')
                                        ->options(\Webkul\Support\Models\Country::pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function (callable $set) {
                                            $set('project_address.state_id', null);
                                        })
                                        ->disabled(fn (callable $get) => $get('use_customer_address'))
                                        ->dehydrated(),
                                    Select::make('project_address.state_id')
                                        ->label('State')
                                        ->options(function (callable $get) {
                                            $countryId = $get('project_address.country_id');
                                            if (!$countryId) {
                                                return [];
                                            }
                                            return \Webkul\Support\Models\State::where('country_id', $countryId)
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->disabled(fn (callable $get) => $get('use_customer_address'))
                                        ->dehydrated(),
                                    TextInput::make('project_address.zip')
                                        ->label('Zip Code')
                                        ->disabled(fn (callable $get) => $get('use_customer_address'))
                                        ->dehydrated(),
                                ]),
                            ]),

                        Section::make('Timeline & Scope')
                            ->schema(static::mergeCustomFormFields([
                                TextInput::make('estimated_linear_feet')
                                    ->label('Estimated Linear Feet')
                                    ->suffix('ft')
                                    ->minValue(0)
                                    ->numeric()
                                    ->step(0.01)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Calculate estimated production time and update allocated hours
                                        static::calculateEstimatedProductionTime($state, $get, $set);

                                        // Auto-populate allocated_hours based on linear feet
                                        if ($state && $get('company_id')) {
                                            $estimate = ProductionEstimatorService::calculate($state, $get('company_id'));
                                            if ($estimate) {
                                                $set('allocated_hours', $estimate['hours']);
                                            }
                                        }
                                    })
                                    ->helperText('Estimated total linear feet for this project')
                                    ->rules(['nullable', 'numeric', 'min:0']),
                                DatePicker::make('start_date')
                                    ->label(__('projects::filament/resources/project.form.sections.additional.fields.start-date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-suggest desired completion date based on production estimate
                                        if ($state && $get('estimated_linear_feet') && $get('company_id')) {
                                            $estimate = ProductionEstimatorService::calculate($get('estimated_linear_feet'), $get('company_id'));
                                            if ($estimate) {
                                                // Get company calendar to calculate actual working days
                                                $companyId = $get('company_id');
                                                $calendar = \DB::selectOne('SELECT id FROM employees_calendars WHERE company_id = ? AND deleted_at IS NULL LIMIT 1', [$companyId]);

                                                if ($calendar) {
                                                    // Get working days from calendar
                                                    $workingDayNames = \DB::select('SELECT DISTINCT LOWER(day_of_week) as day_of_week FROM employees_calendar_attendances WHERE calendar_id = ?', [$calendar->id]);
                                                    $workingDayNames = array_map(fn($row) => $row->day_of_week, $workingDayNames);

                                                    $dayMap = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0];
                                                    $workingDayNumbers = array_map(fn($day) => $dayMap[$day] ?? null, $workingDayNames);
                                                    $workingDayNumbers = array_filter($workingDayNumbers, fn($num) => $num !== null);
                                                } else {
                                                    $workingDayNumbers = [1, 2, 3, 4]; // Mon-Thu fallback
                                                }

                                                // Calculate desired completion date by adding working days
                                                $daysNeeded = ceil($estimate['days']);
                                                $startDate = \Carbon\Carbon::parse($state);
                                                $currentDate = $startDate->copy();
                                                $workingDaysAdded = 0;

                                                while ($workingDaysAdded < $daysNeeded) {
                                                    $currentDate->addDay();
                                                    if (in_array($currentDate->dayOfWeek, $workingDayNumbers)) {
                                                        $workingDaysAdded++;
                                                    }
                                                }

                                                $set('desired_completion_date', $currentDate->format('Y-m-d'));
                                            }
                                        }

                                        // Trigger production time recalculation when start date changes
                                        static::calculateEstimatedProductionTime($get('estimated_linear_feet'), $get, $set);
                                    }),
                                DatePicker::make('desired_completion_date')
                                    ->label('Desired Completion Date')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Trigger production time recalculation when desired completion date changes
                                        static::calculateEstimatedProductionTime($get('estimated_linear_feet'), $get, $set);
                                    })
                                    ->helperText(function (callable $get) {
                                        $linearFeet = $get('estimated_linear_feet');
                                        $companyId = $get('company_id');

                                        if (!$linearFeet || !$companyId) {
                                            return null;
                                        }

                                        $estimate = \App\Services\ProductionEstimatorService::calculate($linearFeet, $companyId);
                                        if (!$estimate) {
                                            return null;
                                        }

                                        return "⚠️ Production time needed: {$estimate['formatted']}";
                                    }),
                                TextInput::make('allocated_hours')
                                    ->label(__('projects::filament/resources/project.form.sections.additional.fields.allocated-hours'))
                                    ->suffixIcon('heroicon-o-clock')
                                    ->minValue(0)
                                    ->numeric()
                                    ->helperText(__('projects::filament/resources/project.form.sections.additional.fields.allocated-hours-helper-text'))
                                    ->visible(static::getTimeSettings()->enable_timesheets)
                                    ->rules(['nullable', 'numeric', 'min:0']),
                                Select::make('user_id')
                                    ->label(__('projects::filament/resources/project.form.sections.additional.fields.project-manager'))
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm(fn (Schema $schema) => UserResource::form($schema)),
                            ]))
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        // Commented out - Project Overview moved to sticky footer
                        // Section::make('Project Overview')
                        //     ->schema([
                        //         \Filament\Forms\Components\Placeholder::make('summary_company')
                        //             ->label('Company')
                        //             ->content(fn (callable $get) => \Webkul\Support\Models\Company::find($get('company_id'))?->name ?? '—'),
                        //         \Filament\Forms\Components\Placeholder::make('summary_customer')
                        //             ->label('Customer')
                        //             ->content(fn (callable $get) => \Webkul\Partner\Models\Partner::find($get('partner_id'))?->name ?? '—'),
                        //         \Filament\Forms\Components\Placeholder::make('summary_project_type')
                        //             ->label('Type')
                        //             ->content(fn (callable $get) => ucfirst($get('project_type') ?? '—')),
                        //         \Filament\Forms\Components\Placeholder::make('summary_linear_feet')
                        //             ->label('Linear Feet')
                        //             ->content(fn (callable $get) => $get('estimated_linear_feet') ? $get('estimated_linear_feet') . ' LF' : '—'),

                        //         \Filament\Forms\Components\ViewField::make('estimated_production_info')
                        //             ->view('filament.forms.components.production-estimate-card')
                        //             ->viewData(function (callable $get) {
                        //                 $linearFeet = $get('estimated_linear_feet');
                        //                 $companyId = $get('company_id');

                        //                 if (!$linearFeet || !$companyId) {
                        //                     return ['estimate' => null];
                        //                 }

                        //                 // Always use parent company capacity for production calculations
                        //                 $estimate = ProductionEstimatorService::calculate($linearFeet, $companyId);

                        //                 return ['estimate' => $estimate, 'linearFeet' => $linearFeet];
                        //             }),
                        //     ])
                        //     ->compact()
                        //     ->columns(1)
                        //     ->collapsible()
                        //     ->collapsed(false)
                        //     ->extraAttributes([
                        //         'style' => 'position: sticky; top: 5rem; max-height: calc(100vh - 6rem); overflow-y: auto;'
                        //     ]),

                        Section::make('Architectural PDF Upload')
                            ->description('Upload architectural plans, blueprints, or technical drawings. PDFs will be available for viewing and annotation after project creation.')
                            ->schema([
                                \Filament\Forms\Components\FileUpload::make('architectural_pdfs')
                                    ->label('Upload PDF Documents')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(51200) // 50MB in KB
                                    ->disk('public')
                                    ->directory('pdf-documents')
                                    ->multiple()
                                    ->reorderable()
                                    ->downloadable()
                                    ->openable()
                                    ->previewable(false)
                                    ->helperText('Maximum file size: 50MB per file. You can upload multiple PDFs.')
                                    ->columnSpanFull()
                                    ->visible(fn ($context) => $context === 'create'),

                                \Filament\Forms\Components\ViewField::make('uploaded_pdfs')
                                    ->label('Uploaded PDF Documents')
                                    ->view('filament.forms.components.uploaded-pdfs-list')
                                    ->viewData(fn ($record) => [
                                        'pdfs' => $record?->pdfDocuments()->get() ?? collect(),
                                    ])
                                    ->visible(fn ($context) => $context === 'edit')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(false),

                        Section::make('Project Tags')
                            ->schema([
                                TagSelectorPanel::make('tags')
                                    ->label(__('projects::filament/resources/project.form.sections.additional.fields.tags'))
                                    ->columnSpan('full'),
                            ])
                            ->collapsible()
                            ->collapsed(false),

                        Section::make(__('projects::filament/resources/project.form.sections.settings.title'))
                            ->schema([
                                Radio::make('visibility')
                                    ->label(__('projects::filament/resources/project.form.sections.settings.fields.visibility'))
                                    ->default('internal')
                                    ->options(ProjectVisibility::options())
                                    ->descriptions([
                                        'private'  => __('projects::filament/resources/project.form.sections.settings.fields.private-description'),
                                        'internal' => __('projects::filament/resources/project.form.sections.settings.fields.internal-description'),
                                        'public'   => __('projects::filament/resources/project.form.sections.settings.fields.public-description'),
                                    ])
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('projects::filament/resources/project.form.sections.settings.fields.visibility-hint-tooltip')),

                                Fieldset::make(__('projects::filament/resources/project.form.sections.settings.fields.time-management'))
                                    ->schema([
                                        Toggle::make('allow_timesheets')
                                            ->label(__('projects::filament/resources/project.form.sections.settings.fields.allow-timesheets'))
                                            ->helperText(__('projects::filament/resources/project.form.sections.settings.fields.allow-timesheets-helper-text'))
                                            ->default(true)
                                            ->visible(static::getTimeSettings()->enable_timesheets),
                                    ])
                                    ->columns(1)
                                    ->visible(static::getTimeSettings()->enable_timesheets)
                                    ->default(static::getTimeSettings()->enable_timesheets),

                                Fieldset::make(__('projects::filament/resources/project.form.sections.settings.fields.task-management'))
                                    ->schema([
                                        Toggle::make('allow_milestones')
                                            ->label(__('projects::filament/resources/project.form.sections.settings.fields.allow-milestones'))
                                            ->helperText(__('projects::filament/resources/project.form.sections.settings.fields.allow-milestones-helper-text'))
                                            ->default(true)
                                            ->visible(static::getTaskSettings()->enable_milestones),
                                    ])
                                    ->columns(1)
                                    ->visible(static::getTaskSettings()->enable_milestones),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::mergeCustomTableColumns([
                Stack::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->weight(FontWeight::Bold)
                            ->label(__('projects::filament/resources/project.table.columns.name'))
                            ->searchable()
                            ->sortable(),
                    ]),
                    Stack::make([
                        TextColumn::make('partner.name')
                            ->label(__('projects::filament/resources/project.table.columns.customer'))
                            ->icon('heroicon-o-phone')
                            ->tooltip(__('projects::filament/resources/project.table.columns.customer'))
                            ->sortable(),
                    ])
                        ->visible(fn (Project $record) => filled($record->partner)),
                    Stack::make([
                        TextColumn::make('start_date')
                            ->label(__('projects::filament/resources/project.table.columns.start-date'))
                            ->sortable()
                            ->extraAttributes(['class' => 'hidden']),
                        TextColumn::make('end_date')
                            ->label(__('projects::filament/resources/project.table.columns.end-date'))
                            ->sortable()
                            ->extraAttributes(['class' => 'hidden']),
                        TextColumn::make('planned_date')
                            ->icon('heroicon-o-calendar')
                            ->tooltip(__('projects::filament/resources/project.table.columns.planned-date'))
                            ->state(fn (Project $record): string => $record->start_date->format('d M Y').' - '.$record->end_date->format('d M Y')),
                    ])
                        ->visible(fn (Project $record) => filled($record->start_date) && filled($record->end_date)),
                    Stack::make([
                        TextColumn::make('remaining_hours')
                            ->icon('heroicon-o-clock')
                            ->badge()
                            ->color('success')
                            ->color(fn (Project $record): string => $record->remaining_hours < 0 ? 'danger' : 'success')
                            ->state(fn (Project $record): string => $record->remaining_hours.' Hours')
                            ->tooltip(__('projects::filament/resources/project.table.columns.remaining-hours')),
                    ])
                        ->visible(fn (Project $record) => static::getTimeSettings()->enable_timesheets && $record->allow_milestones && $record->remaining_hours),
                    Stack::make([
                        TextColumn::make('user.name')
                            ->label(__('projects::filament/resources/project.table.columns.project-manager'))
                            ->icon('heroicon-o-user')
                            ->label(__('projects::filament/resources/project.table.columns.project-manager'))
                            ->sortable(),
                    ])
                        ->visible(fn (Project $record) => filled($record->user)),
                    Stack::make([
                        TextColumn::make('stage.name')
                            ->badge()
                            ->color(fn (Project $record): string => $record->stage?->color ?? 'gray')
                            ->icon('heroicon-o-flag')
                            ->tooltip(__('projects::filament/resources/project.table.columns.stage')),
                    ])
                        ->visible(fn (Project $record): bool => static::getTaskSettings()->enable_project_stages && filled($record->stage)),
                    Stack::make([
                        TextColumn::make('tags.name')
                            ->badge()
                            ->state(function (Project $record): array {
                                return $record->tags->map(fn ($tag) => [
                                    'label' => $tag->name,
                                    'color' => $tag->color ?? '#808080',
                                ])->toArray();
                            })
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state['label'])
                            ->color(fn ($state) => Color::generateV3Palette($state['color']))
                            ->weight(FontWeight::Bold),
                    ])
                        ->visible(fn (Project $record): bool => (bool) $record->tags?->count()),
                ])
                    ->space(3),
            ]))
            ->groups([
                Tables\Grouping\Group::make('stage.name')
                    ->label(__('projects::filament/resources/project.table.groups.stage')),
                Tables\Grouping\Group::make('user.name')
                    ->label(__('projects::filament/resources/project.table.groups.project-manager')),
                Tables\Grouping\Group::make('partner.name')
                    ->label(__('projects::filament/resources/project.table.groups.customer')),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('projects::filament/resources/project.table.groups.created-at'))
                    ->date(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'desc')
            ->filters([
                QueryBuilder::make()
                    ->constraints(static::mergeCustomTableQueryBuilderConstraints([
                        TextConstraint::make('name')
                            ->label(__('projects::filament/resources/project.table.filters.name')),
                        SelectConstraint::make('visibility')
                            ->label(__('projects::filament/resources/project.table.filters.visibility'))
                            ->multiple()
                            ->options(ProjectVisibility::options())
                            ->icon('heroicon-o-eye'),
                        DateConstraint::make('start_date')
                            ->label(__('projects::filament/resources/project.table.filters.start-date')),
                        DateConstraint::make('end_date')
                            ->label(__('projects::filament/resources/project.table.filters.end-date')),
                        BooleanConstraint::make('allow_timesheets')
                            ->label(__('projects::filament/resources/project.table.filters.allow-timesheets'))
                            ->icon('heroicon-o-clock'),
                        BooleanConstraint::make('allow_milestones')
                            ->label(__('projects::filament/resources/project.table.filters.allow-milestones'))
                            ->icon('heroicon-o-flag'),
                        NumberConstraint::make('allocated_hours')
                            ->label(__('projects::filament/resources/project.table.filters.allocated-hours'))
                            ->icon('heroicon-o-clock'),
                        DateConstraint::make('created_at')
                            ->label(__('projects::filament/resources/project.table.filters.created-at')),
                        DateConstraint::make('updated_at')
                            ->label(__('projects::filament/resources/project.table.filters.updated-at')),
                        RelationshipConstraint::make('stage')
                            ->label(__('projects::filament/resources/project.table.filters.stage'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-bars-2'),
                        RelationshipConstraint::make('partner')
                            ->label(__('projects::filament/resources/project.table.filters.customer'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                        RelationshipConstraint::make('user')
                            ->label(__('projects::filament/resources/project.table.filters.project-manager'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                        RelationshipConstraint::make('company')
                            ->label(__('projects::filament/resources/project.table.filters.company'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-building-office'),
                        RelationshipConstraint::make('creator')
                            ->label(__('projects::filament/resources/project.table.filters.creator'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                        RelationshipConstraint::make('tags')
                            ->label(__('projects::filament/resources/project.table.filters.tags'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-tag'),
                    ])),
            ], layout: FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->slideOver(),
            )
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('is_favorite_by_user')
                    ->hiddenLabel()
                    ->icon(fn (Project $record): string => $record->is_favorite_by_user ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (Project $record): string => $record->is_favorite_by_user ? 'warning' : 'gray')
                    ->size('xl')
                    ->action(function (Project $record): void {
                        $record->favoriteUsers()->toggle([Auth::id()]);

                        $record->load(['favoriteUsers' => function ($query) {
                            $query->where('user_id', Auth::id());
                        }]);
                    }),
                Action::make('tasks')
                    ->label(fn (Project $record): string => __('projects::filament/resources/project.table.actions.tasks', ['count' => $record->tasks->whereNull('parent_id')->count()]))
                    ->icon('heroicon-m-clipboard-document-list')
                    ->color('gray')
                    ->url('https:example.com/tasks/{record}')
                    ->hidden(fn ($record) => $record->trashed())
                    ->url(fn (Project $record): string => ManageTasks::getUrl(['record' => $record])),
                Action::make('milestones')
                    ->label(fn (Project $record): string => $record->milestones->where('is_completed', true)->count().'/'.$record->milestones->count())
                    ->icon('heroicon-m-flag')
                    ->color('gray')
                    ->tooltip(fn (Project $record): string => __('projects::filament/resources/project.table.actions.milestones', ['completed' => $record->milestones->where('is_completed', true)->count(), 'all' => $record->milestones->count()]))
                    ->url('https:example.com/tasks/{record}')
                    ->hidden(fn (Project $record) => $record->trashed())
                    ->visible(fn (Project $record) => static::getTaskSettings()->enable_milestones && $record->allow_milestones)
                    ->url(fn (Project $record): string => ManageMilestones::getUrl(['record' => $record])),

                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('projects::filament/resources/project.table.actions.restore.notification.title'))
                                ->body(__('projects::filament/resources/project.table.actions.restore.notification.body')),
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('projects::filament/resources/project.table.actions.delete.notification.title'))
                                ->body(__('projects::filament/resources/project.table.actions.delete.notification.body')),
                        ),
                    ForceDeleteAction::make()
                        ->action(function (Model $record) {
                            try {
                                $record->forceDelete();
                                Notification::make()
                                    ->success()
                                    ->title(__('projects::filament/resources/project.table.actions.force-delete.notification.success.title'))
                                    ->body(__('projects::filament/resources/project.table.actions.force-delete.notification.success.body'))
                                    ->send();
                            } catch (QueryException $th) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('projects::filament/resources/project.table.actions.force-delete.notification.error.title'))
                                    ->body(__('projects::filament/resources/project.table.actions.force-delete.notification.error.body'))
                                    ->send();
                            }
                        }),
                ])
                    ->link()
                    ->hiddenLabel(),
            ])
            ->recordUrl(fn (Project $record): string => static::getUrl('view', ['record' => $record]))
            ->contentGrid([
                'sm'  => 1,
                'md'  => 2,
                'xl'  => 3,
                '2xl' => 4,
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'milestones',
                    'favoriteUsers' => function ($query) {
                        $query->where('user_id', Auth::id());
                    },
                ]);
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('projects::filament/resources/project.infolist.sections.general.title'))
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('projects::filament/resources/project.infolist.sections.general.entries.name'))
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('description')
                                    ->label(__('projects::filament/resources/project.infolist.sections.general.entries.description'))
                                    ->markdown(),
                            ]),

                        Section::make(__('projects::filament/resources/project.infolist.sections.additional.title'))
                            ->schema(static::mergeCustomInfolistEntries([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('project_number')
                                            ->label('Project Number')
                                            ->icon('heroicon-o-hashtag')
                                            ->placeholder('—'),

                                        TextEntry::make('user.name')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.project-manager'))
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),

                                        TextEntry::make('partner.name')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.customer'))
                                            ->icon('heroicon-o-phone')
                                            ->placeholder('—'),

                                        TextEntry::make('project_address')
                                            ->label('Project Address')
                                            ->icon('heroicon-o-map-pin')
                                            ->state(function (Project $record): string {
                                                if ($record->addresses()->count() > 0) {
                                                    $address = $record->addresses()->where('is_primary', true)->first()
                                                               ?? $record->addresses()->first();

                                                    $parts = array_filter([
                                                        $address->street1,
                                                        $address->city,
                                                        $address->state?->name,
                                                    ]);

                                                    return !empty($parts) ? implode(', ', $parts) : '—';
                                                }

                                                return '—';
                                            }),

                                        TextEntry::make('planned_date')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.project-timeline'))
                                            ->icon('heroicon-o-calendar')
                                            ->state(function (Project $record): ?string {
                                                if (! $record->start_date || ! $record->end_date) {
                                                    return '—';
                                                }

                                                return $record->start_date->format('d M Y').' - '.$record->end_date->format('d M Y');
                                            }),

                                        TextEntry::make('allocated_hours')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.allocated-hours'))
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('—')
                                            ->suffix(__('projects::filament/resources/project.infolist.sections.additional.entries.allocated-hours-suffix'))
                                            ->visible(static::getTimeSettings()->enable_timesheets),

                                        TextEntry::make('remaining_hours')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.remaining-hours'))
                                            ->icon('heroicon-o-clock')
                                            ->suffix(__('projects::filament/resources/project.infolist.sections.additional.entries.remaining-hours-suffix'))
                                            ->color(fn (Project $record): string => $record->remaining_hours < 0 ? 'danger' : 'success')
                                            ->visible(static::getTimeSettings()->enable_timesheets),

                                        TextEntry::make('stage.name')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.current-stage'))
                                            ->icon('heroicon-o-flag')
                                            ->badge()
                                            ->color(fn (Project $record): string => $record->stage?->color ?? 'gray')
                                            ->visible(static::getTaskSettings()->enable_project_stages),

                                        TextEntry::make('tags.name')
                                            ->label(__('projects::filament/resources/project.infolist.sections.additional.entries.tags'))
                                            ->badge()
                                            ->state(function (Project $record): array {
                                                return $record->tags->map(fn ($tag) => [
                                                    'label' => $tag->name,
                                                    'color' => $tag->color ?? '#808080',
                                                ])->toArray();
                                            })
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state['label'])
                                            ->color(fn ($state) => Color::generateV3Palette($state['color']))
                                            ->listWithLineBreaks()
                                            ->separator(', ')
                                            ->weight(FontWeight::Bold),
                                    ]),
                            ])),

                        Section::make(__('projects::filament/resources/project.infolist.sections.statistics.title'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('tasks_count')
                                            ->label(__('projects::filament/resources/project.infolist.sections.statistics.entries.total-tasks'))
                                            ->state(fn (Project $record): int => $record->tasks()->count())
                                            ->icon('heroicon-m-clipboard-document-list')
                                            ->iconColor('primary')
                                            ->color('primary')
                                            ->url(fn (Project $record): string => ManageTasks::getUrl(['record' => $record])),

                                        TextEntry::make('milestones_completion')
                                            ->label(__('projects::filament/resources/project.infolist.sections.statistics.entries.milestones-progress'))
                                            ->state(function (Project $record): string {
                                                $completed = $record->milestones()->where('is_completed', true)->count();
                                                $total = $record->milestones()->count();

                                                return "{$completed}/{$total}";
                                            })
                                            ->icon('heroicon-m-flag')
                                            ->iconColor('primary')
                                            ->color('primary')
                                            ->url(fn (Project $record): string => ManageMilestones::getUrl(['record' => $record]))
                                            ->visible(fn (Project $record) => static::getTaskSettings()->enable_milestones && $record->allow_milestones),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make(__('projects::filament/resources/project.infolist.sections.record-information.title'))
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('projects::filament/resources/project.infolist.sections.record-information.entries.created-at'))
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('creator.name')
                                    ->label(__('projects::filament/resources/project.infolist.sections.record-information.entries.created-by'))
                                    ->icon('heroicon-m-user'),

                                TextEntry::make('updated_at')
                                    ->label(__('projects::filament/resources/project.infolist.sections.record-information.entries.last-updated'))
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar-days'),
                            ]),

                        Section::make(__('projects::filament/resources/project.infolist.sections.settings.title'))
                            ->schema([
                                TextEntry::make('visibility')
                                    ->label(__('projects::filament/resources/project.infolist.sections.settings.entries.visibility'))
                                    ->badge()
                                    ->icon(fn (string $state): string => ProjectVisibility::icons()[$state])
                                    ->color(fn (string $state): string => ProjectVisibility::colors()[$state])
                                    ->formatStateUsing(fn (string $state): string => ProjectVisibility::options()[$state]),

                                IconEntry::make('allow_timesheets')
                                    ->label(__('projects::filament/resources/project.infolist.sections.settings.entries.timesheets-enabled'))
                                    ->boolean()
                                    ->visible(static::getTimeSettings()->enable_timesheets),

                                IconEntry::make('allow_milestones')
                                    ->label(__('projects::filament/resources/project.infolist.sections.settings.entries.milestones-enabled'))
                                    ->boolean()
                                    ->visible(static::getTaskSettings()->enable_milestones),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    static public function getTaskSettings(): TaskSettings
    {
        return once(fn () => app(TaskSettings::class));
    }

    static public function getTimeSettings(): TimeSettings
    {
        return once(fn () => app(TimeSettings::class));
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewProject::class,
            EditProject::class,
            ManageTasks::class,
            ManageMilestones::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('Task Stages', [
                TaskStagesRelationManager::class,
            ])
                ->icon('heroicon-o-squares-2x2'),

            RelationGroup::make('Milestones', [
                MilestonesRelationManager::class,
            ])
                ->icon('heroicon-o-flag'),
        ];
    }

    protected static function calculateEstimatedProductionTime($linearFeet, callable $get, callable $set): void
    {
        // Calculate estimated linear feet from date range if dates are set
        $startDate = $get('start_date');
        $desiredCompletionDate = $get('desired_completion_date');
        $companyId = $get('company_id');

        // Only auto-calculate if we have both dates and company, but no linear feet entered
        if ($startDate && $desiredCompletionDate && $companyId && !$linearFeet) {
            try {
                $company = \Webkul\Support\Models\Company::find($companyId);

                if ($company && $company->shop_capacity_per_day) {
                    $start = new \DateTime($startDate);
                    $end = new \DateTime($desiredCompletionDate);

                    // Calculate calendar days
                    $calendarDays = $start->diff($end)->days;

                    // TCS works 4 days per week (Mon-Thu), so approximately 17 working days per month
                    // Calculate working days: (calendar days / 7) * 4 working days per week
                    $workingDaysPerWeek = 4;
                    $workingDays = ($calendarDays / 7) * $workingDaysPerWeek;

                    // Calculate estimated linear feet based on working days and shop capacity
                    $estimatedLinearFeet = round($workingDays * $company->shop_capacity_per_day, 2);

                    // Auto-populate the estimated linear feet field
                    $set('estimated_linear_feet', $estimatedLinearFeet);

                    // Also update allocated hours
                    if ($company->shop_capacity_per_hour) {
                        $allocatedHours = round($estimatedLinearFeet / $company->shop_capacity_per_hour, 2);
                        $set('allocated_hours', $allocatedHours);
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if date calculation errors occur
            }
        }
    }

    protected static function updateProjectName(callable $get, callable $set): void
    {
        // Only generate name if we have both street address and project type
        $street = $get('project_address.street1');
        $projectType = $get('project_type');

        if (!$street || !$projectType) {
            return;
        }

        // Get the project type label
        $projectTypeLabels = [
            'residential' => 'Residential',
            'commercial' => 'Commercial',
            'furniture' => 'Furniture',
            'millwork' => 'Millwork',
            'other' => 'Other',
        ];

        $typeLabel = $projectTypeLabels[$projectType] ?? $projectType;

        // Format: "15B Correia Lane - Residential"
        $projectName = "{$street} - {$typeLabel}";

        // Set the project name
        $set('name', $projectName);
    }

    protected static function updateProjectNumberPreview(?int $companyId, callable $get, callable $set): void
    {
        // Only generate preview if we have both company and street address
        if (!$companyId || !$get('project_address.street1')) {
            return;
        }

        // Get company acronym - use branch if selected, otherwise parent company
        $branchId = $get('branch_id');
        $companyToUse = $branchId ? \Webkul\Support\Models\Company::find($branchId) : \Webkul\Support\Models\Company::find($companyId);

        if (!$companyToUse) {
            return;
        }

        $companyAcronym = $companyToUse->acronym ?? strtoupper(substr($companyToUse->name ?? 'UNK', 0, 3));

        // Get next sequential number for this company/branch
        $lastProject = Project::where('company_id', $companyId)
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = 1;
        if ($lastProject && $lastProject->project_number) {
            // Extract number from format: TCS-0001-Street
            preg_match('/-(\d+)-/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = intval($matches[1]) + 1;
            }
        }

        // Get street address (remove spaces and special chars)
        $street = $get('project_address.street1');
        $streetAbbr = preg_replace('/[^a-zA-Z0-9]/', '', $street);

        // Format: TCS-0001-15BCorreiaLane
        $projectNumber = sprintf(
            '%s-%04d-%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr
        );

        // Set the preview in the project_number field
        $set('project_number', $projectNumber);
    }

    public static function getPages(): array
    {
        return [
            'index'      => ListProjects::route('/'),
            'create'     => CreateProject::route('/create'),
            'edit'       => EditProject::route('/{record}/edit'),
            'view'       => ViewProject::route('/{record}'),
            'milestones' => ManageMilestones::route('/{record}/milestones'),
            'tasks'      => ManageTasks::route('/{record}/tasks'),
        ];
    }
}
