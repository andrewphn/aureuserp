<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * Quick Actions slide-over panel for EditProject page
 * Provides organized CRUD access to essential project management tasks
 */
class QuickActionsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'quick-actions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Quick Actions')
            ->icon('heroicon-o-bolt')
            ->color('gray')
            ->slideOver()
            ->modalContentFooter(fn (Model $record): View => view('webkul-project::livewire.quick-actions.quick-actions-panel', [
                'record' => $record,
            ]))
            ->modalHeading('Quick Actions')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }
}
