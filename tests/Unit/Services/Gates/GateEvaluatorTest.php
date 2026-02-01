<?php

namespace Tests\Unit\Services\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Services\Gates\GateEvaluator;
use Webkul\Project\Services\Gates\GateEvaluationResult;
use Webkul\Project\Services\Gates\GateRequirementChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GateEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected GateEvaluator $evaluator;
    protected ProjectStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $requirementChecker = new GateRequirementChecker();
        $this->evaluator = new GateEvaluator($requirementChecker);

        $this->stage = ProjectStage::factory()->create([
            'name' => 'Design',
            'stage_key' => 'design',
        ]);
    }

    // ========================
    // evaluate() Tests
    // ========================

    /** @test */
    public function evaluate_returns_gate_evaluation_result()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertInstanceOf(GateEvaluationResult::class, $result);
    }

    /** @test */
    public function evaluate_passes_when_all_requirements_pass()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'name' => 'Test Project',
            'description' => 'Has description',
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create(['gate_id' => $gate->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertTrue($result->passed);
        $this->assertCount(0, $result->failureReasons);
    }

    /** @test */
    public function evaluate_fails_when_any_requirement_fails()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'name' => 'Test Project',
            'description' => null,
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create(['gate_id' => $gate->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertFalse($result->passed);
        $this->assertCount(1, $result->failureReasons);
    }

    /** @test */
    public function evaluate_creates_evaluation_record()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);

        $this->evaluator->evaluate($project, $gate);

        $this->assertDatabaseHas('projects_gate_evaluations', [
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);
    }

    /** @test */
    public function evaluate_records_requirement_results()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'name' => 'Test',
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);
        $requirement = GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertArrayHasKey($requirement->id, $result->requirementResults);
        $this->assertTrue($result->requirementResults[$requirement->id]['passed']);
    }

    /** @test */
    public function evaluate_records_failure_details()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);
        $requirement = GateRequirement::factory()
            ->fieldNotNull('description')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'Description is required',
                'help_text' => 'Add a project description',
            ]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertCount(1, $result->failureReasons);
        $failure = $result->failureReasons[0];
        $this->assertEquals($requirement->id, $failure['requirement_id']);
        $this->assertEquals('Description is required', $failure['error_message']);
        $this->assertEquals('Add a project description', $failure['help_text']);
    }

    /** @test */
    public function evaluate_uses_specified_evaluation_type()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);

        $this->evaluator->evaluate($project, $gate, GateEvaluation::TYPE_SCHEDULED);

        $this->assertDatabaseHas('projects_gate_evaluations', [
            'project_id' => $project->id,
            'evaluation_type' => GateEvaluation::TYPE_SCHEDULED,
        ]);
    }

    /** @test */
    public function evaluate_passes_with_no_requirements()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function evaluate_only_checks_active_requirements()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);

        // Active requirement that would fail
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->inactive()
            ->create(['gate_id' => $gate->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $this->assertTrue($result->passed);
    }

    // ========================
    // evaluateCurrentStageGates() Tests
    // ========================

    /** @test */
    public function evaluate_current_stage_gates_evaluates_all_gates_for_stage()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        Gate::factory()->count(3)->create(['stage_id' => $this->stage->id]);

        $results = $this->evaluator->evaluateCurrentStageGates($project);

        $this->assertCount(3, $results);
    }

    /** @test */
    public function evaluate_current_stage_gates_returns_keyed_by_gate_key()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        $gate1 = Gate::factory()->create([
            'stage_id' => $this->stage->id,
            'gate_key' => 'gate-one',
        ]);
        $gate2 = Gate::factory()->create([
            'stage_id' => $this->stage->id,
            'gate_key' => 'gate-two',
        ]);

        $results = $this->evaluator->evaluateCurrentStageGates($project);

        $this->assertArrayHasKey('gate-one', $results);
        $this->assertArrayHasKey('gate-two', $results);
    }

    // ========================
    // canAdvance() Tests
    // ========================

    /** @test */
    public function can_advance_returns_true_when_all_blocking_gates_pass()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'name' => 'Test',
        ]);
        $gate = Gate::factory()->blocking()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id]);

        $canAdvance = $this->evaluator->canAdvance($project);

        $this->assertTrue($canAdvance);
    }

    /** @test */
    public function can_advance_returns_false_when_any_blocking_gate_fails()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate = Gate::factory()->blocking()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create(['gate_id' => $gate->id]);

        $canAdvance = $this->evaluator->canAdvance($project);

        $this->assertFalse($canAdvance);
    }

    /** @test */
    public function can_advance_ignores_non_blocking_gates()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate = Gate::factory()->nonBlocking()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create(['gate_id' => $gate->id]);

        $canAdvance = $this->evaluator->canAdvance($project);

        $this->assertTrue($canAdvance);
    }

    /** @test */
    public function can_advance_uses_automatic_evaluation_type()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        Gate::factory()->blocking()->create(['stage_id' => $this->stage->id]);

        $this->evaluator->canAdvance($project);

        $this->assertDatabaseHas('projects_gate_evaluations', [
            'project_id' => $project->id,
            'evaluation_type' => GateEvaluation::TYPE_AUTOMATIC,
        ]);
    }

    // ========================
    // getBlockers() Tests
    // ========================

    /** @test */
    public function get_blockers_returns_all_failing_blocking_gates()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate1 = Gate::factory()->blocking()->create([
            'stage_id' => $this->stage->id,
            'gate_key' => 'gate-1',
        ]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create(['gate_id' => $gate1->id]);

        $gate2 = Gate::factory()->blocking()->create([
            'stage_id' => $this->stage->id,
            'gate_key' => 'gate-2',
        ]);
        // No requirements, should pass

        $blockers = $this->evaluator->getBlockers($project);

        $this->assertArrayHasKey('gate-1', $blockers);
        $this->assertArrayNotHasKey('gate-2', $blockers);
    }

    /** @test */
    public function get_blockers_includes_gate_and_blocker_details()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate = Gate::factory()->blocking()->create([
            'stage_id' => $this->stage->id,
            'gate_key' => 'test-gate',
        ]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'Missing description',
            ]);

        $blockers = $this->evaluator->getBlockers($project);

        $this->assertArrayHasKey('gate', $blockers['test-gate']);
        $this->assertArrayHasKey('blockers', $blockers['test-gate']);
        $this->assertEquals($gate->id, $blockers['test-gate']['gate']->id);
    }

    /** @test */
    public function get_blockers_returns_empty_array_when_all_pass()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'name' => 'Test',
        ]);
        $gate = Gate::factory()->blocking()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id]);

        $blockers = $this->evaluator->getBlockers($project);

        $this->assertEmpty($blockers);
    }

    // ========================
    // getGateStatus() Tests
    // ========================

    /** @test */
    public function get_gate_status_returns_status_for_all_current_stage_gates()
    {
        $project = Project::factory()->create(['stage_id' => $this->stage->id]);
        Gate::factory()->count(2)->create(['stage_id' => $this->stage->id]);

        $status = $this->evaluator->getGateStatus($project);

        $this->assertCount(2, $status);
    }

    /** @test */
    public function get_gate_status_includes_gate_details()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'name' => 'Test',
        ]);
        $gate = Gate::factory()->create([
            'stage_id' => $this->stage->id,
            'gate_key' => 'status-gate',
            'name' => 'Status Test Gate',
            'is_blocking' => true,
        ]);
        GateRequirement::factory()
            ->fieldNotNull('name')
            ->create(['gate_id' => $gate->id]);

        $status = $this->evaluator->getGateStatus($project);

        $gateStatus = $status[0];
        $this->assertEquals('status-gate', $gateStatus['gate_key']);
        $this->assertEquals('Status Test Gate', $gateStatus['name']);
        $this->assertTrue($gateStatus['passed']);
        $this->assertTrue($gateStatus['is_blocking']);
        $this->assertEquals(1, $gateStatus['requirements_total']);
        $this->assertEquals(1, $gateStatus['requirements_passed']);
        $this->assertEmpty($gateStatus['blockers']);
    }

    /** @test */
    public function get_gate_status_includes_blockers_for_failing_gates()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'description' => null,
        ]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);
        GateRequirement::factory()
            ->fieldNotNull('description')
            ->create([
                'gate_id' => $gate->id,
                'error_message' => 'Description required',
            ]);

        $status = $this->evaluator->getGateStatus($project);

        $gateStatus = $status[0];
        $this->assertFalse($gateStatus['passed']);
        $this->assertNotEmpty($gateStatus['blockers']);
    }

    // ========================
    // Context Snapshot Tests
    // ========================

    /** @test */
    public function evaluate_includes_context_snapshot()
    {
        $project = Project::factory()->create([
            'stage_id' => $this->stage->id,
            'project_number' => 'P001',
        ]);
        Room::factory()->count(2)->create(['project_id' => $project->id]);
        $gate = Gate::factory()->create(['stage_id' => $this->stage->id]);

        $result = $this->evaluator->evaluate($project, $gate);

        $context = $result->evaluation->context;
        $this->assertEquals($project->id, $context['project_id']);
        $this->assertEquals('P001', $context['project_number']);
        $this->assertEquals($this->stage->id, $context['stage_id']);
        $this->assertEquals(2, $context['room_count']);
        $this->assertArrayHasKey('snapshot_at', $context);
    }
}
