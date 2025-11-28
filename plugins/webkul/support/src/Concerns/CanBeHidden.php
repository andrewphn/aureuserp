<?php

namespace Webkul\Support\Concerns;

use Closure;

/**
 * Can Be Hidden trait
 *
 */
trait CanBeHidden
{
    protected bool | Closure $isHidden = false;

    protected bool | Closure $isVisible = true;

    protected mixed $evaluationContext = null;

    /**
     * Hidden
     *
     * @return static
     */
    public function hidden(bool | Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    /**
     * Visible
     *
     * @return static
     */
    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    /**
     * Set Evaluation Context
     *
     * @param mixed $context
     * @return static
     */
    public function setEvaluationContext(mixed $context): static
    {
        $this->evaluationContext = $context;

        return $this;
    }

    /**
     * Is Hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        if ($this->evaluateCondition($this->isHidden)) {
            return true;
        }

        return ! $this->evaluateCondition($this->isVisible);
    }

    /**
     * Is Visible
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }

    /**
     * Evaluate Condition
     *
     * @return bool
     */
    protected function evaluateCondition(bool | Closure $condition): bool
    {
        if ($condition instanceof Closure) {
            if ($this->evaluationContext) {
                return (bool) $condition($this->evaluationContext);
            }
            
            if (method_exists($this, 'evaluate')) {
                return (bool) $this->evaluate($condition);
            }
            
            return (bool) $condition();
        }

        return (bool) $condition;
    }
}