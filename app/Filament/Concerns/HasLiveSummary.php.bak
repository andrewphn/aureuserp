<?php

namespace App\Filament\Concerns;

trait HasLiveSummary
{
    /**
     * Get the header widgets for the page.
     * Override this method to include your custom summary widget.
     */
    protected function getHeaderWidgets(): array
    {
        return array_merge(
            parent::getHeaderWidgets(),
            $this->getLiveSummaryWidgets()
        );
    }

    /**
     * Define which summary widgets to display.
     * Override this method in your page class.
     */
    protected function getLiveSummaryWidgets(): array
    {
        return [];
    }
}
