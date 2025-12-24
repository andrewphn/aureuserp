<?php

namespace Webkul\Project\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\LeadConversionService;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Security\Models\User;

class ProjectsKanbanBoard extends KanbanBoard
{
    protected static string $model = Project::class;

    protected static string $recordTitleAttribute = 'name';

    protected static string $recordStatusAttribute = 'stage_id';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Kanban Board';

    protected static ?string $slug = 'project/kanban';

    protected static ?int $navigationSort = 4;

    // Custom main view with Chatter modal
    protected string $view = 'webkul-project::kanban.kanban-board';

    // Custom views
    protected static string $headerView = 'webkul-project::kanban.kanban-header';

    protected static string $recordView = 'webkul-project::kanban.kanban-record';

    protected static string $statusView = 'webkul-project::kanban.kanban-status';

    protected static string $scriptsView = 'webkul-project::kanban.kanban-scripts';

    // Filters
    public ?int $customerFilter = null;

    public ?int $personFilter = null;

    public bool $overdueOnly = false;

    public ?string $sortBy = 'desired_completion_date';

    public ?string $sortDirection = 'asc';

    // Card settings (simplified based on "Don't Make Me Think" principles)
    public array $cardSettings = [
        'show_customer' => true,
        'show_days' => true,
        'show_linear_feet' => true,
        'show_milestones' => true,
        'compact_mode' => false,
    ];

    // Chatter modal
    public ?int $chatterRecordId = null;

    // Leads inbox
    public bool $leadsInboxOpen = true;

    public ?int $selectedLeadId = null;

    public static function getNavigationGroup(): string
    {
        return __('webkul-project::filament/resources/project.navigation.group');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Project Kanban Board';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Project Kanban Board';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Drag and drop projects between stages';
    }

    public function mount(): void
    {
        parent::mount();

        // Load card settings from session
        $this->cardSettings = session('kanban_card_settings', $this->cardSettings);
    }

    /**
     * Get statuses from database stages
     */
    protected function statuses(): Collection
    {
        return ProjectStage::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->map(fn(ProjectStage $stage) => [
                'id' => $stage->id,
                'title' => $stage->name,
                'color' => $stage->color ?? '#6b7280',
                'wip_limit' => $stage->wip_limit,
                'is_collapsed' => $stage->is_collapsed ?? false,
            ]);
    }

    /**
     * Get eloquent query with eager loading
     */
    protected function getEloquentQuery(): Builder
    {
        return Project::query()
            ->with(['partner', 'stage', 'milestones', 'orders', 'tasks', 'user'])
            ->when($this->customerFilter, fn($q) => $q->where('partner_id', $this->customerFilter))
            ->when($this->personFilter, fn($q) => $q->where('user_id', $this->personFilter))
            ->when($this->overdueOnly, fn($q) => $q->where('desired_completion_date', '<', now()))
            ->when($this->sortBy, fn($q) => $q->orderBy($this->sortBy, $this->sortDirection ?? 'asc'));
    }

