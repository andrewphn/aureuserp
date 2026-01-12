<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Enums\BudgetRange;
use Webkul\Project\Enums\LeadSource;
use Webkul\Project\Models\Project;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\State;

/**
 * Universal Project Summary Sidebar Container
 *
 * A flexible, widget-based sidebar container that can hold various widgets.
 * Supports stage-aware display and custom widget slots.
 *
 * Usage in Blade (with wire:key for reactive updates):
 *   <livewire:project-summary-sidebar
 *       wire:key="sidebar-{{ md5(json_encode($this->data)) }}"
 *       :data="$this->data"
 *   />
 *
 * Usage with custom widgets:
 *   <livewire:project-summary-sidebar :data="$this->data" :widgets="['customer', 'scope', 'estimate']" />
 *
 * Note: We use wire:key to force re-render on data changes instead of #[Reactive]
 * because Livewire 3 reactive props are immutable and cause errors when parent updates them.
 */
class ProjectSummarySidebar extends Component
{
    /**
     * The form data to display in widgets
     * Note: Not reactive - use wire:key on parent to trigger re-renders
     */
    public array $data = [];

    /**
     * Current stage context (discovery, design, sourcing, production, delivery)
     */
    public ?string $stage = 'discovery';

    /**
     * Optional: Existing project ID for edit/view pages
     */
    public ?int $projectId = null;

    /**
     * Widgets to display in the sidebar
     * If empty, uses stage-default widgets
     */
    public array $widgets = [];

    /**
     * Custom widget components registered by the user
     */
    public array $customWidgets = [];

    /**
     * Header configuration
     */
    public string $headerTitle = 'Project Summary';
    public ?string $headerIcon = 'heroicon-o-clipboard-document-list';
    public bool $showHeader = true;

    /**
     * Footer configuration
     */
    public bool $showFooter = true;
    public ?string $footerWidget = 'estimate';

    /**
     * Price per linear foot for estimates
     */
    public float $pricePerLinearFoot = 350.00;

    /**
     * Whether the sidebar is collapsible on mobile
     */
    public bool $collapsible = true;
    public bool $collapsed = false;

    /**
     * Available built-in widgets mapped to their view partials
     */
    protected array $builtInWidgets = [
        'project_preview' => 'widgets.project-preview',
        'customer' => 'widgets.customer',
        'customer_history' => 'widgets.customer-history',
        'checkout_summary' => 'widgets.checkout-summary',
        'capacity' => 'widgets.capacity',
        'location' => 'widgets.location',
        'project_type' => 'widgets.project-type',
        'lead_source' => 'widgets.lead-source',
        'scope' => 'widgets.scope',
        'budget' => 'widgets.budget',
        'timeline' => 'widgets.timeline',
        'documents' => 'widgets.documents',
        'estimate' => 'widgets.estimate',
        'progress' => 'widgets.progress',
        'rooms' => 'widgets.rooms',
        'production_progress' => 'widgets.production-progress',
        'delivery_status' => 'widgets.delivery-status',
        'materials' => 'widgets.materials',
        'qc_status' => 'widgets.qc-status',
    ];

    /**
     * Stage-specific default widget configurations
     */
    protected array $stageWidgetDefaults = [
        'discovery' => ['customer', 'checkout_summary', 'capacity', 'location', 'project_type', 'lead_source', 'budget', 'documents'],
        'design' => ['customer', 'checkout_summary', 'capacity', 'location', 'budget', 'timeline', 'documents'],
        'sourcing' => ['customer', 'location', 'scope', 'capacity', 'materials', 'timeline'],
        'production' => ['customer', 'scope', 'capacity', 'production_progress', 'timeline'],
        'delivery' => ['customer', 'location', 'scope', 'delivery_status', 'qc_status'],
    ];

