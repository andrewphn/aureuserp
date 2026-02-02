<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Services\Gates\GateEvaluator;

/**
 * Project Stage Widget - Shows production stage with gate checklist
 *
 * Connected to the actual Gate system in the database.
 */
class ProjectStageWidget extends Widget
{
    public ?Model $record = null;

    protected string $view = 'webkul-project::filament.widgets.project-stage';

    protected int | string | array $columnSpan = 1;

    protected array $stageLabels = [
        'discovery' => 'Discovery',
        'design' => 'Design',
        'sourcing' => 'Sourcing',
        'production' => 'Production',
        'delivery' => 'Delivery',
    ];

    protected array $stageIcons = [
        'discovery' => 'heroicon-o-magnifying-glass',
        'design' => 'heroicon-o-pencil-square',
        'sourcing' => 'heroicon-o-shopping-cart',
        'production' => 'heroicon-o-wrench-screwdriver',
        'delivery' => 'heroicon-o-truck',
    ];

    public function getStageData(): array
    {
        if (!$this->record) {
            return [
                'stage' => 'discovery',
                'label' => 'Discovery',
                'icon' => 'heroicon-o-magnifying-glass',
                'gates' => [],
                'progress' => 0,
                'completed' => 0,
                'total' => 0,
            ];
        }

        $currentStage = $this->record->current_production_stage ?? 'discovery';
        $label = $this->stageLabels[$currentStage] ?? ucfirst($currentStage);
        $icon = $this->stageIcons[$currentStage] ?? 'heroicon-o-flag';

        // Get gates from the database via GateEvaluator
        $gates = $this->getGatesFromDatabase();

        // If no gates in database, fall back to hardcoded checks
        if (empty($gates)) {
            $gates = $this->getFallbackGates($currentStage);
        }

        $completed = collect($gates)->where('completed', true)->count();
        $total = count($gates);
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'stage' => $currentStage,
            'label' => $label,
            'icon' => $icon,
            'gates' => $gates,
            'progress' => $progress,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    /**
     * Get gates from the database using GateEvaluator service
     * Shows individual requirements as checklist items
     */
    protected function getGatesFromDatabase(): array
    {
        try {
            $evaluator = app(GateEvaluator::class);
            $gateStatuses = $evaluator->getGateStatus($this->record);

            if (empty($gateStatuses)) {
                return [];
            }

            $items = [];
            foreach ($gateStatuses as $status) {
                // Get the gate's requirements to show individual checklist items
                $gate = \Webkul\Project\Models\Gate::where('gate_key', $status['gate_key'])->first();

                if ($gate && $gate->requirements->count() > 0) {
                    // Show each requirement as a checklist item
                    foreach ($gate->requirements as $requirement) {
                        // Check if this specific requirement passed
                        $requirementPassed = $this->checkRequirementPassed($status, $requirement->id);

                        // Use a friendly label - invert the error message to positive
                        $label = $this->getPositiveLabel($requirement);

                        $items[] = [
                            'label' => $label,
                            'completed' => $requirementPassed,
                            'is_requirement' => true,
                            'help_text' => $requirement->help_text,
                        ];
                    }
                } else {
                    // Fallback to gate name if no requirements
                    $items[] = [
                        'label' => $status['name'],
                        'completed' => $status['passed'],
                        'is_gate' => true,
                    ];
                }
            }

            return $items;
        } catch (\Exception $e) {
            // If gate evaluation fails, return empty to use fallback
            return [];
        }
    }

    /**
     * Check if a specific requirement passed based on gate status
     */
    protected function checkRequirementPassed(array $gateStatus, int $requirementId): bool
    {
        // If gate passed, all requirements passed
        if ($gateStatus['passed']) {
            return true;
        }

        // Check if this requirement is in the blockers list
        if (!empty($gateStatus['blockers'])) {
            foreach ($gateStatus['blockers'] as $blocker) {
                if (($blocker['requirement_id'] ?? null) === $requirementId) {
                    return false;
                }
            }
        }

        // If not in blockers, it passed
        return true;
    }

    /**
     * Convert error message to positive checklist label
     */
    protected function getPositiveLabel(\Webkul\Project\Models\GateRequirement $requirement): string
    {
        // Map of error messages to positive labels
        $positiveLabels = [
            'No client assigned to project' => 'Client assigned',
            'No sales order linked to project' => 'Sales order linked',
            'Deposit payment not received' => 'Deposit received',
            'No rooms/specifications defined' => 'Rooms defined',
            'Not all cabinets have dimensions' => 'Cabinet dimensions set',
            'BOM not generated' => 'BOM generated',
            'Design not approved by customer' => 'Design approved',
            'Final redline changes not confirmed' => 'Redlines confirmed',
            'Not all materials sourced' => 'Materials sourced',
            'Outstanding POs not confirmed' => 'POs confirmed',
            'Not all materials received' => 'Materials received',
            'Materials not staged for production' => 'Materials staged',
            'Not all production tasks completed' => 'Production complete',
            'Not all cabinets have passed QC' => 'QC passed',
            'Blocking defects remain open' => 'Defects resolved',
            'Delivery date not scheduled' => 'Delivery scheduled',
            'Delivery not confirmed' => 'Delivered',
            'Closeout package not delivered' => 'Closeout sent',
            'Customer signoff not received' => 'Customer signoff',
            'Final payment not received' => 'Final payment',
        ];

        return $positiveLabels[$requirement->error_message] ?? $requirement->error_message;
    }

    /**
     * Fallback gates when no database gates are configured
     */
    protected function getFallbackGates(string $stage): array
    {
        $gates = [];

        switch (strtolower($stage)) {
            case 'discovery':
                $salesOrder = $this->record->orders()->first();
                $gates[] = [
                    'label' => 'Sales order created',
                    'completed' => $salesOrder !== null,
                ];
                $gates[] = [
                    'label' => 'Proposal accepted',
                    'completed' => $salesOrder && $salesOrder->proposal_accepted_at !== null,
                ];
                $gates[] = [
                    'label' => 'Deposit received',
                    'completed' => $salesOrder && $salesOrder->deposit_paid_at !== null,
                ];
                break;

            case 'design':
                $gates[] = [
                    'label' => 'Design approved',
                    'completed' => $this->record->design_approved_at !== null,
                ];
                $gates[] = [
                    'label' => 'Redlines confirmed',
                    'completed' => $this->record->redline_approved_at !== null,
                ];
                break;

            case 'sourcing':
                $gates[] = [
                    'label' => 'Materials ordered',
                    'completed' => $this->record->materials_ordered_at !== null,
                ];
                $gates[] = [
                    'label' => 'Materials received',
                    'completed' => $this->record->all_materials_received_at !== null,
                ];
                $gates[] = [
                    'label' => 'Materials staged',
                    'completed' => $this->record->materials_staged_at !== null,
                ];
                break;

            case 'production':
                $totalCabinets = $this->record->cabinets()->count();
                $qcPassedCabinets = $this->record->cabinets()->where('qc_passed', true)->count();

                $gates[] = [
                    'label' => 'Cabinets built',
                    'completed' => $totalCabinets > 0,
                ];
                $gates[] = [
                    'label' => "QC passed ({$qcPassedCabinets}/{$totalCabinets})",
                    'completed' => $totalCabinets > 0 && $qcPassedCabinets >= $totalCabinets,
                ];
                break;

            case 'delivery':
                $salesOrder = $this->record->orders()->first();
                $gates[] = [
                    'label' => 'Delivered',
                    'completed' => $this->record->delivered_at !== null,
                ];
                $gates[] = [
                    'label' => 'Closeout sent',
                    'completed' => $this->record->closeout_delivered_at !== null,
                ];
                $gates[] = [
                    'label' => 'Customer signoff',
                    'completed' => $this->record->customer_signoff_at !== null,
                ];
                $gates[] = [
                    'label' => 'Final payment',
                    'completed' => $salesOrder && $salesOrder->final_paid_at !== null,
                ];
                break;
        }

        return $gates;
    }
}
