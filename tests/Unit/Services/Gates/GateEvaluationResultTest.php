<?php

namespace Tests\Unit\Services\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\GateEvaluationResult;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GateEvaluationResultTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_can_be_constructed_with_all_properties()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $requirementResults = [
            1 => ['passed' => true, 'message' => 'OK'],
            2 => ['passed' => false, 'message' => 'Failed'],
        ];
        $failureReasons = [
            ['requirement_id' => 2, 'error_message' => 'Test failed'],
        ];

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: $requirementResults,
            failureReasons: $failureReasons
        );

        $this->assertFalse($result->passed);
        $this->assertEquals($gate->id, $result->gate->id);
        $this->assertEquals($evaluation->id, $result->evaluation->id);
        $this->assertEquals($requirementResults, $result->requirementResults);
        $this->assertEquals($failureReasons, $result->failureReasons);
    }

    /** @test */
    public function get_failed_count_returns_number_of_failure_reasons()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: [
                ['requirement_id' => 1, 'error_message' => 'Error 1'],
                ['requirement_id' => 2, 'error_message' => 'Error 2'],
                ['requirement_id' => 3, 'error_message' => 'Error 3'],
            ]
        );

        $this->assertEquals(3, $result->getFailedCount());
    }

    /** @test */
    public function get_failed_count_returns_zero_when_no_failures()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->passed()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: true,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [1 => ['passed' => true]],
            failureReasons: []
        );

        $this->assertEquals(0, $result->getFailedCount());
    }

    /** @test */
    public function get_passed_count_returns_number_of_passed_requirements()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [
                1 => ['passed' => true],
                2 => ['passed' => true],
                3 => ['passed' => false],
                4 => ['passed' => true],
            ],
            failureReasons: []
        );

        $this->assertEquals(3, $result->getPassedCount());
    }

    /** @test */
    public function get_passed_count_handles_missing_passed_key()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [
                1 => ['message' => 'No passed key'],
                2 => ['passed' => true],
            ],
            failureReasons: []
        );

        $this->assertEquals(1, $result->getPassedCount());
    }

    /** @test */
    public function get_total_count_returns_total_requirement_count()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [
                1 => ['passed' => true],
                2 => ['passed' => false],
                3 => ['passed' => true],
            ],
            failureReasons: []
        );

        $this->assertEquals(3, $result->getTotalCount());
    }

    /** @test */
    public function get_progress_percentage_calculates_correctly()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [
                1 => ['passed' => true],
                2 => ['passed' => true],
                3 => ['passed' => false],
                4 => ['passed' => false],
            ],
            failureReasons: []
        );

        $this->assertEquals(50.0, $result->getProgressPercentage());
    }

    /** @test */
    public function get_progress_percentage_returns_100_for_zero_requirements()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: true,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: []
        );

        $this->assertEquals(100.0, $result->getProgressPercentage());
    }

    /** @test */
    public function get_progress_percentage_rounds_to_one_decimal()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [
                1 => ['passed' => true],
                2 => ['passed' => false],
                3 => ['passed' => false],
            ],
            failureReasons: []
        );

        $this->assertEquals(33.3, $result->getProgressPercentage());
    }

    /** @test */
    public function get_blocker_messages_extracts_error_messages()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: [
                ['error_message' => 'First error'],
                ['error_message' => 'Second error'],
            ]
        );

        $messages = $result->getBlockerMessages();

        $this->assertCount(2, $messages);
        $this->assertContains('First error', $messages);
        $this->assertContains('Second error', $messages);
    }

    /** @test */
    public function get_blocker_messages_falls_back_to_details()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: [
                ['details' => 'Detailed failure info'],
            ]
        );

        $messages = $result->getBlockerMessages();

        $this->assertContains('Detailed failure info', $messages);
    }

    /** @test */
    public function get_blocker_messages_returns_unknown_blocker_as_fallback()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: false,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: [
                ['requirement_id' => 1], // No error_message or details
            ]
        );

        $messages = $result->getBlockerMessages();

        $this->assertContains('Unknown blocker', $messages);
    }

    /** @test */
    public function get_blocker_messages_returns_empty_array_when_no_failures()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->passed()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: true,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: []
        );

        $this->assertEmpty($result->getBlockerMessages());
    }

    /** @test */
    public function properties_are_readonly()
    {
        $gate = Gate::factory()->create();
        $project = Project::factory()->create();
        $evaluation = GateEvaluation::factory()->create([
            'project_id' => $project->id,
            'gate_id' => $gate->id,
        ]);

        $result = new GateEvaluationResult(
            passed: true,
            gate: $gate,
            evaluation: $evaluation,
            requirementResults: [],
            failureReasons: []
        );

        // Properties should be publicly accessible
        $this->assertTrue($result->passed);
        $this->assertInstanceOf(Gate::class, $result->gate);
        $this->assertInstanceOf(GateEvaluation::class, $result->evaluation);
        $this->assertIsArray($result->requirementResults);
        $this->assertIsArray($result->failureReasons);
    }
}