    /**
     * Check if any filters are active
     */
    public function hasActiveFilters(): bool
    {
        return $this->customerFilter || $this->personFilter || $this->overdueOnly;
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            // Create new project
            Action::make('createProject')
                ->label('New Project')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->url(fn() => ProjectResource::getUrl('create')),

            // Customize View slide-over
            Action::make('customizeView')
                ->label('Customize')
                ->icon('heroicon-o-adjustments-horizontal')
                ->slideOver()
                ->modalWidth(Width::Medium)
                ->form([
                    \Filament\Schemas\Components\Section::make('Card Fields')
                        ->description('Choose which fields to display on cards')
                        ->schema([
                            Toggle::make('show_customer')
                                ->label('Customer Name')
                                ->default(fn() => $this->cardSettings['show_customer']),
                            Toggle::make('show_days')
                                ->label('Days Left/Late')
                                ->default(fn() => $this->cardSettings['show_days']),
                            Toggle::make('show_linear_feet')
                                ->label('Linear Feet')
                                ->default(fn() => $this->cardSettings['show_linear_feet']),
                            Toggle::make('show_milestones')
                                ->label('Progress Bar')
                                ->default(fn() => $this->cardSettings['show_milestones']),
                        ])->columns(2),
                    \Filament\Schemas\Components\Section::make('Display Options')
                        ->schema([
                            Toggle::make('compact_mode')
                                ->label('Compact Cards')
                                ->helperText('Show smaller cards with less detail')
                                ->default(fn() => $this->cardSettings['compact_mode']),
                        ]),
                ])
                ->action(function (array $data) {
                    $this->cardSettings = array_merge($this->cardSettings, $data);
                    session()->put('kanban_card_settings', $this->cardSettings);

                    Notification::make()
                        ->title('View settings saved')
                        ->success()
                        ->send();
                }),

            // Filter by person
            Action::make('filterPerson')
                ->label('Person')
                ->icon('heroicon-o-user')
                ->badge(fn() => $this->personFilter ? '1' : null)
                ->form([
                    Select::make('user_id')
                        ->label('Filter by Project Manager')
                        ->options(User::pluck('name', 'id'))
                        ->placeholder('All People')
                        ->searchable()
                        ->default($this->personFilter),
                ])
                ->action(function (array $data) {
                    $this->personFilter = $data['user_id'] ?? null;
                }),

            // Filter
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-o-funnel')
                ->badge(fn() => ($this->customerFilter || $this->overdueOnly) ? '!' : null)
                ->form([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->options(Partner::pluck('name', 'id'))
                        ->placeholder('All Customers')
                        ->searchable()
                        ->default($this->customerFilter),
                    Toggle::make('overdue_only')
                        ->label('Overdue Only')
                        ->default($this->overdueOnly),
                ])
                ->action(function (array $data) {
                    $this->customerFilter = $data['customer_id'] ?? null;
                    $this->overdueOnly = $data['overdue_only'] ?? false;
                }),

            // Sort
            Action::make('sort')
                ->label('Sort')
                ->icon('heroicon-o-bars-arrow-down')
                ->form([
                    Select::make('sort_by')
                        ->label('Sort By')
                        ->options([
                            'name' => 'Project Name',
                            'desired_completion_date' => 'Due Date',
                            'created_at' => 'Created Date',
                        ])
                        ->default($this->sortBy),
                    Select::make('sort_direction')
                        ->label('Direction')
                        ->options([
                            'asc' => 'Ascending',
                            'desc' => 'Descending',
                        ])
                        ->default($this->sortDirection),
                ])
                ->action(function (array $data) {
                    $this->sortBy = $data['sort_by'];
                    $this->sortDirection = $data['sort_direction'];
                }),

            // Clear filters
            Action::make('clearFilters')
                ->label('Clear')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn() => $this->hasActiveFilters())
                ->action(function () {
                    $this->customerFilter = null;
                    $this->personFilter = null;
                    $this->overdueOnly = false;

                    Notification::make()
                        ->title('Filters cleared')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Edit modal form schema
     */
    protected function getEditModalFormSchema(null|int|string $recordId): array
    {
        return [
            TextInput::make('name')
                ->label('Project Name')
                ->required()
                ->maxLength(255),

            Select::make('partner_id')
                ->label('Customer')
                ->relationship('partner', 'name')
                ->searchable()
                ->preload(),

            Select::make('stage_id')
                ->label('Stage')
                ->options(ProjectStage::where('is_active', true)->pluck('name', 'id'))
                ->required(),

            DatePicker::make('desired_completion_date')
                ->label('Due Date'),

            TextInput::make('estimated_linear_feet')
                ->label('Estimated Linear Feet')
                ->numeric()
                ->step(0.1),

            Textarea::make('description')
                ->label('Description')
                ->rows(3),
        ];
    }

    protected function getEditModalTitle(): string
    {
        return 'Edit Project';
    }

    protected function getEditModalSlideOver(): bool
    {
        return true;
    }

    protected function getEditModalWidth(): string
    {
        return '2xl';
    }

    /**
     * Get project blockers - checks tasks with "blocked" status first,
     * then other blocking conditions
     */
    public function getProjectBlockers(Project $project): array
    {
        $blockers = [];

        // PRIMARY: Check for blocked tasks
        $blockedTasks = $project->tasks()->where('state', 'blocked')->count();
        if ($blockedTasks > 0) {
            $blockers[] = $blockedTasks . ' task(s) blocked';
        }

        // SECONDARY: Check for missing order (only if no blocked tasks)
        if (empty($blockers) && !$project->orders()->exists()) {
            $blockers[] = 'No sales order linked';
        }

        // SECONDARY: Check for missing customer
        if (empty($blockers) && !$project->partner_id) {
            $blockers[] = 'No customer assigned';
        }

        // Check for project dependencies
        if ($project->dependsOn->where('is_completed', false)->count() > 0) {
            $blockers[] = 'Waiting on dependencies';
        }

        return $blockers;
    }

    /**
     * Get project priority
     */
    public function getProjectPriority(Project $project): ?string
    {
        // Check if overdue - high priority
        if ($project->desired_completion_date && $project->desired_completion_date < now()) {
            return 'high';
        }

        // Check complexity score
        $score = $project->complexity_score ?? 0;
        if ($score >= 8) {
            return 'high';
        }
        if ($score >= 5) {
            return 'medium';
        }

        // Check if due within 7 days
        if ($project->desired_completion_date && $project->desired_completion_date->diffInDays(now()) < 7) {
            return 'medium';
        }

        return null;
    }

    /**
     * Open Chatter slide-over
     */
    public function openChatter(int|string $recordId): void
    {
        $this->chatterRecordId = (int) $recordId;
        $this->dispatch('open-modal', id: 'kanban--chatter-modal');
    }

    /**
     * Get chatter record
     */
    public function getChatterRecord(): ?Project
    {
        if (!$this->chatterRecordId) {
            return null;
        }

        return Project::find($this->chatterRecordId);
    }

    /**
     * Open create modal for a specific stage
     */
    public function openCreateModal(int|string $stageId): void
    {
        // Redirect to create page with stage pre-selected
        $this->redirect(ProjectResource::getUrl('create', ['stage_id' => $stageId]));
    }

    /**
     * Duplicate a project
     */
    public function duplicateProject(int|string $recordId): void
    {
        $project = Project::find($recordId);

        if (!$project) {
            Notification::make()
                ->title('Project not found')
                ->danger()
                ->send();
            return;
        }

        $newProject = $project->replicate();
        $newProject->name = $project->name . ' (Copy)';
        $newProject->save();

        Notification::make()
            ->title('Project duplicated')
            ->success()
            ->send();
    }

    /**
     * Override the view to add Chatter modal
     */
    protected function getViewData(): array
    {
        $data = parent::getViewData();
        $data['chatterRecord'] = $this->getChatterRecord();
        $data['cardSettings'] = $this->cardSettings;

        // Add leads data
        $data['leads'] = $this->getInboxLeads();
        $data['leadsCount'] = $this->getInboxLeadsCount();
        $data['newLeadsCount'] = $this->getNewLeadsCount();
        $data['selectedLead'] = $this->selectedLeadId ? Lead::find($this->selectedLeadId) : null;

        return $data;
    }

    /**
     * Get leads for inbox
     */
    public function getInboxLeads(): Collection
    {
        return Lead::query()
            ->inbox()
            ->with(['assignedUser'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get count of inbox leads
     */
    public function getInboxLeadsCount(): int
    {
        return Lead::inbox()->count();
    }

    /**
     * Get count of new leads (created in last 24 hours)
     */
    public function getNewLeadsCount(): int
    {
        return Lead::inbox()
            ->where('created_at', '>', now()->subDay())
            ->count();
    }

    /**
     * Open lead detail panel
     */
    public function openLeadDetails(int $leadId): void
    {
        $this->selectedLeadId = $leadId;
        $this->dispatch('open-modal', id: 'kanban--lead-detail-modal');
    }

    /**
     * Close lead detail panel
     */
    public function closeLeadDetails(): void
    {
        $this->selectedLeadId = null;
    }

    /**
     * Convert lead to project
     */
    public function convertLeadToProject(int $leadId): void
    {
        $lead = Lead::find($leadId);

        if (! $lead) {
            Notification::make()
                ->title('Lead not found')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = new LeadConversionService;
            $result = $service->convert($lead);

            $this->selectedLeadId = null;

            Notification::make()
                ->title('Lead converted successfully')
                ->body("Created project: {$result['project']->name}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Conversion failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Update lead status
     */
    public function updateLeadStatus(int $leadId, string $status): void
    {
        $lead = Lead::find($leadId);

        if (! $lead) {
            return;
        }

        $lead->update(['status' => $status]);

        Notification::make()
            ->title('Lead status updated')
            ->success()
            ->send();
    }

    /**
     * Toggle leads inbox visibility
     */
    public function toggleLeadsInbox(): void
    {
        $this->leadsInboxOpen = ! $this->leadsInboxOpen;
    }
}
