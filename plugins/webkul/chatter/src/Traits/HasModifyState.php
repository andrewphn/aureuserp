<?php

namespace Webkul\Chatter\Traits;

use Closure;
use Illuminate\Support\HtmlString;

/**
 * Has Modify State trait
 *
 */
trait HasModifyState
{
    protected $state;

    /**
     * Modify State
     *
     * @param Closure $callback The callback function
     * @return static
     */
    public function modifyState(Closure $callback): static
    {
        $this->state = $callback;

        return $this;
    }

    public function getModifiedState(): null|string|HtmlString
    {
        return $this->evaluate($this->state);
    }

    /**
     * Get Causer Name
     *
     * @param mixed $causer
     * @return string
     */
    private function getCauserName($causer): string
    {
        return $causer->name ?? $causer->first_name ?? $causer->last_name ?? $causer->username ?? 'Unknown';
    }
}
