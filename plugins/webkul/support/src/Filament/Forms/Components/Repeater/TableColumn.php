<?php

namespace Webkul\Support\Filament\Forms\Components\Repeater;

use Filament\Forms\Components\Repeater\TableColumn as BaseTableColumn;
use Filament\Schemas\Components\Concerns\HasName;
use Webkul\Support\Concerns\CanBeHidden;
use Filament\Tables\Columns\Concerns\CanBeToggled;

class TableColumn extends BaseTableColumn
{
    use CanBeHidden;
    use CanBeToggled;
    use HasName;

     public function getName(): string
    {
        return $this->getLabel();
    }
}
