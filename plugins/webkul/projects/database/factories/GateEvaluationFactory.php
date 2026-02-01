<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * @extends Factory<GateEvaluation>
 */
class GateEvaluationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GateEvaluation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'gate_id' => Gate::factory(),
            'passed' => false,
            'evaluated_at' => now(),
            'evaluated_by' => null,
            'requirement_results' => [],
            'failure_reasons' => [],
            'context' => [],
            'evaluation_type' => GateEvaluation::TYPE_MANUAL,
        ];
    }

    /**
     * Indicate the evaluation passed.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'passed' => true,
            'failure_reasons' => [],
        ]);
    }

    /**
     * Indicate the evaluation failed.
     */
    public function failed(array $reasons = []): static
    {
        return $this->state(fn (array $attributes) => [
            'passed' => false,
            'failure_reasons' => $reasons ?: [
                [
                    'requirement_id' => 1,
                    'error_message' => 'Test requirement failed',
                    'details' => 'Some detail about failure',
                ],
            ],
        ]);
    }

    /**
     * Set the evaluation type to manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type' => GateEvaluation::TYPE_MANUAL,
        ]);
    }

    /**
     * Set the evaluation type to automatic.
     */
    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type' => GateEvaluation::TYPE_AUTOMATIC,
        ]);
    }

    /**
     * Set the evaluation type to scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type' => GateEvaluation::TYPE_SCHEDULED,
        ]);
    }

    /**
     * Set requirement results.
     */
    public function withRequirementResults(array $results): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_results' => $results,
        ]);
    }

    /**
     * Set context snapshot.
     */
    public function withContext(array $context): static
    {
        return $this->state(fn (array $attributes) => [
            'context' => $context,
        ]);
    }

    /**
     * Set the evaluator.
     */
    public function evaluatedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluated_by' => $user->id,
        ]);
    }
}
