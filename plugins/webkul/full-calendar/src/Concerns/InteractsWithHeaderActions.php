<?php

namespace Webkul\FullCalendar\Concerns;

use Filament\Actions\ActionGroup;

/**
 * Interacts With Header Actions trait
 *
 */
trait InteractsWithHeaderActions
{
    protected array $cachedHeaderActions = [];

    /**
     * Booted Interacts With Header Actions
     *
     * @return void
     */
    public function bootedInteractsWithHeaderActions(): void
    {
        $this->cacheHeaderActions();
    }

    /**
     * Cache Header Actions
     *
     * @return void
     */
    protected function cacheHeaderActions(): void
    {
        $actions = $this->headerActions();

        foreach ($actions as $action) {
            if ($action instanceof ActionGroup) {
                $action->livewire($this);

                if (! $action->getDropdownPlacement()) {
                    $action->dropdownPlacement('bottom-end');
                }

                $flatActions = $action->getFlatActions();

                $this->mergeCachedActions($flatActions);

                $this->cachedHeaderActions[] = $action;

                continue;
            }

            $this->cacheAction($action);

            $this->cachedHeaderActions[] = $action;
        }
    }

    public function getCachedHeaderActions(): array
    {
        if (! $this->getModel()) {
            return [];
        }

        return $this->cachedHeaderActions;
    }

    /**
     * Header Actions
     *
     * @return array
     */
    protected function headerActions(): array
    {
        return [];
    }
}
