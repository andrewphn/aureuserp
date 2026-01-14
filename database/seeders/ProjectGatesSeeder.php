<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\ProjectStage;

/**
 * Seeder for Project Gates and Gate Requirements.
 *
 * Creates the default gates for each production stage and their requirements.
 * Gates are configured based on the existing hard-coded checks in Project model.
 */
class ProjectGatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get stages by stage_key
        $stages = ProjectStage::whereNotNull('stage_key')
            ->where('stage_key', '!=', '')
            ->pluck('id', 'stage_key')
            ->toArray();

        if (empty($stages)) {
            $this->command->warn('No project stages with stage_key found. Skipping gate seeding.');
            return;
        }

        DB::transaction(function () use ($stages) {
            $this->seedDiscoveryGate($stages);
            $this->seedDesignGate($stages);
            $this->seedSourcingGate($stages);
            $this->seedProductionGate($stages);
            $this->seedDeliveryGate($stages);
        });

        $this->command->info('Project gates and requirements seeded successfully.');
    }

    /**
     * Seed the Discovery Complete gate.
     */
    protected function seedDiscoveryGate(array $stages): void
    {
        if (!isset($stages['discovery'])) {
            return;
        }

        $gate = Gate::updateOrCreate(
            ['gate_key' => 'discovery_complete'],
            [
                'stage_id' => $stages['discovery'],
                'name' => 'Discovery Complete',
                'description' => 'Confirm project is commercially real before engineering begins. Requires client info, budget, deposit, and initial documentation.',
                'sequence' => 1,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => false,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => true,
                'task_templates_json' => [
                    ['title' => 'Initial Design Review', 'description' => 'Review client requirements and begin design process'],
                    ['title' => 'Site Measurements Verification', 'description' => 'Verify all site measurements are complete and accurate'],
                ],
            ]
        );

        $this->createDiscoveryRequirements($gate);
    }

    /**
     * Create requirements for Discovery Complete gate.
     */
    protected function createDiscoveryRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'partner_id',
                'error_message' => 'No client assigned to project',
                'help_text' => 'A customer/partner must be linked to the project before proceeding.',
                'action_label' => 'Assign Client',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_RELATION_EXISTS,
                'target_model' => 'Project',
                'target_relation' => 'orders',
                'error_message' => 'No sales order linked to project',
                'help_text' => 'Create a sales order/quote for this project.',
                'action_label' => 'Create Quote',
                'action_route' => 'filament.sales.resources.orders.create',
                'sequence' => 2,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\DepositReceivedCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Deposit payment not received',
                'help_text' => 'The deposit payment must be recorded on the sales order.',
                'action_label' => 'Record Payment',
                'action_route' => 'filament.sales.resources.orders.edit',
                'sequence' => 3,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_RELATION_EXISTS,
                'target_model' => 'Project',
                'target_relation' => 'rooms',
                'error_message' => 'No rooms/specifications defined',
                'help_text' => 'At least one room must be defined with initial specifications.',
                'action_label' => 'Add Room',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 4,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Seed the Design Lock gate.
     */
    protected function seedDesignGate(array $stages): void
    {
        if (!isset($stages['design'])) {
            return;
        }

        $gate = Gate::updateOrCreate(
            ['gate_key' => 'design_lock'],
            [
                'stage_id' => $stages['design'],
                'name' => 'Design Lock',
                'description' => 'Engineering for fabrication complete. Locks cabinet specs, sections, and components from direct edits.',
                'sequence' => 1,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => true,
                'applies_procurement_lock' => false,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => true,
                'task_templates_json' => [
                    ['title' => 'Generate BOM', 'description' => 'Generate final Bill of Materials for procurement'],
                    ['title' => 'Create Purchase Requisitions', 'description' => 'Create purchase requisitions for all materials'],
                ],
            ]
        );

        $this->createDesignRequirements($gate);
    }

    /**
     * Create requirements for Design Lock gate.
     */
    protected function createDesignRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\AllCabinetsDimensionedCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Not all cabinets have dimensions',
                'help_text' => 'Every cabinet must have width, height, and depth specified.',
                'action_label' => 'Review Cabinets',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_RELATION_COUNT,
                'target_model' => 'Project',
                'target_relation' => 'bomLines',
                'target_value' => '1',
                'comparison_operator' => '>=',
                'error_message' => 'BOM not generated',
                'help_text' => 'Generate the Bill of Materials from the cabinet specifications.',
                'action_label' => 'Generate BOM',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 2,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'design_approved_at',
                'error_message' => 'Design not approved by customer',
                'help_text' => 'Customer must sign off on the final design before proceeding.',
                'action_label' => 'Send for Approval',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 3,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'redline_approved_at',
                'error_message' => 'Final redline changes not confirmed',
                'help_text' => 'All redline markup changes must be addressed and confirmed.',
                'action_label' => 'Review Redlines',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 4,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Seed the Procurement Locked gate.
     */
    protected function seedSourcingGate(array $stages): void
    {
        if (!isset($stages['sourcing'])) {
            return;
        }

        $gate = Gate::updateOrCreate(
            ['gate_key' => 'procurement_locked'],
            [
                'stage_id' => $stages['sourcing'],
                'name' => 'Procurement Complete',
                'description' => 'All materials sourced and POs confirmed. Locks BOM quantities from changes.',
                'sequence' => 1,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => true,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => true,
                'task_templates_json' => [
                    ['title' => 'Stage Materials', 'description' => 'Stage all received materials for production'],
                    ['title' => 'Prepare Cut Lists', 'description' => 'Generate cut lists for CNC and shop floor'],
                ],
            ]
        );

        $this->createSourcingRequirements($gate);
    }

    /**
     * Create requirements for Procurement Locked gate.
     */
    protected function createSourcingRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\AllBomLinesCoveredCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Not all materials sourced',
                'help_text' => 'Every BOM line must have inventory reserved or a PO line.',
                'action_label' => 'Review BOM Coverage',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\AllPOsConfirmedCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Outstanding POs not confirmed',
                'help_text' => 'All purchase orders must be in confirmed/received status.',
                'action_label' => 'Review POs',
                'action_route' => 'filament.purchases.resources.orders.index',
                'sequence' => 2,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Seed the Production gates.
     */
    protected function seedProductionGate(array $stages): void
    {
        if (!isset($stages['production'])) {
            return;
        }

        // Receiving Complete gate
        $receivingGate = Gate::updateOrCreate(
            ['gate_key' => 'receiving_complete'],
            [
                'stage_id' => $stages['production'],
                'name' => 'Receiving Complete',
                'description' => 'All critical materials received and staged for production.',
                'sequence' => 1,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => false,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => true,
                'task_templates_json' => [
                    ['title' => 'Begin CNC Cutting', 'description' => 'Start CNC cutting operations'],
                ],
            ]
        );

        $this->createReceivingRequirements($receivingGate);

        // Production Complete gate
        $productionGate = Gate::updateOrCreate(
            ['gate_key' => 'production_complete'],
            [
                'stage_id' => $stages['production'],
                'name' => 'Production Complete',
                'description' => 'All production tasks completed.',
                'sequence' => 2,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => false,
                'applies_production_lock' => true,
                'creates_tasks_on_pass' => false,
            ]
        );

        $this->createProductionCompleteRequirements($productionGate);

        // QC Passed gate
        $qcGate = Gate::updateOrCreate(
            ['gate_key' => 'qc_passed'],
            [
                'stage_id' => $stages['production'],
                'name' => 'QC Passed',
                'description' => 'All cabinets and components have passed quality control.',
                'sequence' => 3,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => false,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => true,
                'task_templates_json' => [
                    ['title' => 'Schedule Delivery', 'description' => 'Coordinate delivery date with client'],
                    ['title' => 'Prepare Hardware Kits', 'description' => 'Assemble hardware kits for installation'],
                ],
            ]
        );

        $this->createQCRequirements($qcGate);
    }

    /**
     * Create requirements for Receiving Complete gate.
     */
    protected function createReceivingRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'all_materials_received_at',
                'error_message' => 'Not all materials received',
                'help_text' => 'All critical PO lines must be received or have exceptions logged.',
                'action_label' => 'View Receiving Status',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'materials_staged_at',
                'error_message' => 'Materials not staged for production',
                'help_text' => 'Materials must be moved to the production staging location.',
                'action_label' => 'Stage Materials',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 2,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Create requirements for Production Complete gate.
     */
    protected function createProductionCompleteRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\AllProductionTasksCompleteCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Not all production tasks completed',
                'help_text' => 'All production-related tasks must be marked as done.',
                'action_label' => 'View Tasks',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Create requirements for QC Passed gate.
     */
    protected function createQCRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_ALL_CHILDREN_PASS,
                'target_model' => 'Project',
                'target_relation' => 'cabinets',
                'target_field' => 'qc_passed',
                'target_value' => 'true',
                'error_message' => 'Not all cabinets have passed QC',
                'help_text' => 'Every cabinet must have qc_passed = true.',
                'action_label' => 'View QC Status',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\NoBlockingDefectsCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Blocking defects remain open',
                'help_text' => 'All blocking defects must be resolved before delivery.',
                'action_label' => 'View Defects',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 2,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Seed the Delivery gates.
     */
    protected function seedDeliveryGate(array $stages): void
    {
        if (!isset($stages['delivery'])) {
            return;
        }

        // Delivery Scheduled gate
        $scheduledGate = Gate::updateOrCreate(
            ['gate_key' => 'delivery_scheduled'],
            [
                'stage_id' => $stages['delivery'],
                'name' => 'Delivery Scheduled',
                'description' => 'Delivery date confirmed with client.',
                'sequence' => 1,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => false,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => false,
            ]
        );

        $this->createDeliveryScheduledRequirements($scheduledGate);

        // Delivered & Closed gate
        $closedGate = Gate::updateOrCreate(
            ['gate_key' => 'delivered_closed'],
            [
                'stage_id' => $stages['delivery'],
                'name' => 'Delivered & Closed',
                'description' => 'Project delivered, signed off, and ready for archival.',
                'sequence' => 2,
                'is_blocking' => true,
                'is_active' => true,
                'applies_design_lock' => false,
                'applies_procurement_lock' => false,
                'applies_production_lock' => false,
                'creates_tasks_on_pass' => true,
                'task_templates_json' => [
                    ['title' => 'Archive Project', 'description' => 'Archive project files and documentation'],
                    ['title' => 'Request Review', 'description' => 'Send review request to client'],
                ],
            ]
        );

        $this->createDeliveredClosedRequirements($closedGate);
    }

    /**
     * Create requirements for Delivery Scheduled gate.
     */
    protected function createDeliveryScheduledRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\DeliveryDateSetCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Delivery date not scheduled',
                'help_text' => 'A delivery date must be set and confirmed.',
                'action_label' => 'Schedule Delivery',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }

    /**
     * Create requirements for Delivered & Closed gate.
     */
    protected function createDeliveredClosedRequirements(Gate $gate): void
    {
        $requirements = [
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'delivered_at',
                'error_message' => 'Delivery not confirmed',
                'help_text' => 'Mark the delivery as complete.',
                'action_label' => 'Confirm Delivery',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 1,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'closeout_delivered_at',
                'error_message' => 'Closeout package not delivered',
                'help_text' => 'Deliver the closeout package to the client.',
                'action_label' => 'Send Closeout',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 2,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
                'target_model' => 'Project',
                'target_field' => 'customer_signoff_at',
                'error_message' => 'Customer signoff not received',
                'help_text' => 'Get customer signature on the completion document.',
                'action_label' => 'Get Signoff',
                'action_route' => 'filament.projects.resources.projects.edit',
                'sequence' => 3,
            ],
            [
                'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
                'custom_check_class' => 'Webkul\\Project\\Services\\Gates\\Requirements\\FinalPaymentReceivedCheck',
                'custom_check_method' => 'check',
                'error_message' => 'Final payment not received',
                'help_text' => 'All payments must be received before project closure.',
                'action_label' => 'View Payments',
                'action_route' => 'filament.sales.resources.orders.index',
                'sequence' => 4,
            ],
        ];

        foreach ($requirements as $req) {
            GateRequirement::updateOrCreate(
                [
                    'gate_id' => $gate->id,
                    'error_message' => $req['error_message'],
                ],
                array_merge($req, ['gate_id' => $gate->id, 'is_active' => true])
            );
        }
    }
}
