<?php

namespace Webkul\Project\Services\Gates;

use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateEvaluation;

/**
 * Data transfer object for gate evaluation results.
 */
class GateEvaluationResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly Gate $gate,
        public readonly GateEvaluation $evaluation,
        public readonly array $requirementResults,
        public readonly array $failureReasons
    ) {}

    /**
     * Get the count of failed requirements.
     */
    public function getFailedCount(): int
    {
        return count($this->failureReasons);
    }

    /**
     * Get the count of passed requirements.
     */
    public function getPassedCount(): int
    {
        return collect($this->requirementResults)
            ->filter(fn($r) => $r['passed'] ?? false)
            ->count();
    }

    /**
     * Get total requirement count.
     */
    public function getTotalCount(): int
    {
        return count($this->requirementResults);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->getTotalCount() === 0) {
            return 100.0;
        }

        return round(($this->getPassedCount() / $this->getTotalCount()) * 100, 1);
    }

    /**
     * Get blockers as a simple array of messages.
     */
    public function getBlockerMessages(): array
    {
        return array_map(
            fn($blocker) => $blocker['error_message'] ?? $blocker['details'] ?? 'Unknown blocker',
            $this->failureReasons
        );
    }
}
