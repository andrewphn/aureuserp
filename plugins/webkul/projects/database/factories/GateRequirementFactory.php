<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;

/**
 * @extends Factory<GateRequirement>
 */
class GateRequirementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GateRequirement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gate_id' => Gate::factory(),
            'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
            'target_model' => 'Project',
            'target_relation' => null,
            'target_field' => 'name',
            'target_value' => null,
            'comparison_operator' => '=',
            'custom_check_class' => null,
            'custom_check_method' => null,
            'error_message' => fake()->sentence(),
            'help_text' => fake()->sentence(),
            'action_label' => null,
            'action_route' => null,
            'sequence' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    /**
     * Create a field not null requirement.
     */
    public function fieldNotNull(string $field, string $targetModel = 'Project'): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
            'target_model' => $targetModel,
            'target_field' => $field,
            'error_message' => "Field '{$field}' must not be empty",
        ]);
    }

    /**
     * Create a field equals requirement.
     */
    public function fieldEquals(string $field, $value, string $targetModel = 'Project'): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_FIELD_EQUALS,
            'target_model' => $targetModel,
            'target_field' => $field,
            'target_value' => is_array($value) ? json_encode($value) : (string) $value,
            'error_message' => "Field '{$field}' must equal expected value",
        ]);
    }

    /**
     * Create a field greater than requirement.
     */
    public function fieldGreaterThan(string $field, $value, string $targetModel = 'Project'): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_FIELD_GREATER_THAN,
            'target_model' => $targetModel,
            'target_field' => $field,
            'target_value' => (string) $value,
            'error_message' => "Field '{$field}' must be greater than {$value}",
        ]);
    }

    /**
     * Create a relation exists requirement.
     */
    public function relationExists(string $relation): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_RELATION_EXISTS,
            'target_relation' => $relation,
            'target_model' => null,
            'target_field' => null,
            'error_message' => "Relation '{$relation}' must have at least one record",
        ]);
    }

    /**
     * Create a relation count requirement.
     */
    public function relationCount(string $relation, int $count, string $operator = '>='): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_RELATION_COUNT,
            'target_relation' => $relation,
            'target_value' => (string) $count,
            'comparison_operator' => $operator,
            'target_model' => null,
            'target_field' => null,
            'error_message' => "Relation '{$relation}' count must be {$operator} {$count}",
        ]);
    }

    /**
     * Create an all children pass requirement.
     */
    public function allChildrenPass(string $relation, string $field, $value): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_ALL_CHILDREN_PASS,
            'target_relation' => $relation,
            'target_field' => $field,
            'target_value' => is_array($value) ? json_encode($value) : (string) $value,
            'target_model' => null,
            'error_message' => "All {$relation} must have {$field} = {$value}",
        ]);
    }

    /**
     * Create a document uploaded requirement.
     */
    public function documentUploaded(string $documentType): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_DOCUMENT_UPLOADED,
            'target_value' => $documentType,
            'target_model' => null,
            'target_relation' => null,
            'target_field' => null,
            'error_message' => "Document type '{$documentType}' must be uploaded",
        ]);
    }

    /**
     * Create a payment received requirement.
     */
    public function paymentReceived(string $paymentType = 'deposit'): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_PAYMENT_RECEIVED,
            'target_value' => $paymentType,
            'target_model' => null,
            'target_relation' => null,
            'target_field' => null,
            'error_message' => "Payment '{$paymentType}' must be received",
        ]);
    }

    /**
     * Create a task completed requirement.
     */
    public function taskCompleted(string $taskType): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_TASK_COMPLETED,
            'target_value' => $taskType,
            'target_model' => null,
            'target_relation' => null,
            'target_field' => null,
            'error_message' => "Task type '{$taskType}' must be completed",
        ]);
    }

    /**
     * Create a custom check requirement.
     */
    public function customCheck(string $class, string $method): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => GateRequirement::TYPE_CUSTOM_CHECK,
            'custom_check_class' => $class,
            'custom_check_method' => $method,
            'target_model' => null,
            'target_relation' => null,
            'target_field' => null,
            'target_value' => null,
            'error_message' => "Custom check failed: {$class}@{$method}",
        ]);
    }

    /**
     * Make the requirement inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Add an action to the requirement.
     */
    public function withAction(string $label, string $route): static
    {
        return $this->state(fn (array $attributes) => [
            'action_label' => $label,
            'action_route' => $route,
        ]);
    }
}