    /**
     * Stage-specific field configurations for progress calculation
     */
    protected array $stageFieldConfig = [
        'discovery' => [
            'required' => ['partner_id', 'project_type', 'lead_source'],
            'optional' => ['budget_range', 'city', 'state_id'],
        ],
        'design' => [
            'required' => ['partner_id', 'project_type', 'estimated_linear_feet'],
            'optional' => ['complexity_score', 'budget_range', 'rooms_count'],
        ],
        'sourcing' => [
            'required' => ['partner_id', 'estimated_linear_feet'],
            'optional' => ['bom_status', 'po_count'],
        ],
        'production' => [
            'required' => ['partner_id', 'estimated_linear_feet'],
            'optional' => ['cnc_progress', 'assembly_progress', 'qc_status'],
        ],
        'delivery' => [
            'required' => ['partner_id'],
            'optional' => ['bol_status', 'delivery_date', 'final_payment_status'],
        ],
    ];

    /**
     * Mount the component
     */
    public function mount(
        array $data = [],
        ?string $stage = 'discovery',
        ?int $projectId = null,
        array $widgets = [],
        array $customWidgets = [],
        string $headerTitle = 'Project Summary',
        ?string $headerIcon = 'heroicon-o-clipboard-document-list',
        bool $showHeader = true,
        bool $showFooter = true,
        ?string $footerWidget = 'estimate',
        float $pricePerLinearFoot = 350.00,
        bool $collapsible = true
    ): void {
        $this->data = $data;
        $this->stage = $stage;
        $this->projectId = $projectId;
        $this->widgets = $widgets;
        $this->customWidgets = $customWidgets;
        $this->headerTitle = $headerTitle;
        $this->headerIcon = $headerIcon;
        $this->showHeader = $showHeader;
        $this->showFooter = $showFooter;
        $this->footerWidget = $footerWidget;
        $this->pricePerLinearFoot = $pricePerLinearFoot;
        $this->collapsible = $collapsible;
    }

    /**
     * Get the widgets to render (custom or stage defaults)
     */
    public function getActiveWidgetsProperty(): array
    {
        if (!empty($this->widgets)) {
            return $this->widgets;
        }

        return $this->stageWidgetDefaults[$this->stage] ?? $this->stageWidgetDefaults['discovery'];
    }

    /**
     * Get the view path for a widget
     */
    public function getWidgetView(string $widget): ?string
    {
        // Check custom widgets first
        if (isset($this->customWidgets[$widget])) {
            return $this->customWidgets[$widget];
        }

        // Check built-in widgets
        if (isset($this->builtInWidgets[$widget])) {
            return 'webkul-project::livewire.sidebar-widgets.' . $this->builtInWidgets[$widget];
        }

        return null;
    }

    /**
     * Check if a widget should be rendered
     */
    public function shouldRenderWidget(string $widget): bool
    {
        return $this->getWidgetView($widget) !== null;
    }

    /**
     * Whether to show empty fields toggle
     */
    public bool $showEmptyFields = false;

    /**
     * Toggle showing empty fields
     */
    public function toggleShowEmptyFields(): void
    {
        $this->showEmptyFields = !$this->showEmptyFields;
    }

    /**
     * Check if a widget has data (to hide empty widgets)
     * Used for "Don't Make Me Think" UX - hiding empty widgets reduces cognitive load
     */
    public function widgetHasData(string $widget): bool
    {
        // Always show project_preview (it shows status/draft number which is always useful)
        if ($widget === 'project_preview') {
            return true;
        }

        return match ($widget) {
            'customer' => !empty($this->data['partner_id']),
            'location' => !empty($this->formattedLocation),
            'project_type' => !empty($this->data['project_type']),
            'lead_source' => !empty($this->data['lead_source']),
            'scope' => !empty($this->data['estimated_linear_feet']),
            'budget' => !empty($this->data['budget_range']),
            'timeline' => !empty($this->data['start_date']) || !empty($this->data['target_completion_date']),
            'documents' => $this->documentCount > 0,
            'rooms' => $this->roomsCount > 0,
            'customer_history' => !empty($this->customerHistory),
            'estimate' => !empty($this->quickEstimate),
            'checkout_summary' => true, // Always show checkout summary
            'capacity' => !empty($this->data['estimated_linear_feet']), // Show when LF is entered
            'progress' => true, // Always show progress
            // Production/Delivery widgets - always show if in those stages
            'production_progress', 'delivery_status', 'qc_status', 'materials' => true,
            default => true,
        };
    }

