<?php

namespace Webkul\Support\Filament\Forms\Components;

use Filament\Forms\Components\Repeater as BaseRepeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;

class Repeater extends BaseRepeater
{
    public function getDefaultView(): string
    {
        return 'support::filament.forms.components.repeater.table';
    }

    public function getTableColumns(): ?array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            return null;
        }

        $visibleColumns = array_filter(
            $columns,
            fn (TableColumn $column): bool => ! $column->isHidden()
        );

        return array_values($visibleColumns);
    }
}
