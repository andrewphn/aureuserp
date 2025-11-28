<?php

namespace Webkul\Chatter\Filament\Pages\Concerns;

use Webkul\Chatter\Filament\Widgets\ChatterWidget;

/**
 * Has Chatter trait
 *
 */
trait HasChatter
{
    protected function getFooterWidgets(): array
    {
        return [
            ChatterWidget::class,
        ];
    }
}