    /**
     * Register a custom widget at runtime
     */
    public function registerWidget(string $name, string $viewPath): void
    {
        $this->customWidgets[$name] = $viewPath;
    }

    /**
     * Get the project record if editing
     */
    public function getProjectProperty(): ?Project
    {
        if (!$this->projectId) {
            return null;
        }

        return Project::with(['partner', 'rooms', 'cabinetRuns'])->find($this->projectId);
    }

    /**
     * Get the customer name from partner_id
     */
    public function getCustomerNameProperty(): ?string
    {
        if (empty($this->data['partner_id'])) {
            return null;
        }

        return Partner::find($this->data['partner_id'])?->name;
    }

    /**
     * Get the customer record
     */
    public function getCustomerProperty(): ?Partner
    {
        if (empty($this->data['partner_id'])) {
            return null;
        }

        return Partner::find($this->data['partner_id']);
    }

    /**
     * Get the state code from state_id
     */
    public function getStateCodeProperty(): ?string
    {
        $stateId = $this->data['project_address']['state_id'] ?? $this->data['state_id'] ?? null;

        if (empty($stateId)) {
            return null;
        }

        return State::find($stateId)?->code;
    }

    /**
     * Get formatted location string
     */
    public function getFormattedLocationProperty(): ?string
    {
        $parts = [];

        $street1 = $this->data['project_address']['street1'] ?? $this->data['street1'] ?? null;
        $city = $this->data['project_address']['city'] ?? $this->data['city'] ?? null;
        $zip = $this->data['project_address']['zip'] ?? $this->data['zip'] ?? null;

        if (!empty($street1)) {
            $parts[] = $street1;
        }

        $cityState = [];
        if (!empty($city)) {
            $cityState[] = $city;
        }
        if ($this->stateCode) {
            $cityState[] = $this->stateCode;
        }

        if (!empty($cityState)) {
            $locationLine = implode(', ', $cityState);
            if (!empty($zip)) {
                $locationLine .= ' ' . $zip;
            }
            $parts[] = $locationLine;
        }

        return !empty($parts) ? implode("\n", $parts) : null;
    }

    /**
     * Get the budget range label
     */
    public function getBudgetRangeLabelProperty(): ?string
    {
        if (empty($this->data['budget_range'])) {
            return null;
        }

        return BudgetRange::label($this->data['budget_range']);
    }

    /**
     * Get the lead source label
     */
    public function getLeadSourceLabelProperty(): ?string
    {
        if (empty($this->data['lead_source'])) {
            return null;
        }

        return LeadSource::label($this->data['lead_source']);
    }

    /**
     * Get quick estimate based on linear feet
     */
    public function getQuickEstimateProperty(): ?float
    {
        if (empty($this->data['estimated_linear_feet'])) {
            return null;
        }

        return (float) $this->data['estimated_linear_feet'] * $this->pricePerLinearFoot;
    }

    /**
     * Get document count
     */
    public function getDocumentCountProperty(): int
    {
        $pdfDocs = $this->data['pdf_documents'] ?? [];
        $archPdfs = $this->data['architectural_pdfs'] ?? [];

        if (!is_array($pdfDocs)) {
            $pdfDocs = [];
        }
        if (!is_array($archPdfs)) {
            $archPdfs = [];
        }

        return count($pdfDocs) + count($archPdfs);
    }

    /**
     * Get rooms count
     */
    public function getRoomsCountProperty(): int
    {
        if ($this->project) {
            return $this->project->rooms()->count();
        }

        return 0;
    }

    /**
     * Get cabinet runs count
     */
    public function getCabinetRunsCountProperty(): int
    {
        if ($this->project) {
            return $this->project->cabinetRuns()->count();
        }

        return 0;
    }

    /**
     * Get customer history data (previous projects with this customer)
     */
    public function getCustomerHistoryProperty(): ?array
    {
        $partnerId = $this->data['partner_id'] ?? null;

        if (!$partnerId) {
            return null;
        }

        $partner = Partner::find($partnerId);
        if (!$partner) {
            return null;
        }

        // Get previous projects for this customer
        $projects = Project::where('partner_id', $partnerId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'project_number', 'draft_number', 'created_at', 'estimated_linear_feet']);

