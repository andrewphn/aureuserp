<?php

namespace Tests\Unit\Models\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GateEvaluationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $evaluation = GateEvaluation::factory()->create();

        $this->assertDatabaseHas('projects_gate_evaluations', [
            'id' => $evaluation->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_project()
    {
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $evaluation->project);
        $this->assertEquals($project->id, $evaluation->project->id);
    }

    /** @test */
    public function it_belongs_to_a_gate()
    {
        $gate = Gate::factory()->create();
        $evaluation = GateEvaluation::factory()->create(['gate_id' => $gate->id]);

        $this->assertInstanceOf(Gate::class, $evaluation->gate);
        $this->assertEquals($gate->id, $evaluation->gate->id);
    }

    /** @test */
    public function it_belongs_to_an_evaluator()
    {
        $user = User::factory()->create();
        $evaluation = GateEvaluation::factory()->evaluatedBy($user)->create();

        $this->assertInstanceOf(User::class, $evaluation->evaluator);
        $this->assertEquals($user->id, $evaluation->evaluator->id);
    }

    /** @test */
    public function scope_passed_filters_passed_evaluations()
    {
        GateEvaluation::factory()->count(2)->passed()->create();
        GateEvaluation::factory()->count(3)->failed()->create();

        $this->assertCount(2, GateEvaluation::passed()->get());
    }

    /** @test */
    public function scope_failed_filters_failed_evaluations()
    {
        GateEvaluation::factory()->count(2)->passed()->create();
        GateEvaluation::factory()->count(3)->failed()->create();

        $this->assertCount(3, GateEvaluation::failed()->get());
    }

    /** @test */
    public function scope_for_project_filters_by_project()
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        GateEvaluation::factory()->count(2)->create(['project_id' => $project1->id]);
        GateEvaluation::factory()->count(3)->create(['project_id' => $project2->id]);

        $this->assertCount(2, GateEvaluation::forProject($project1->id)->get());
        $this->assertCount(3, GateEvaluation::forProject($project2->id)->get());
    }

    /** @test */
    public function scope_for_gate_filters_by_gate()
    {
        $gate1 = Gate::factory()->create();
        $gate2 = Gate::factory()->create();

        GateEvaluation::factory()->count(2)->create(['gate_id' => $gate1->id]);
        GateEvaluation::factory()->count(3)->create(['gate_id' => $gate2->id]);

        $this->assertCount(2, GateEvaluation::forGate($gate1->id)->get());
        $this->assertCount(3, GateEvaluation::forGate($gate2->id)->get());
    }

    /** @test */
    public function scope_recent_orders_by_evaluated_at_descending()
    {
        GateEvaluation::factory()->create(['evaluated_at' => now()->subDays(2)]);
        GateEvaluation::factory()->create(['evaluated_at' => now()]);
        GateEvaluation::factory()->create(['evaluated_at' => now()->subDay()]);

        $evaluations = GateEvaluation::recent()->get();

        $this->assertTrue($evaluations[0]->evaluated_at >= $evaluations[1]->evaluated_at);
        $this->assertTrue($evaluations[1]->evaluated_at >= $evaluations[2]->evaluated_at);
    }

    /** @test */
    public function get_failed_count_returns_failure_reasons_count()
    {
        $evaluation = GateEvaluation::factory()->failed([
            ['requirement_id' => 1, 'error_message' => 'Error 1'],
            ['requirement_id' => 2, 'error_message' => 'Error 2'],
            ['requirement_id' => 3, 'error_message' => 'Error 3'],
        ])->create();

        $this->assertEquals(3, $evaluation->getFailedCount());
    }

    /** @test */
    public function get_failed_count_returns_zero_when_no_failures()
    {
        $evaluation = GateEvaluation::factory()->passed()->create();

        $this->assertEquals(0, $evaluation->getFailedCount());
    }

    /** @test */
    public function get_passed_count_returns_count_of_passed_requirements()
    {
        $evaluation = GateEvaluation::factory()->withRequirementResults([
            1 => ['passed' => true, 'message' => 'OK'],
            2 => ['passed' => true, 'message' => 'OK'],
            3 => ['passed' => false, 'message' => 'Failed'],
        ])->create();

        $this->assertEquals(2, $evaluation->getPassedCount());
    }

    /** @test */
    public function get_passed_count_returns_zero_when_no_results()
    {
        $evaluation = GateEvaluation::factory()->create(['requirement_results' => null]);

        $this->assertEquals(0, $evaluation->getPassedCount());
    }

    /** @test */
    public function get_total_requirement_count_returns_all_requirements()
    {
        $evaluation = GateEvaluation::factory()->withRequirementResults([
            1 => ['passed' => true],
            2 => ['passed' => false],
            3 => ['passed' => true],
        ])->create();

        $this->assertEquals(3, $evaluation->getTotalRequirementCount());
    }

    /** @test */
    public function get_requirement_result_returns_specific_requirement()
    {
        $evaluation = GateEvaluation::factory()->withRequirementResults([
            1 => ['passed' => true, 'message' => 'Passed'],
            2 => ['passed' => false, 'message' => 'Failed'],
        ])->create();

        $result = $evaluation->getRequirementResult(1);

        $this->assertNotNull($result);
        $this->assertTrue($result['passed']);
        $this->assertEquals('Passed', $result['message']);
    }

    /** @test */
    public function get_requirement_result_returns_null_for_nonexistent_requirement()
    {
        $evaluation = GateEvaluation::factory()->create(['requirement_results' => []]);

        $this->assertNull($evaluation->getRequirementResult(999));
    }

    /** @test */
    public function record_creates_evaluation_with_all_data()
    {
        $project = Project::factory()->create();
        $gate = Gate::factory()->create();

        $results = [
            1 => ['passed' => true, 'message' => 'OK'],
            2 => ['passed' => false, 'message' => 'Failed'],
        ];
        $failures = [
            ['requirement_id' => 2, 'error_message' => 'Test error'],
        ];
        $context = ['test_key' => 'test_value'];

        $evaluation = GateEvaluation::record(
            $project,
            $gate,
            false,
            $results,
            $failures,
            $context,
            GateEvaluation::TYPE_AUTOMATIC
        );

        $this->assertDatabaseHas('projects_gate_evaluations', [
            'id' => $evaluation->id,
            'project_id' => $project->id,
            'gate_id' => $gate->id,
            'passed' => false,
            'evaluation_type' => GateEvaluation::TYPE_AUTOMATIC,
        ]);

        $this->assertEquals($results, $evaluation->requirement_results);
        $this->assertEquals($failures, $evaluation->failure_reasons);
        $this->assertEquals($context, $evaluation->context);
        $this->assertNotNull($evaluation->evaluated_at);
    }

    /** @test */
    public function record_uses_manual_type_by_default()
    {
        $project = Project::factory()->create();
        $gate = Gate::factory()->create();

        $evaluation = GateEvaluation::record($project, $gate, true, [], []);

        $this->assertEquals(GateEvaluation::TYPE_MANUAL, $evaluation->evaluation_type);
    }

    /** @test */
    public function evaluated_at_is_cast_to_datetime()
    {
        $evaluation = GateEvaluation::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $evaluation->evaluated_at);
    }

    /** @test */
    public function requirement_results_is_cast_to_array()
    {
        $results = ['1' => ['passed' => true]];
        $evaluation = GateEvaluation::factory()->withRequirementResults($results)->create();

        $evaluation->refresh();

        $this->assertIsArray($evaluation->requirement_results);
    }

    /** @test */
    public function failure_reasons_is_cast_to_array()
    {
        $failures = [['requirement_id' => 1, 'error_message' => 'Test']];
        $evaluation = GateEvaluation::factory()->failed($failures)->create();

        $evaluation->refresh();

        $this->assertIsArray($evaluation->failure_reasons);
    }

    /** @test */
    public function context_is_cast_to_array()
    {
        $context = ['project_number' => 'P001', 'stage' => 'design'];
        $evaluation = GateEvaluation::factory()->withContext($context)->create();

        $evaluation->refresh();

        $this->assertIsArray($evaluation->context);
        $this->assertEquals('P001', $evaluation->context['project_number']);
    }

    /** @test */
    public function evaluation_type_constants_are_defined()
    {
        $this->assertEquals('manual', GateEvaluation::TYPE_MANUAL);
        $this->assertEquals('automatic', GateEvaluation::TYPE_AUTOMATIC);
        $this->assertEquals('scheduled', GateEvaluation::TYPE_SCHEDULED);
    }
}
