<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\ChangeOrders\ChangeOrderService;

/**
 * Change Order Wizard Livewire Component
 *
 * A multi-step wizard for creating and managing change orders.
 * Allows users to request modifications to locked entities.
 */
class ChangeOrderWizard extends Component
{
    public Project $project;
    public ?ChangeOrder $changeOrder = null;

    // Wizard state
    public int $currentStep = 1;
    public int $totalSteps = 4;

    // Step 1: Basic Info
    public string $title = '';
    public string $description = '';
    public string $reason = 'client_request';

    // Step 2: Changes
    public array $changes = [];
    public array $changeTypes = [
        'cabinet_dimensions' => 'Cabinet Dimensions',
        'material_specs' => 'Material Specifications',
        'hardware_specs' => 'Hardware Specifications',
        'add_remove' => 'Add/Remove Components',
    ];
    public ?string $selectedChangeType = null;

    // Step 3: Review
    public float $priceDelta = 0;
    public array $bomChanges = [];

    // Validation rules per step
    protected array $stepRules = [
        1 => [
            'title' => 'required|min:5|max:255',
            'description' => 'nullable|max:1000',
            'reason' => 'required|in:client_request,field_condition,design_error,material_substitution,scope_addition,scope_removal,other',
        ],
        2 => [
            'changes' => 'required|array|min:1',
            'changes.*.entity_type' => 'required|string',
            'changes.*.entity_id' => 'required|integer',
            'changes.*.field_name' => 'required|string',
            'changes.*.new_value' => 'required|string',
        ],
    ];

    protected ChangeOrderService $service;

    protected $listeners = [
        'entitySelected' => 'handleEntitySelected',
    ];

    public function boot(ChangeOrderService $service): void
    {
        $this->service = $service;
    }

    public function mount(Project $project, ?ChangeOrder $changeOrder = null): void
    {
        $this->project = $project;
        
        if ($changeOrder) {
            $this->changeOrder = $changeOrder;
            $this->loadChangeOrder();
        }
    }

    /**
     * Load existing change order data.
     */
    protected function loadChangeOrder(): void
    {
        $this->title = $this->changeOrder->title;
        $this->description = $this->changeOrder->description ?? '';
        $this->reason = $this->changeOrder->reason;

        $this->changes = $this->changeOrder->lines->map(function ($line) {
            return [
                'entity_type' => $line->entity_type,
                'entity_id' => $line->entity_id,
                'field_name' => $line->field_name,
                'old_value' => $line->old_value,
                'new_value' => $line->new_value,
                'price_impact' => $line->price_impact,
            ];
        })->toArray();

        $this->priceDelta = $this->changeOrder->price_delta;
    }

    /**
     * Navigate to next step.
     */
    public function nextStep(): void
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            
            // Calculate impact when moving to review step
            if ($this->currentStep === 3) {
                $this->calculateImpact();
            }
        }
    }

    /**
     * Navigate to previous step.
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Go to a specific step.
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    /**
     * Validate current step.
     */
    protected function validateCurrentStep(): void
    {
        if (isset($this->stepRules[$this->currentStep])) {
            $this->validate($this->stepRules[$this->currentStep]);
        }
    }

    /**
     * Add a new change line.
     */
    public function addChange(): void
    {
        $this->changes[] = [
            'entity_type' => '',
            'entity_id' => null,
            'field_name' => '',
            'old_value' => '',
            'new_value' => '',
            'price_impact' => 0,
        ];
    }

    /**
     * Remove a change line.
     */
    public function removeChange(int $index): void
    {
        unset($this->changes[$index]);
        $this->changes = array_values($this->changes);
    }

    /**
     * Handle entity selection from external component.
     */
    public function handleEntitySelected(array $entity): void
    {
        // Add the entity as a change line
        $this->changes[] = [
            'entity_type' => $entity['type'],
            'entity_id' => $entity['id'],
            'field_name' => $entity['field'] ?? '',
            'old_value' => $entity['current_value'] ?? '',
            'new_value' => '',
            'price_impact' => 0,
        ];
    }

    /**
     * Calculate price and BOM impact.
     */
    protected function calculateImpact(): void
    {
        $this->priceDelta = 0;
        $this->bomChanges = [];

        foreach ($this->changes as $change) {
            // Sum up price impacts
            $this->priceDelta += (float) ($change['price_impact'] ?? 0);
            
            // Collect BOM changes
            if (!empty($change['bom_impact'])) {
                $this->bomChanges[] = $change['bom_impact'];
            }
        }
    }

    /**
     * Submit the change order.
     */
    public function submit(): void
    {
        $this->validateCurrentStep();

        try {
            if ($this->changeOrder) {
                // Update existing change order
                $this->changeOrder->update([
                    'title' => $this->title,
                    'description' => $this->description,
                    'reason' => $this->reason,
                    'price_delta' => $this->priceDelta,
                ]);
            } else {
                // Create new change order
                $this->changeOrder = $this->service->create($this->project, [
                    'title' => $this->title,
                    'description' => $this->description,
                    'reason' => $this->reason,
                    'lines' => $this->changes,
                ]);
            }

            // Submit for approval
            $this->service->submitForApproval($this->changeOrder);

            session()->flash('message', 'Change order submitted for approval.');
            $this->dispatch('changeOrderCreated', id: $this->changeOrder->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Error creating change order: ' . $e->getMessage());
        }
    }

    /**
     * Get the list of reasons for display.
     */
    public function getReasonOptions(): array
    {
        return ChangeOrder::getReasons();
    }

    /**
     * Get entities available for modification.
     */
    public function getLockedEntities(): array
    {
        $entities = [];

        // Get locked cabinets
        if ($this->project->isDesignLocked()) {
            foreach ($this->project->cabinets as $cabinet) {
                $entities[] = [
                    'type' => 'Cabinet',
                    'id' => $cabinet->id,
                    'name' => $cabinet->name ?? "Cabinet #{$cabinet->id}",
                    'lock_level' => 'design',
                ];
            }
        }

        return $entities;
    }

    /**
     * Get step title.
     */
    public function getStepTitle(): string
    {
        return match ($this->currentStep) {
            1 => 'Basic Information',
            2 => 'Specify Changes',
            3 => 'Review Impact',
            4 => 'Submit',
            default => 'Change Order',
        };
    }

    public function render()
    {
        return view('webkul-project::livewire.change-order-wizard', [
            'stepTitle' => $this->getStepTitle(),
            'lockedEntities' => $this->getLockedEntities(),
            'reasonOptions' => $this->getReasonOptions(),
        ]);
    }
}
