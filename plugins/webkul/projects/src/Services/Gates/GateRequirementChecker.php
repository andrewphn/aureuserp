<?php

namespace Webkul\Project\Services\Gates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\Requirements\AllCncProgramsCompleteCheck;

/**
 * Gate Requirement Checker
 *
 * Evaluates individual gate requirements based on their type.
 */
class GateRequirementChecker
{
    /**
     * Check a single requirement against a project.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        try {
            return match ($requirement->requirement_type) {
                GateRequirement::TYPE_FIELD_NOT_NULL => $this->checkFieldNotNull($project, $requirement),
                GateRequirement::TYPE_FIELD_EQUALS => $this->checkFieldEquals($project, $requirement),
                GateRequirement::TYPE_FIELD_GREATER_THAN => $this->checkFieldGreaterThan($project, $requirement),
                GateRequirement::TYPE_RELATION_EXISTS => $this->checkRelationExists($project, $requirement),
                GateRequirement::TYPE_RELATION_COUNT => $this->checkRelationCount($project, $requirement),
                GateRequirement::TYPE_ALL_CHILDREN_PASS => $this->checkAllChildrenPass($project, $requirement),
                GateRequirement::TYPE_DOCUMENT_UPLOADED => $this->checkDocumentUploaded($project, $requirement),
                GateRequirement::TYPE_PAYMENT_RECEIVED => $this->checkPaymentReceived($project, $requirement),
                GateRequirement::TYPE_TASK_COMPLETED => $this->checkTaskCompleted($project, $requirement),
                GateRequirement::TYPE_CUSTOM_CHECK => $this->checkCustom($project, $requirement),
                GateRequirement::TYPE_ALL_CNC_COMPLETE => $this->checkAllCncComplete($project, $requirement),
                default => new RequirementCheckResult(false, "Unknown requirement type: {$requirement->requirement_type}"),
            };
        } catch (\Exception $e) {
            Log::error('Gate requirement check failed', [
                'requirement_id' => $requirement->id,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return new RequirementCheckResult(
                false,
                "Error checking requirement: {$e->getMessage()}",
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Check if a field is not null.
     */
    protected function checkFieldNotNull(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $target = $this->getTarget($project, $requirement);
        if (!$target) {
            return new RequirementCheckResult(false, 'Target model not found');
        }

        $field = $requirement->target_field;
        $value = $target->{$field};

        $passed = $value !== null && $value !== '';

        return new RequirementCheckResult(
            $passed,
            $passed ? "Field '{$field}' has value" : "Field '{$field}' is empty",
            ['field' => $field, 'value' => $value]
        );
    }

    /**
     * Check if a field equals a specific value.
     */
    protected function checkFieldEquals(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $target = $this->getTarget($project, $requirement);
        if (!$target) {
            return new RequirementCheckResult(false, 'Target model not found');
        }

        $field = $requirement->target_field;
        $expectedValue = $requirement->getDecodedTargetValue();
        $actualValue = $target->{$field};

        $passed = $actualValue == $expectedValue;

        return new RequirementCheckResult(
            $passed,
            $passed ? "Field '{$field}' equals expected value" : "Field '{$field}' does not equal expected value",
            ['field' => $field, 'expected' => $expectedValue, 'actual' => $actualValue]
        );
    }

    /**
     * Check if a field is greater than a value.
     */
    protected function checkFieldGreaterThan(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $target = $this->getTarget($project, $requirement);
        if (!$target) {
            return new RequirementCheckResult(false, 'Target model not found');
        }

        $field = $requirement->target_field;
        $expectedValue = (float) $requirement->target_value;
        $actualValue = (float) $target->{$field};

        $passed = $actualValue > $expectedValue;

        return new RequirementCheckResult(
            $passed,
            $passed ? "Field '{$field}' is greater than {$expectedValue}" : "Field '{$field}' ({$actualValue}) is not greater than {$expectedValue}",
            ['field' => $field, 'expected' => $expectedValue, 'actual' => $actualValue]
        );
    }

    /**
     * Check if a relation exists.
     */
    protected function checkRelationExists(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $relation = $requirement->target_relation;
        
        if (!method_exists($project, $relation)) {
            return new RequirementCheckResult(false, "Relation '{$relation}' does not exist on Project model");
        }

        $exists = $project->{$relation}()->exists();

        return new RequirementCheckResult(
            $exists,
            $exists ? "Relation '{$relation}' has records" : "Relation '{$relation}' is empty",
            ['relation' => $relation, 'exists' => $exists]
        );
    }

