<?php

namespace App\Forms\Components;

use Filament\Forms\Components\ViewField;
use Webkul\Project\Models\Project;

/**
 * Inline Calendar class
 *
 */
class InlineCalendar extends ViewField
{
    protected string $view = 'forms.components.inline-calendar';

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewData([
            'occupiedDates' => $this->getOccupiedDates(),
            'startDateStatePath' => 'start_date',
            'endDateStatePath' => 'end_date',
        ]);

        $this->dehydrated(false);
    }

    protected function getOccupiedDates(): array
    {
        return Project::query()
            ->whereNotNull('end_date')
            ->pluck('end_date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->toArray();
    }
}
