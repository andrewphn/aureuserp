<?php

namespace Webkul\Support\Concerns;

use Webkul\Support\Filament\Forms\Components\Repeater;

/**
 * Has Repeater Column Manager trait
 *
 */
trait HasRepeaterColumnManager
{
    /**
     * Apply Repeater Column Manager
     *
     * @param string $repeaterKey
     * @param array $columns
     * @return void
     */
    public function applyRepeaterColumnManager(string $repeaterKey, array $columns): void
    {
        $repeater = $this->getRepeaterComponent($repeaterKey);

        if ($repeater) {
            $repeater->applyTableColumnManager($columns);
        }
    }

    /**
     * Reset Repeater Column Manager
     *
     * @param string $repeaterKey
     * @return void
     */
    public function resetRepeaterColumnManager(string $repeaterKey): void
    {
        $repeater = $this->getRepeaterComponent($repeaterKey);

        if ($repeater) {
            $repeater->resetTableColumnManager();
        }
    }

    /**
     * Get Repeater Component
     *
     * @param string $repeaterKey
     * @return ?Repeater
     */
    protected function getRepeaterComponent(string $repeaterKey): ?Repeater
    {
        $form = $this->form->getFlatComponents();

        foreach ($form as $component) {
            if ($component instanceof Repeater && $component->getStatePath() === $repeaterKey) {
                return $component;
            }

            if (method_exists($component, 'getChildComponents')) {
                $found = $this->findRepeaterInComponents($component->getChildComponents(), $repeaterKey);

                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Find Repeater In Components
     *
     * @param array $components
     * @param string $repeaterKey
     * @return ?Repeater
     */
    protected function findRepeaterInComponents(array $components, string $repeaterKey): ?Repeater
    {
        foreach ($components as $component) {
            if ($component instanceof Repeater && $component->getStatePath() === $repeaterKey) {
                return $component;
            }

            if (method_exists($component, 'getChildComponents')) {
                $found = $this->findRepeaterInComponents($component->getChildComponents(), $repeaterKey);

                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }
}