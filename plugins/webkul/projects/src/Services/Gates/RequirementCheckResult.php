<?php

namespace Webkul\Project\Services\Gates;

/**
 * Data transfer object for individual requirement check results.
 */
class RequirementCheckResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly string $message,
        public readonly array $details = []
    ) {}
}
