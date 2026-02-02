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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Services\LeadConversionService;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Task;
use Webkul\Project\Services\KanbanKpiService;
use Webkul\Project\Services\KanbanQueryService;
use Webkul\Project\Services\ProjectBlockerService;
use Webkul\Project\Services\QuickActionService;
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

    // Custom views
    protected string $view = 'webkul-project::kanban.kanban-board';
    protected static string $headerView = 'webkul-project::kanban.kanban-header';
    protected static string $recordView = 'webkul-project::kanban.kanban-record';
    protected static string $statusView = 'webkul-project::kanban.kanban-status';
    protected static string $scriptsView = 'webkul-project::kanban.kanban-scripts';

    // Services (lazy loaded)
    protected ?KanbanQueryService $queryService = null;
    protected ?KanbanKpiService $kpiService = null;
    protected ?ProjectBlockerService $blockerService = null;
    protected ?QuickActionService $actionService = null;

    // Filters
    public ?int $customerFilter = null;
    public ?int $personFilter = null;
    public bool $overdueOnly = false;
    public ?string $sortBy = 'desired_completion_date';
    public ?string $sortDirection = 'asc';

    // Card settings
    public array $cardSettings = [
        'show_customer' => true,
        'show_days' => true,
        'show_linear_feet' => true,
        'show_milestones' => true,
        'compact_mode' => false,
    ];

    // Modal states
    public ?int $chatterRecordId = null;
    public ?int $quickActionsRecordId = null;
    public ?int $selectedLeadId = null;

    // Leads inbox
    public bool $leadsInboxOpen = true;

    // Filters & view mode
    public ?string $widgetFilter = 'all';
    public string $viewMode = 'projects';
    public ?int $projectFilter = null;

    // Quick Actions form inputs
    public string $quickTaskTitle = '';
    public string $quickMilestoneTitle = '';
    public string $quickComment = '';

    // Chart & KPI
    public int $chartYear;
    public bool $chartCollapsed = false;
    public string $kpiTimeRange = 'this_week';

    // Layout settings
    public array $layoutSettings = [
        'show_kpi_row' => false,
        'show_chart' => false,
        'inbox_collapsed' => true,
        'compact_filters' => true,
    ];

    public function boot(): void
    {
        $this->queryService = app(KanbanQueryService::class);
        $this->kpiService = app(KanbanKpiService::class);
        $this->blockerService = app(ProjectBlockerService::class);
        $this->actionService = app(QuickActionService::class);
    }

    // Lazy service getters (fallback if boot() hasn't run)
    protected function queryService(): KanbanQueryService
    {
        return $this->queryService ??= app(KanbanQueryService::class);
    }

    protected function kpiService(): KanbanKpiService
    {
        return $this->kpiService ??= app(KanbanKpiService::class);
    }

    protected function blockerService(): ProjectBlockerService
    {
        return $this->blockerService ??= app(ProjectBlockerService::class);
    }

    protected function actionService(): QuickActionService
    {
        return $this->actionService ??= app(QuickActionService::class);
    }

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

        $this->cardSettings = session('kanban_card_settings', $this->cardSettings);
        $this->layoutSettings = session('kanban_layout_settings', $this->layoutSettings);
        $this->viewMode = session('kanban_view_mode', 'projects');
        $this->chartYear = session('kanban_chart_year', now()->year);
        $this->chartCollapsed = session('kanban_chart_collapsed', $this->layoutSettings['show_chart'] ? false : true);
        $this->kpiTimeRange = session('kanban_kpi_time_range', 'this_week');
        $this->leadsInboxOpen = !($this->layoutSettings['inbox_collapsed'] ?? true);
    }

    // =========================================
    // VIEW MODE & FILTERS
    // =========================================

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'projects' ? 'tasks' : 'projects';
        session()->put('kanban_view_mode', $this->viewMode);
        $this->widgetFilter = 'all';
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        session()->put('kanban_view_mode', $this->viewMode);
        $this->widgetFilter = 'all';
    }

    public function toggleWidgetFilter(string $filter): void
    {
        $this->widgetFilter = ($this->widgetFilter === $filter && $filter !== 'all') ? 'all' : $filter;
    }

    public function setProjectFilter(?int $projectId): void
    {
        $this->projectFilter = $projectId;
    }

    public function hasActiveFilters(): bool
    {
        return $this->customerFilter || $this->personFilter || $this->overdueOnly;
    }

    // =========================================
    // QUERIES (delegated to service)
    // =========================================

    protected function statuses(): Collection
    {
        return $this->queryService()->getStatuses($this->viewMode, $this->projectFilter);
    }

    protected function getEloquentQuery(): Builder
    {
        $filters = [
            'customer' => $this->customerFilter,
            'person' => $this->personFilter,
            'project' => $this->projectFilter,
            'overdue_only' => $this->overdueOnly,
            'widget' => $this->widgetFilter,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ];

        return $this->viewMode === 'tasks'
            ? $this->queryService()->getTasksQuery($filters)
            : $this->queryService()->getProjectsQuery($filters);
    }

    // =========================================
    // BLOCKER & PRIORITY (delegated to service)
    // =========================================

    public function getProjectBlockers(Project $project): array
    {
        return $this->blockerService()->getBlockers($project);
    }

    public function getProjectPriority(Project $project): ?string
    {
        return $this->blockerService()->getPriority($project);
    }

    public function getTaskProgress(Task $task): array
    {
        return $this->blockerService()->getTaskProgress($task);
    }

    public function isTaskOverdue(Task $task): bool
    {
        return $this->blockerService()->isTaskOverdue($task);
    }

    public function isTaskDueSoon(Task $task): bool
    {
        return $this->blockerService()->isTaskDueSoon($task);
    }

    // =========================================
    // KPI & CHART (delegated to service)
    // =========================================

    public function setKpiTimeRange(string $range): void
    {
        $this->kpiTimeRange = $range;
        session()->put('kanban_kpi_time_range', $range);
    }

    public function getKpiStats(): array
    {
        return $this->kpiService()->getKpiStats($this->kpiTimeRange);
    }

    public function getYearlyStats(): array
    {
        return $this->kpiService()->getYearlyStats($this->chartYear);
    }

    public function getAvailableYears(): array
    {
        return $this->kpiService()->getAvailableYears();
    }

    public function setChartYear(int $year): void
    {
        $this->chartYear = $year;
        session()->put('kanban_chart_year', $year);
        $this->dispatch('chartDataUpdated', $this->getYearlyStats());
    }

    public function toggleChartCollapsed(): void
    {
        $this->chartCollapsed = !$this->chartCollapsed;
        session()->put('kanban_chart_collapsed', $this->chartCollapsed);
    }

    public function toggleKpiRow(): void
    {
        $this->layoutSettings['show_kpi_row'] = !($this->layoutSettings['show_kpi_row'] ?? false);
        session()->put('kanban_layout_settings', $this->layoutSettings);
    }

    public function toggleChartVisibility(): void
    {
        $this->layoutSettings['show_chart'] = !($this->layoutSettings['show_chart'] ?? false);
        $this->chartCollapsed = !$this->layoutSettings['show_chart'];
        session()->put('kanban_layout_settings', $this->layoutSettings);
        session()->put('kanban_chart_collapsed', $this->chartCollapsed);
    }

    public function toggleCompactFilters(): void
    {
        $this->layoutSettings['compact_filters'] = !($this->layoutSettings['compact_filters'] ?? true);
        session()->put('kanban_layout_settings', $this->layoutSettings);
    }

    // =========================================
    // MODALS
    // =========================================

    public function openChatter(int|string $recordId): void
    {
        $this->chatterRecordId = (int) $recordId;
        $this->dispatch('open-modal', id: 'kanban--chatter-modal');
    }

    public function getChatterRecord(): ?Project
    {
        return $this->chatterRecordId ? Project::find($this->chatterRecordId) : null;
    }

    public function openQuickActions(int|string $recordId): void
    {
        $this->quickActionsRecordId = (int) $recordId;
        $this->dispatch('open-modal', id: 'kanban--quick-actions-modal');
    }

    public function getQuickActionsRecord(): ?Project
    {
        return $this->quickActionsRecordId
            ? Project::with(['partner', 'stage', 'milestones', 'tasks', 'orders', 'user', 'designer', 'purchasingManager'])
                ->find($this->quickActionsRecordId)
            : null;
    }

    public function openCreateModal(int|string $stageId): void
    {
        $this->redirect(ProjectResource::getUrl('create', ['stage_id' => $stageId]));
    }

    // =========================================
    // QUICK ACTIONS (delegated to service)
    // =========================================

    public function toggleMilestoneStatus(int $milestoneId): void
    {
        $milestone = $this->actionService()->toggleMilestone($milestoneId);

        if ($milestone) {
            Notification::make()
                ->title($milestone->is_completed ? 'Milestone completed' : 'Milestone reopened')
                ->success()
                ->send();
        }
    }

    public function addQuickMilestone(): void
    {
        if (empty(trim($this->quickMilestoneTitle))) return;

        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $this->actionService()->addMilestone($project, $this->quickMilestoneTitle);
        $this->quickMilestoneTitle = '';

        Notification::make()->title('Milestone added')->success()->send();
    }

    public function addQuickTask(): void
    {
        if (empty(trim($this->quickTaskTitle))) return;

        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $this->actionService()->addTask($project, $this->quickTaskTitle);
        $this->quickTaskTitle = '';

        Notification::make()->title('Task added')->success()->send();
    }

    public function updateTaskStatus(int $taskId, string $state): void
    {
        $this->actionService()->updateTaskStatus($taskId, $state);
        Notification::make()->title('Task status updated')->success()->send();
    }

    public function assignTeamMember(string $role, ?int $userId): void
    {
        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $this->actionService()->assignTeamMember($project, $role, $userId);
        $roleName = $this->actionService()->getRoleName($role);

        Notification::make()
            ->title($userId ? "{$roleName} assigned" : "{$roleName} unassigned")
            ->success()
            ->send();
    }

    public function changeProjectStage(int $stageId): void
    {
        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $stage = $this->actionService()->changeProjectStage($project, $stageId);

        if ($stage) {
            Notification::make()
                ->title('Stage updated')
                ->body("Moved to {$stage->name}")
                ->success()
                ->send();
        }
    }

    // =========================================
    // PROJECT TAGS
    // =========================================

    public function getAvailableTags(): Collection
    {
        return \Webkul\Project\Models\Tag::query()->orderBy('name')->get();
    }

    public function toggleProjectTag(int $tagId): void
    {
        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $project->tags()->toggle($tagId);

        Notification::make()
            ->title('Tags updated')
            ->success()
            ->send();
    }

    public function postQuickComment(): void
    {
        if (empty(trim($this->quickComment))) return;

        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $this->actionService()->postComment($project, $this->quickComment);
        $this->quickComment = '';

        Notification::make()->title('Comment added')->success()->send();
    }

    public function toggleProjectBlocked(int|string|null $projectId = null): void
    {
        if ($projectId !== null) {
            $this->quickActionsRecordId = (int) $projectId;
        }

        $project = $this->getQuickActionsRecord();
        if (!$project) return;

        $result = $this->blockerService()->toggleBlocked($project);

        Notification::make()
            ->title($result['action'] === 'unblocked' ? 'Project unblocked' : 'Project marked as blocked')
            ->body($result['message'])
            ->color($result['action'] === 'unblocked' ? 'success' : 'warning')
            ->send();
    }

    public function duplicateProject(int|string $recordId): void
    {
        $newProject = $this->actionService()->duplicateProject((int) $recordId);

        Notification::make()
            ->title($newProject ? 'Project duplicated' : 'Project not found')
            ->color($newProject ? 'success' : 'danger')
            ->send();
    }

    public function getAvailableUsers(): Collection
    {
        return $this->actionService()->getAvailableUsers();
    }

    public function getAvailableStages(): Collection
    {
        return $this->actionService()->getAvailableStages();
    }

    // =========================================
    // LEADS INBOX
    // =========================================

    public function getInboxLeads(): Collection
    {
        return Lead::query()
            ->inbox()
            ->with(['assignedUser'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getInboxLeadsCount(): int
    {
        return Lead::inbox()->count();
    }

    public function getNewLeadsCount(): int
    {
        return Lead::inbox()->where('created_at', '>', now()->subDay())->count();
    }

    public function openLeadDetails(int $leadId): void
    {
        $this->selectedLeadId = $leadId;
        $this->dispatch('open-modal', id: 'kanban--lead-detail-modal');
    }

    public function closeLeadDetails(): void
    {
        $this->selectedLeadId = null;
    }

    public function convertLeadToProject(int $leadId): void
    {
        $lead = Lead::find($leadId);

        if (!$lead) {
            Notification::make()->title('Lead not found')->danger()->send();
            return;
        }

        try {
            $result = (new LeadConversionService)->convert($lead);
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

    public function updateLeadStatus(int $leadId, string $status): void
    {
        $lead = Lead::find($leadId);
        if (!$lead) return;

        $lead->update(['status' => $status]);
        Notification::make()->title('Lead status updated')->success()->send();
    }

    public function toggleLeadsInbox(): void
    {
        $this->leadsInboxOpen = !$this->leadsInboxOpen;
    }

    // =========================================
    // BULK ACTIONS (Multi-Select)
    // =========================================

    public function bulkChangeStage(array $projectIds, int $stageId): void
    {
        if (empty($projectIds)) return;

        $stage = ProjectStage::find($stageId);
        if (!$stage) {
            Notification::make()->title('Stage not found')->danger()->send();
            return;
        }

        $count = Project::whereIn('id', $projectIds)->update([
            'stage_id' => $stageId,
            'stage_entered_at' => now(),
        ]);

        Notification::make()
            ->title("{$count} projects moved")
            ->body("Moved to {$stage->name}")
            ->success()
            ->send();
    }

    public function bulkMarkBlocked(array $projectIds): void
    {
        if (empty($projectIds)) return;

        $count = 0;
        foreach ($projectIds as $projectId) {
            $project = Project::with('tasks')->find($projectId);
            if (!$project) continue;

            // Only block if not already blocked
            if (!$this->blockerService()->isBlocked($project)) {
                // Create a blocker task
                Task::create([
                    'project_id' => $project->id,
                    'title' => 'Blocker - Needs attention',
                    'state' => 'blocked',
                    'creator_id' => auth()->id(),
                ]);
                $count++;
            }
        }

        Notification::make()
            ->title("{$count} projects marked as blocked")
            ->warning()
            ->send();
    }

    public function bulkUnblock(array $projectIds): void
    {
        if (empty($projectIds)) return;

        $count = 0;
        foreach ($projectIds as $projectId) {
            $project = Project::with('tasks')->find($projectId);
            if (!$project) continue;

            // Unblock all blocked tasks for this project
            $unblocked = $project->tasks()->where('state', 'blocked')->update(['state' => 'pending']);
            if ($unblocked > 0) {
                $count++;
            }
        }

        Notification::make()
            ->title("{$count} projects unblocked")
            ->success()
            ->send();
    }

    public function bulkDuplicate(array $projectIds): void
    {
        if (empty($projectIds)) return;

        $count = 0;
        foreach ($projectIds as $projectId) {
            if ($this->actionService()->duplicateProject((int) $projectId)) {
                $count++;
            }
        }

        Notification::make()
            ->title("{$count} projects duplicated")
            ->success()
            ->send();
    }

    #[On('bulk-status-changed')]
    public function bulkStatusChanged(array $recordIds, int $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        if (empty($recordIds)) return;

        $stage = ProjectStage::find($status);
        if (!$stage) {
            Notification::make()->title('Stage not found')->danger()->send();
            return;
        }

        // Update all projects to new stage
        $count = Project::whereIn('id', $recordIds)->update([
            'stage_id' => $status,
            'stage_entered_at' => now(),
        ]);

        Notification::make()
            ->title("{$count} projects moved")
            ->body("Moved to {$stage->name}")
            ->success()
            ->send();
    }

    // =========================================
    // HEADER ACTIONS
    // =========================================

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createProject')
                ->label('New Project')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->url(fn() => ProjectResource::getUrl('create')),

            Action::make('customizeView')
                ->label('Customize')
                ->icon('heroicon-o-adjustments-horizontal')
                ->slideOver()
                ->modalWidth(Width::Medium)
                ->form($this->getCustomizeViewForm())
                ->action(fn(array $data) => $this->saveCustomizeSettings($data)),

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
                ->action(fn(array $data) => $this->personFilter = $data['user_id'] ?? null),

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
                        ->options(['asc' => 'Ascending', 'desc' => 'Descending'])
                        ->default($this->sortDirection),
                ])
                ->action(function (array $data) {
                    $this->sortBy = $data['sort_by'];
                    $this->sortDirection = $data['sort_direction'];
                }),

            Action::make('clearFilters')
                ->label('Clear')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn() => $this->hasActiveFilters())
                ->action(function () {
                    $this->customerFilter = null;
                    $this->personFilter = null;
                    $this->overdueOnly = false;
                    Notification::make()->title('Filters cleared')->success()->send();
                }),
        ];
    }

    protected function getCustomizeViewForm(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Layout')
                ->description('Control board layout and visibility')
                ->schema([
                    Toggle::make('compact_filters')->label('Compact Filter Bar')->default(fn() => $this->layoutSettings['compact_filters'] ?? true),
                    Toggle::make('show_kpi_row')->label('Show Analytics Row')->default(fn() => $this->layoutSettings['show_kpi_row'] ?? false),
                    Toggle::make('show_chart')->label('Show Yearly Chart')->default(fn() => $this->layoutSettings['show_chart'] ?? false),
                    Toggle::make('inbox_collapsed')->label('Collapse Inbox by Default')->default(fn() => $this->layoutSettings['inbox_collapsed'] ?? true),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Card Fields')
                ->description('Choose which fields to display on cards')
                ->schema([
                    Toggle::make('show_customer')->label('Customer Name')->default(fn() => $this->cardSettings['show_customer']),
                    Toggle::make('show_days')->label('Days Left/Late')->default(fn() => $this->cardSettings['show_days']),
                    Toggle::make('show_linear_feet')->label('Linear Feet')->default(fn() => $this->cardSettings['show_linear_feet']),
                    Toggle::make('show_milestones')->label('Progress Bar')->default(fn() => $this->cardSettings['show_milestones']),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Card Display')
                ->schema([
                    Toggle::make('compact_mode')->label('Compact Cards')->default(fn() => $this->cardSettings['compact_mode']),
                ]),
        ];
    }

    protected function saveCustomizeSettings(array $data): void
    {
        $layoutKeys = ['compact_filters', 'show_kpi_row', 'show_chart', 'inbox_collapsed'];
        foreach ($layoutKeys as $key) {
            if (isset($data[$key])) $this->layoutSettings[$key] = $data[$key];
        }
        session()->put('kanban_layout_settings', $this->layoutSettings);

        $cardKeys = ['show_customer', 'show_days', 'show_linear_feet', 'show_milestones', 'compact_mode'];
        foreach ($cardKeys as $key) {
            if (isset($data[$key])) $this->cardSettings[$key] = $data[$key];
        }
        session()->put('kanban_card_settings', $this->cardSettings);

        $this->leadsInboxOpen = !($this->layoutSettings['inbox_collapsed'] ?? true);
        $this->chartCollapsed = !($this->layoutSettings['show_chart'] ?? false);

        Notification::make()->title('View settings saved')->success()->send();
    }

    // =========================================
    // EDIT MODAL
    // =========================================

    protected function getEditModalFormSchema(null|int|string $recordId): array
    {
        return [
            TextInput::make('name')->label('Project Name')->required()->maxLength(255),
            Select::make('partner_id')->label('Customer')->relationship('partner', 'name')->searchable()->preload(),
            Select::make('stage_id')->label('Stage')->options(ProjectStage::where('is_active', true)->pluck('name', 'id'))->required(),
            DatePicker::make('desired_completion_date')->label('Due Date'),
            TextInput::make('estimated_linear_feet')->label('Estimated Linear Feet')->numeric()->step(0.1),
            Textarea::make('description')->label('Description')->rows(3),
        ];
    }

    protected function getEditModalTitle(): string { return 'Edit Project'; }
    protected function getEditModalSlideOver(): bool { return true; }
    protected function getEditModalWidth(): string { return '2xl'; }

    // =========================================
    // VIEW DATA
    // =========================================

    protected function getViewData(): array
    {
        $data = parent::getViewData();

        $data['chatterRecord'] = $this->getChatterRecord();
        $data['quickActionsRecord'] = $this->getQuickActionsRecord();
        $data['cardSettings'] = $this->cardSettings;
        $data['viewMode'] = $this->viewMode;
        $data['projects'] = Project::query()->orderBy('name')->pluck('name', 'id');
        $data['projectFilter'] = $this->projectFilter;
        $data['leads'] = $this->getInboxLeads();
        $data['leadsCount'] = $this->getInboxLeadsCount();
        $data['newLeadsCount'] = $this->getNewLeadsCount();
        $data['selectedLead'] = $this->selectedLeadId ? Lead::find($this->selectedLeadId) : null;
        $data['chartYear'] = $this->chartYear;
        $data['chartCollapsed'] = $this->chartCollapsed;
        $data['availableYears'] = $this->getAvailableYears();
        $data['yearlyStats'] = $this->getYearlyStats();
        $data['kpiTimeRange'] = $this->kpiTimeRange;
        $data['kpiStats'] = $this->getKpiStats();
        $data['layoutSettings'] = $this->layoutSettings;

        return $data;
    }
}
