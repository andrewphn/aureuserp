<?php

namespace Webkul\Support\Filament\Forms\Components;

use Filament\Forms\Components\Repeater as BaseRepeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;
use Filament\Tables\Table\Concerns\HasColumnManager;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

class Repeater extends BaseRepeater
{
    use HasColumnManager;
    
    public function getDefaultView(): string
    {
        return 'support::filament.forms.components.repeater.table';
    }
    
    public function getTableColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $visibleColumns = array_filter(
            $columns,
            fn (TableColumn $column): bool => ! $column->isHidden() && !($column->isToggledHiddenByDefault() && $column->isToggledHiddenByDefault())
        );

        return array_values($visibleColumns);
    }

    public function hasToggleableColumns(): bool
    {
        foreach ($this->getTableColumns() as $column) {
            if (! $column->isToggleable()) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getColumnManagerApplyAction(): Action
    {
        $action = Action::make('applyTableColumnManager')
            ->label(__('filament-tables::table.column_manager.actions.apply.label'))
            ->button()
            ->visible($this->hasDeferredColumnManager())
            ->alpineClickHandler('applyTableColumnManager')
            ->authorize(true);

        if ($this->modifyColumnManagerApplyActionUsing) {
            $action = $this->evaluate($this->modifyColumnManagerApplyActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        return $action;
    }

     public function getColumnManagerTriggerAction(): Action
    {
        $action = Action::make('openColumnManager')
            ->label(__('filament-tables::table.actions.column_manager.label'))
            ->iconButton()
            ->icon(Heroicon::ViewColumns)
            ->color('gray')
            ->livewireClickHandlerEnabled(false)
            ->authorize(true);

        if ($this->modifyColumnManagerTriggerActionUsing) {
            $action = $this->evaluate($this->modifyColumnManagerTriggerActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        if ($action->getView() === Action::BUTTON_VIEW) {
            $action->defaultSize(Size::Small);
        }

        return $action;
    }
}
