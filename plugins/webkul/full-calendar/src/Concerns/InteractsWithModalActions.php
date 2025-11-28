<?php

namespace Webkul\FullCalendar\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use InvalidArgumentException;

/**
 * Interacts With Modal Actions trait
 *
 */
trait InteractsWithModalActions
{
    protected array $cachedModalActions = [];

    /**
     * Booted Interacts With Modal Actions
     *
     * @return void
     */
    public function bootedInteractsWithModalActions(): void
    {
        $this->cacheModalActions();
    }

    /**
     * Cache Modal Actions
     *
     * @return void
     */
    protected function cacheModalActions(): void
    {
        foreach ($this->modalActions() as $action) {
            if ($action instanceof ActionGroup) {
                $action->livewire($this);

                $flatActions = $action->getFlatActions();

                $this->mergeCachedActions($flatActions);

                $this->cachedModalActions[] = $action;

                continue;
            }

            if (! $action instanceof Action) {
                throw new InvalidArgumentException('Header actions must be an instance of '.Action::class.', or '.ActionGroup::class.'.');
            }

            $this->cacheAction($action);

            $this->cachedModalActions[] = $action;
        }
    }

    public function getCachedModalActions(): array
    {
        if (! $this->getModel()) {
            return [];
        }

        return $this->cachedModalActions;
    }

    /**
     * Modal Actions
     *
     * @return array
     */
    public function modalActions(): array
    {
        return [];
    }
}
