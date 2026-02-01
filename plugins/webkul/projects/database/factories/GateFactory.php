<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\ProjectStage;

/**
 * @extends Factory<Gate>
 */
class GateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stage_id' => ProjectStage::factory(),
            'name' => fake()->words(3, true),
            'gate_key' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'sequence' => fake()->numberBetween(1, 100),
            'is_blocking' => true,
            'is_active' => true,
            'applies_design_lock' => false,
            'applies_procurement_lock' => false,
            'applies_production_lock' => false,
            'creates_tasks_on_pass' => false,
            'task_templates_json' => null,
        ];
    }

    /**
     * Indicate that the gate is blocking.
     */
    public function blocking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocking' => true,
        ]);
    }

    /**
     * Indicate that the gate is non-blocking.
     */
    public function nonBlocking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocking' => false,
        ]);
    }

    /**
     * Indicate that the gate is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the gate applies design lock.
     */
    public function withDesignLock(): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_design_lock' => true,
        ]);
    }

    /**
     * Indicate that the gate applies procurement lock.
     */
    public function withProcurementLock(): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_procurement_lock' => true,
        ]);
    }

    /**
     * Indicate that the gate applies production lock.
     */
    public function withProductionLock(): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_production_lock' => true,
        ]);
    }

    /**
     * Indicate that the gate creates tasks on pass.
     */
    public function createsTasksOnPass(array $templates = []): static
    {
        return $this->state(fn (array $attributes) => [
            'creates_tasks_on_pass' => true,
            'task_templates_json' => $templates ?: [
                ['name' => 'Follow-up Task', 'type' => 'follow_up'],
            ],
        ]);
    }
}
