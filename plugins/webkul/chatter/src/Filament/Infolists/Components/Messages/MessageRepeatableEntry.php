<?php

namespace Webkul\Chatter\Filament\Infolists\Components\Messages;

use Filament\Infolists\Components\RepeatableEntry;

/**
 * Message Repeatable Entry class
 *
 * @see \Filament\Resources\Resource
 */
class MessageRepeatableEntry extends RepeatableEntry
{
    protected string $view = 'chatter::filament.infolists.components.messages.repeatable-entry';

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
}