    /**
     * Check if relation count meets threshold.
     */
    protected function checkRelationCount(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $relation = $requirement->target_relation;
        $expectedCount = (int) $requirement->target_value;
        $operator = $requirement->comparison_operator;

        if (!method_exists($project, $relation)) {
            return new RequirementCheckResult(false, "Relation '{$relation}' does not exist on Project model");
        }

        $actualCount = $project->{$relation}()->count();
        
        $passed = $this->compare($actualCount, $expectedCount, $operator);

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? "Relation '{$relation}' count ({$actualCount}) {$operator} {$expectedCount}"
                : "Relation '{$relation}' count ({$actualCount}) does not satisfy {$operator} {$expectedCount}",
            ['relation' => $relation, 'count' => $actualCount, 'expected' => $expectedCount, 'operator' => $operator]
        );
    }

    /**
     * Check if all children pass a condition.
     */
    protected function checkAllChildrenPass(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $relation = $requirement->target_relation;
        $field = $requirement->target_field;
        $expectedValue = $requirement->getDecodedTargetValue();

        if (!method_exists($project, $relation)) {
            return new RequirementCheckResult(false, "Relation '{$relation}' does not exist on Project model");
        }

        $total = $project->{$relation}()->count();
        
        if ($total === 0) {
            return new RequirementCheckResult(false, "No {$relation} found to check");
        }

        // Count items that pass the condition
        $passingCount = $project->{$relation}()
            ->where($field, $this->normalizeValue($expectedValue))
            ->count();

        $passed = $passingCount === $total;

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? "All {$total} {$relation} have {$field} = {$expectedValue}"
                : "{$passingCount}/{$total} {$relation} have {$field} = {$expectedValue}",
            ['relation' => $relation, 'field' => $field, 'passing' => $passingCount, 'total' => $total]
        );
    }

    /**
     * Check if a document type is uploaded.
     */
    protected function checkDocumentUploaded(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $documentType = $requirement->target_value;

        // Check media library for documents with the specified collection
        $hasDocument = $project->getMedia($documentType)->isNotEmpty();

        return new RequirementCheckResult(
            $hasDocument,
            $hasDocument ? "Document type '{$documentType}' is uploaded" : "Document type '{$documentType}' not found",
            ['document_type' => $documentType, 'found' => $hasDocument]
        );
    }

    /**
     * Check if a payment has been received.
     */
    protected function checkPaymentReceived(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $paymentType = $requirement->target_value; // 'deposit', 'final', etc.

        $salesOrder = $project->orders()->first();
        
        if (!$salesOrder) {
            return new RequirementCheckResult(false, 'No sales order found');
        }

        $paid = match ($paymentType) {
            'deposit' => $salesOrder->deposit_paid_at !== null,
            'final' => $salesOrder->final_paid_at !== null,
            default => false,
        };

        return new RequirementCheckResult(
            $paid,
            $paid ? "Payment '{$paymentType}' received" : "Payment '{$paymentType}' not received",
            ['payment_type' => $paymentType, 'received' => $paid]
        );
    }

    /**
     * Check if a task type is completed.
     */
    protected function checkTaskCompleted(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $taskType = $requirement->target_value;

        $completed = $project->tasks()
            ->where('task_type', $taskType)
            ->where('state', 'done')
            ->exists();

        return new RequirementCheckResult(
            $completed,
            $completed ? "Task type '{$taskType}' is completed" : "Task type '{$taskType}' not completed",
            ['task_type' => $taskType, 'completed' => $completed]
        );
    }

    /**
     * Execute a custom check class/method.
     */
    protected function checkCustom(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $class = $requirement->custom_check_class;
        $method = $requirement->custom_check_method;

        if (!class_exists($class)) {
            return new RequirementCheckResult(false, "Custom check class '{$class}' not found");
        }

        $checker = app($class);

        if (!method_exists($checker, $method)) {
            return new RequirementCheckResult(false, "Method '{$method}' not found on '{$class}'");
        }

        return $checker->{$method}($project, $requirement);
    }

    /**
     * Get the target model for a requirement.
     */
    protected function getTarget(Project $project, GateRequirement $requirement): ?Model
    {
        $targetModel = $requirement->target_model;

        if (empty($targetModel) || $targetModel === 'Project') {
            return $project;
        }

        // Handle related models
        return match ($targetModel) {
            'SalesOrder' => $project->orders()->first(),
            'Partner' => $project->partner,
            default => null,
        };
    }

    /**
     * Compare two values with an operator.
     */
    protected function compare($actual, $expected, string $operator): bool
    {
        return match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            default => false,
        };
    }

    /**
     * Normalize a value for database comparison.
     */
    protected function normalizeValue($value)
    {
        if ($value === 'true' || $value === true) {
            return true;
        }
        if ($value === 'false' || $value === false) {
            return false;
        }
        if (is_numeric($value)) {
            return $value + 0; // Convert to int or float
        }
        return $value;
    }

    /**
     * Check if all CNC programs are complete.
     */
    protected function checkAllCncComplete(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $checker = app(AllCncProgramsCompleteCheck::class);

        return $checker->check($project, $requirement);
    }
}