        $totalProjects = Project::where('partner_id', $partnerId)->count();
        $totalLinearFeet = Project::where('partner_id', $partnerId)->sum('estimated_linear_feet');

        return [
            'partner' => $partner,
            'projects' => $projects,
            'totalProjects' => $totalProjects,
            'totalLinearFeet' => $totalLinearFeet,
        ];
    }

    /**
     * Get customer chatter (messages/activities) for the customer widget
     */
    public function getCustomerChatterProperty(): ?array
    {
        $partnerId = $this->data['partner_id'] ?? null;

        if (!$partnerId) {
            return null;
        }

        $partner = Partner::find($partnerId);
        if (!$partner) {
            return null;
        }

        // Get recent messages/activities using HasChatter trait
        $messages = $partner->messages()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $activities = $partner->activities()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'messages' => $messages,
            'activities' => $activities,
            'totalMessages' => $partner->messages()->count(),
            'totalActivities' => $partner->activities()->count(),
        ];
    }

    /**
     * Toggle collapsed state (for mobile)
     */
    public function toggleCollapsed(): void
    {
        $this->collapsed = !$this->collapsed;
    }

    /**
     * Get completion percentage based on filled fields for current stage
     */
    public function getCompletionPercentageProperty(): int
    {
        $config = $this->stageFieldConfig[$this->stage] ?? $this->stageFieldConfig['discovery'];

        $requiredFields = $config['required'] ?? [];
        $optionalFields = $config['optional'] ?? [];

        $filledRequired = 0;
        $filledOptional = 0;

        foreach ($requiredFields as $field) {
            if ($this->isFieldFilled($field)) {
                $filledRequired++;
            }
        }

        foreach ($optionalFields as $field) {
            if ($this->isFieldFilled($field)) {
                $filledOptional++;
            }
        }

        $requiredPercentage = count($requiredFields) > 0
            ? ($filledRequired / count($requiredFields)) * 60
            : 0;
        $optionalPercentage = count($optionalFields) > 0
            ? ($filledOptional / count($optionalFields)) * 40
            : 0;

        return (int) round($requiredPercentage + $optionalPercentage);
    }

    /**
     * Check if a field is filled
     */
    protected function isFieldFilled(string $field): bool
    {
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $value = $this->data;
            foreach ($parts as $part) {
                $value = $value[$part] ?? null;
                if ($value === null) {
                    return false;
                }
            }
            return !empty($value);
        }

        return !empty($this->data[$field]);
    }

    /**
     * Get the project type label
     */
    public function getProjectTypeLabelProperty(): ?string
    {
        $type = $this->data['project_type'] ?? null;

        if (!$type) {
            return null;
        }

        $labels = [
            'residential' => 'Residential',
            'commercial' => 'Commercial',
            'furniture' => 'Furniture',
            'millwork' => 'Millwork',
            'other' => 'Other',
        ];

        return $labels[$type] ?? ucfirst($type);
    }

    /**
     * Get draft number preview (auto-generated format)
     * Shows a preview like "TCS-D047-..." for new drafts
     *
     * Draft numbers are assigned at creation. Project numbers are only
     * assigned when a draft is converted to an official project.
     *
     * @return array ['value' => string, 'isPlaceholder' => bool, 'hint' => string|null]
     */
    public function getDraftNumberPreviewProperty(): array
    {
        // If editing an existing project with a draft number, show it
        if (!empty($this->data['draft_number'])) {
            return [
                'value' => $this->data['draft_number'],
                'isPlaceholder' => false,
                'hint' => null,
            ];
        }

        // Calculate the preview based on company acronym and next draft sequence
        $companyId = $this->data['company_id'] ?? null;
        $branchId = $this->data['branch_id'] ?? null;

        if (!$companyId) {
            return [
                'value' => '???-D000-...',
                'isPlaceholder' => true,
                'hint' => 'Select a company',
            ];
        }

        // Get company for acronym
        $company = $branchId ? Company::find($branchId) : Company::find($companyId);
        if (!$company) {
            return [
                'value' => '???-D000-...',
                'isPlaceholder' => true,
                'hint' => 'Company not found',
            ];
        }

        $companyAcronym = $company->acronym ?? strtoupper(substr($company->name ?? 'UNK', 0, 3));

        // Get next draft sequential number
        $startNumber = $company->draft_number_start ?? 1;
        $lastDraft = Project::where('company_id', $companyId)
            ->where('draft_number', 'like', "{$companyAcronym}-D%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = $startNumber;
        if ($lastDraft && $lastDraft->draft_number) {
            // Extract number from format like TCS-D047-Address
            preg_match('/-D(\d+)-/', $lastDraft->draft_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = max(intval($matches[1]) + 1, $startNumber);
            }
        }

        // Check if we have street address for suffix preview
        $street = $this->data['project_address']['street1'] ?? $this->data['street1'] ?? null;
        if ($street) {
            $streetAbbr = preg_replace('/[^a-zA-Z0-9]/', '', $street);
            $draftNumber = sprintf('%s-D%03d-%s', $companyAcronym, $sequentialNumber, $streetAbbr);
            return [
                'value' => $draftNumber,
                'isPlaceholder' => false,
                'hint' => null,
            ];
        }

        // Show partial preview with hint
        return [
            'value' => sprintf('%s-D%03d-...', $companyAcronym, $sequentialNumber),
            'isPlaceholder' => true,
            'hint' => 'Enter address to complete',
        ];
    }

    /**
     * Get project number preview (for converted/official projects)
     * Shows a preview like "TCS-501-..." - only assigned when project is converted
     *
     * @return array ['value' => string, 'isPlaceholder' => bool, 'hint' => string|null]
     */
    public function getProjectNumberPreviewProperty(): array
    {
        // If project has been converted and has a project number, show it
        if (!empty($this->data['project_number'])) {
            return [
                'value' => $this->data['project_number'],
                'isPlaceholder' => false,
                'hint' => null,
            ];
        }

        // For unconverted drafts, show pending message
        return [
            'value' => 'Pending',
            'isPlaceholder' => true,
            'hint' => 'Assigned when project starts',
        ];
    }

    /**
     * Get project name preview
     *
     * @return array ['value' => string, 'isPlaceholder' => bool, 'hint' => string|null]
     */
    public function getProjectNamePreviewProperty(): array
    {
        // If user provided a custom name, use it
        if (!empty($this->data['name'])) {
            return [
                'value' => $this->data['name'],
                'isPlaceholder' => false,
                'hint' => null,
            ];
        }

        // Build name from address and project type
        $street = $this->data['project_address']['street1'] ?? $this->data['street1'] ?? null;
        $city = $this->data['project_address']['city'] ?? $this->data['city'] ?? null;
        $projectType = $this->projectTypeLabel;

        // Need at least address or city to show a name
        $location = $street ?: $city;
        if (!$location) {
            return [
                'value' => 'Pending',
                'isPlaceholder' => true,
                'hint' => null,
            ];
        }

        // Build the name
        $name = ucwords(strtolower($location));
        if ($projectType) {
            $name .= ' - ' . $projectType;
        }

        return [
            'value' => $name,
            'isPlaceholder' => false,
            'hint' => null,
        ];
    }

    /**
     * Get project status for display (Draft, Active, etc.)
     *
     * @return array ['label' => string, 'color' => string, 'icon' => string]
     */
    public function getProjectStatusProperty(): array
    {
        // For new projects (no projectId), it's a draft
        if (!$this->projectId) {
            return [
                'label' => 'Draft',
                'color' => 'warning',
                'icon' => 'heroicon-o-pencil-square',
            ];
        }

        // For existing projects, check actual status
        $project = $this->project;
        if (!$project) {
            return [
                'label' => 'Unknown',
                'color' => 'gray',
                'icon' => 'heroicon-o-question-mark-circle',
            ];
        }

        if (!$project->is_active) {
            return [
                'label' => 'Inactive',
                'color' => 'gray',
                'icon' => 'heroicon-o-pause-circle',
            ];
        }

        return [
            'label' => 'Active',
            'color' => 'success',
            'icon' => 'heroicon-o-check-circle',
        ];
    }

    public function render()
    {
        return view('webkul-project::livewire.project-summary-sidebar');
    }
}
