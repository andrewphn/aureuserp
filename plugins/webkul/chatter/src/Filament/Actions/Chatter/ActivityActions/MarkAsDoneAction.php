<?php

namespace Webkul\Chatter\Filament\Actions\Chatter\ActivityActions;

use Filament\Actions\Action;

/**
 * Mark As Done Action Filament action
 *
 * @see \Filament\Resources\Resource
 */
class MarkAsDoneAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activity.mark_as_done.action';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('gray')
            ->outlined()
            ->slideOver(false);
    }
}
