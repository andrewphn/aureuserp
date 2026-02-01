<?php

namespace Tests\Feature\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\Task;
use Webkul\Project\Services\Gates\GateEvaluator;
use Webkul\Project\Services\Gates\GateRequirementChecker;
use Webkul\Partner\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for the complete gate workflow.
 * These tests verify the integration of all gate components working together.
 */
class GateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected GateEvaluator $evaluator;
    protected ProjectStage $discoveryStage;
    protected ProjectStage $designStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new GateEvaluator(new GateRequirementChecker());

        $this->discoveryStage = ProjectStage::factory()->create([
            'name' => 'Discovery',
            'stage_key' => 'discovery',
        ]);

        $this->designStage = ProjectStage::factory()->create([
            'name' => 'Design',
            'stage_key' => 'design',
        ]);
    }

    // ========================
    // Complete Workflow Tests
    // ========================

    /** @test */
    public function project_can_advance_when_all_blocking_gates_pass()
    {
        $partner = Partner::factory()->create();
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
            'partner_id' => $partner->id,
            'name' => 'Test Project',
            'description' => 'Test Description',
        ]);

        // Create "Discovery Complete" gate with requirements
        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'name' => 'Discovery Complete',
            'gate_key' => 'discovery-complete',
        ]);

        GateRequirement::factory()
            ->fieldNotNull('partner_id')
            ->create(['gate_id' => $gate->id, 'sequence' => 1]);

        GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id, 'sequence' => 2]);

        // Verify project can advance
        $canAdvance = $this->evaluator->canAdvance($project);

        $this->assertTrue($canAdvance);
    }

    /** @test */
    public function project_cannot_advance_when_missing_partner()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
            'partner_id' => null,
        ]);

        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'discovery-complete',
        ]);

        GateRequirement::factory()
            ->fieldNotNull('partner_id')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'Customer must be assigned',
            ]);

        $canAdvance = $this->evaluator->canAdvance($project);
        $blockers = $this->evaluator->getBlockers($project);

        $this->assertFalse($canAdvance);
        $this->assertArrayHasKey('discovery-complete', $blockers);
    }

    /** @test */
    public function gate_with_room_requirements_works()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);

        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'rooms-defined',
        ]);

        GateRequirement::factory()
            ->relationExists('rooms')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'At least one room must be defined',
            ]);

        // Initially should fail - no rooms
        $result1 = $this->evaluator->evaluate($project, $gate);
        $this->assertFalse($result1->passed);

        // Add a room
        Room::factory()->create(['project_id' => $project->id]);

        // Now should pass
        $result2 = $this->evaluator->evaluate($project, $gate);
        $this->assertTrue($result2->passed);
    }

    /** @test */
    public function gate_with_minimum_room_count_requirement()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);

        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'minimum-rooms',
        ]);

        GateRequirement::factory()
            ->relationCount('rooms', 3, '>=')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'At least 3 rooms required',
            ]);

        // Add only 2 rooms - should fail
        Room::factory()->count(2)->create(['project_id' => $project->id]);
        $result1 = $this->evaluator->evaluate($project, $gate);
        $this->assertFalse($result1->passed);

        // Add another room - should pass
        Room::factory()->create(['project_id' => $project->id]);
        $result2 = $this->evaluator->evaluate($project, $gate);
        $this->assertTrue($result2->passed);
    }

    /** @test */
    public function multi_gate_stage_evaluates_all_gates()
    {
        $partner = Partner::factory()->create();
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
            'partner_id' => $partner->id,
        ]);

        // Gate 1: Partner assigned
        $gate1 = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'partner-assigned',
            'sequence' => 1,
        ]);
        GateRequirement::factory()
            ->fieldNotNull('partner_id')
            ->create(['gate_id' => $gate1->id]);

        // Gate 2: Rooms defined
        $gate2 = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'rooms-defined',
            'sequence' => 2,
        ]);
        GateRequirement::factory()
            ->relationExists('rooms')
            ->create(['gate_id' => $gate2->id]);

        // Gate 1 passes, Gate 2 fails
        $canAdvance = $this->evaluator->canAdvance($project);
        $this->assertFalse($canAdvance);

        $status = $this->evaluator->getGateStatus($project);
        $this->assertCount(2, $status);

        // Add room and retry
        Room::factory()->create(['project_id' => $project->id]);
        $canAdvance = $this->evaluator->canAdvance($project);
        $this->assertTrue($canAdvance);
    }

    // ========================
    // Evaluation Audit Tests
    // ========================

    /** @test */
    public function evaluations_are_recorded_for_audit()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->discoveryStage->id]);

        // Run multiple evaluations
        $this->evaluator->evaluate($project, $gate);
        $this->evaluator->evaluate($project, $gate);
        $this->evaluator->evaluate($project, $gate);

        $evaluations = GateEvaluation::forProject($project->id)->get();
        $this->assertCount(3, $evaluations);
    }

    /** @test */
    public function evaluation_context_provides_project_snapshot()
    {
        $partner = Partner::factory()->create();
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
            'project_number' => 'TCS-2024-001',
            'partner_id' => $partner->id,
        ]);
        Room::factory()->count(3)->create(['project_id' => $project->id]);

        $gate = Gate::factory()->create(['stage_id' => $this->discoveryStage->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $context = $result->evaluation->context;

        $this->assertEquals('TCS-2024-001', $context['project_number']);
        $this->assertEquals($this->discoveryStage->id, $context['stage_id']);
        $this->assertEquals($partner->id, $context['partner_id']);
        $this->assertEquals(3, $context['room_count']);
    }

    // ========================
    // Non-Blocking Gate Tests
    // ========================

    /** @test */
    public function non_blocking_gates_are_evaluated_but_dont_prevent_advance()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
            'description' => null, // Missing description
        ]);

        // Non-blocking advisory gate
        $gate = Gate::factory()->nonBlocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'description-recommended',
        ]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'Description recommended but not required',
            ]);

        // Should be able to advance even though gate fails
        $canAdvance = $this->evaluator->canAdvance($project);
        $this->assertTrue($canAdvance);

        // But status should show it failed
        $status = $this->evaluator->getGateStatus($project);
        $this->assertFalse($status[0]['passed']);
    }

    // ========================
    // Lock Configuration Tests
    // ========================

    /** @test */
    public function gate_with_design_lock_is_configured_correctly()
    {
        $gate = Gate::factory()
            ->withDesignLock()
            ->create(['stage_id' => $this->designStage->id]);

        $this->assertTrue($gate->applies_design_lock);
        $this->assertTrue($gate->appliesAnyLock());
        $this->assertContains('design', $gate->getLockTypes());
    }

    /** @test */
    public function gate_with_multiple_locks()
    {
        $gate = Gate::factory()->create([
            'stage_id' => $this->designStage->id,
            'applies_design_lock' => true,
            'applies_procurement_lock' => true,
            'applies_production_lock' => false,
        ]);

        $lockTypes = $gate->getLockTypes();

        $this->assertCount(2, $lockTypes);
        $this->assertContains('design', $lockTypes);
        $this->assertContains('procurement', $lockTypes);
        $this->assertNotContains('production', $lockTypes);
    }

    // ========================
    // Task Completion Gate Tests
    // ========================
    // NOTE: Task model doesn't have task_type field in current schema.
    // This test verifies the requirement checker handles missing tasks correctly.

    /** @test */
    public function task_completion_requirement_fails_without_matching_task()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->designStage->id,
        ]);

        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->designStage->id,
            'gate_key' => 'design-review-complete',
        ]);

        GateRequirement::factory()
            ->taskCompleted('design_review')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'Design review task must be completed',
            ]);

        // Should fail - no task with matching task_type
        $result = $this->evaluator->evaluate($project, $gate);
        $this->assertFalse($result->passed);
    }

    // ========================
    // Complex Requirement Tests
    // ========================

    /** @test */
    public function gate_with_all_children_pass_requirement()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->designStage->id,
        ]);

        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->designStage->id,
            'gate_key' => 'all-rooms-kitchen',
        ]);

        GateRequirement::factory()
            ->allChildrenPass('rooms', 'room_type', 'kitchen')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'All rooms must be kitchen type',
            ]);

        // Create rooms, one not kitchen
        Room::factory()->create(['project_id' => $project->id, 'room_type' => 'kitchen']);
        Room::factory()->create(['project_id' => $project->id, 'room_type' => 'kitchen']);
        Room::factory()->create(['project_id' => $project->id, 'room_type' => 'bathroom']);

        // Should fail - one room is not kitchen
        $result = $this->evaluator->evaluate($project, $gate);
        $this->assertFalse($result->passed);
        $this->assertStringContainsString('2/3', $result->failureReasons[0]['details']);

        // Make all rooms kitchen type
        Room::where('project_id', $project->id)->update(['room_type' => 'kitchen']);

        // Now should pass
        $result2 = $this->evaluator->evaluate($project, $gate);
        $this->assertTrue($result2->passed);
    }

    // ========================
    // Error Handling Tests
    // ========================

    /** @test */
    public function gracefully_handles_invalid_requirement_configuration()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);

        $gate = Gate::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);

        // Create requirement with invalid relation
        GateRequirement::factory()->create([
            'gate_id' => $gate->id,
            'requirement_type' => GateRequirement::TYPE_RELATION_EXISTS,
            'target_relation' => 'nonExistentRelation',
        ]);

        // Should not throw exception, should fail gracefully
        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertFalse($result->passed);
    }

    /** @test */
    public function inactive_requirements_are_skipped()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
            'description' => null, // Would fail if active
        ]);

        $gate = Gate::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);

        // Create inactive requirement that would fail
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->inactive()
            ->create(['gate_id' => $gate->id]);

        // Should pass because requirement is inactive
        $result = $this->evaluator->evaluate($project, $gate);
        $this->assertTrue($result->passed);
    }

    // ========================
    // Stage Isolation Tests
    // ========================

    /** @test */
    public function gates_are_isolated_to_their_stage()
    {
        // Project in Discovery stage
        $project = Project::factory()->create([
            'stage_id' => $this->discoveryStage->id,
        ]);

        // Create gate for Design stage (not Discovery)
        $designGate = Gate::factory()->blocking()->create([
            'stage_id' => $this->designStage->id,
            'gate_key' => 'design-gate',
        ]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create(['gate_id' => $designGate->id]);

        // Discovery gate (current stage)
        $discoveryGate = Gate::factory()->blocking()->create([
            'stage_id' => $this->discoveryStage->id,
            'gate_key' => 'discovery-gate',
        ]);
        // No requirements - will pass

        // Should only evaluate Discovery gate, not Design gate
        $status = $this->evaluator->getGateStatus($project);

        $this->assertCount(1, $status);
        $this->assertEquals('discovery-gate', $status[0]['gate_key']);
    }
}
