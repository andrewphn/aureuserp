<?php

namespace Webkul\Support\Filament\Forms\Components\Repeater;

use Filament\Forms\Components\Repeater\TableColumn as BaseTableColumn;
use Webkul\Support\Concerns\CanBeHidden;
use Filament\Tables\Columns\Concerns\CanBeToggled;

class TableColumn extends BaseTableColumn
{
    use CanBeHidden;
    use CanBeToggled;
}
