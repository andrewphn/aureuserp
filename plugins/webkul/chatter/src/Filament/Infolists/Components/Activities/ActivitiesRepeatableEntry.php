<?php

namespace Webkul\Chatter\Filament\Infolists\Components\Activities;

use Filament\Infolists\Components\RepeatableEntry;

/**
 * Activities Repeatable Entry class
 *
 * @see \Filament\Resources\Resource
 */
class ActivitiesRepeatableEntry extends RepeatableEntry
{
    /**
     * Setup
     *
     * @return void
     */
    protected function setup(): void
    {
        parent::setup();

        $this->configureRepeatableEntry();
    }

    /**
     * Configure Repeatable Entry
     *
     * @return void
     */
    private function configureRepeatableEntry(): void
    {
        $this
            ->contained(false)
            ->hiddenLabel();
    }

    protected string $view = 'chatter::filament.infolists.components.activities.repeatable-entry';
}
