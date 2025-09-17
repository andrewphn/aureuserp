<?php

namespace Webkul\Support\Filament\Forms\Components;

use Filament\Forms\Components\Repeater as BaseRepeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;
use Filament\Tables\Table\Concerns\HasColumnManager;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Webkul\Support\Filament\Forms\Components\Actions\Action;

class Repeater extends BaseRepeater
{
    use HasColumnManager;

    protected string | null $columnManagerSessionKey = null;

    public static string $view = 'support::filament.forms.components.repeater.table';

    public function getDefaultView(): string
    {
        return static::$view;
    }

    public function getColumnManagerSessionKey(): string
    {
        return $this->columnManagerSessionKey ??= 'repeater_' . $this->getStatePath() . '_column_manager';
    }

    public function getMappedColumnsForColumnManager(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $savedState = session($this->getColumnManagerSessionKey(), []);

        return array_map(
            function (TableColumn $column) use ($savedState): array {
                $columnName = $column->getName();
                $isToggled = isset($savedState[$columnName]) 
                    ? $savedState[$columnName]['isToggled'] 
                    : !$column->isToggledHiddenByDefault();

                return [
                    'type' => 'column',
                    'name' => $columnName,
                    'label' => $column->getLabel(),
                    'isHidden' => $column->isHidden(),
                    'isToggled' => $isToggled,
                    'isToggleable' => $column->isToggleable(),
                    'isToggledHiddenByDefault' => $column->isToggledHiddenByDefault(),
                ];
            },
            $columns,
        );
    }
    
    public function getTableColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $savedState = session($this->getColumnManagerSessionKey(), []);

        $visibleColumns = array_filter(
            $columns,
            function (TableColumn $column) use ($savedState): bool {
                if ($column->isHidden()) {
                    return false;
                }

                $columnName = $column->getName();
                
                if (isset($savedState[$columnName])) {
                    return $savedState[$columnName]['isToggled'];
                }

                return ! $column->isToggledHiddenByDefault();
            }
        );

        return array_values($visibleColumns);
    }

    public function hasToggleableColumns(): bool
    {
        $columns = $this->evaluate($this->tableColumns) ?? [];
        
        foreach ($columns as $column) {
            if ($column->isToggleable()) {
                return true;
            }
        }

        return false;
    }

    public function getColumnManagerApplyAction(): Action
    {
        $action = Action::make('applyTableColumnManager')
            ->label(__('filament-tables::table.column_manager.actions.apply.label'))
            ->button()
            ->repeater($this)
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
            ->repeater($this)
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

    public function applyTableColumnManager(?array $columns = null): void
    {
        if (! $columns) {
            return;
        }

        $columnState = [];
        
        foreach ($columns as $column) {
            if (isset($column['name']) && isset($column['isToggled'])) {
                $columnState[$column['name']] = [
                    'isToggled' => $column['isToggled'],
                    'isToggleable' => $column['isToggleable'] ?? true,
                ];
            }
        }

        session([$this->getColumnManagerSessionKey() => $columnState]);
    }

    public function resetTableColumnManager(): void
    {
        session()->forget($this->getColumnManagerSessionKey());
    }

    public function hasDeferredColumnManager(): bool
    {
        return false;
    }
}