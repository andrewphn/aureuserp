<?php

namespace Webkul\Support\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater as BaseRepeater;
use Filament\Support\Enums\Size;
use Filament\Tables\Table\Concerns\HasColumnManager;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;

/**
 * Repeater class
 *
 * @see \Filament\Resources\Resource
 */
class Repeater extends BaseRepeater
{
    use HasColumnManager;

    protected ?string $columnManagerSessionKey = null;

    protected bool | Closure | null $isRepeaterHasTableView = false;

    public function getDefaultView(): string
    {
        if ($this->hasTableView()) {
            return 'support::filament.forms.components.repeater.table';
        }

        return (string) parent::getDefaultView();
    }

    /**
     * Define the table schema
     *
     * @return static
     */
    public function table(array | Closure | null $columns): static
    {
        $this->isRepeaterHasTableView = true;

        $this->tableColumns = $columns;

        return $this;
    }

    /**
     * Has Table View
     *
     * @return bool
     */
    public function hasTableView(): bool
    {
        return $this->evaluate($this->isRepeaterHasTableView) || filled($this->getTableColumns());
    }

    public function getColumnManagerSessionKey(): string
    {
        return $this->columnManagerSessionKey ??= 'repeater_'.$this->getStatePath().'_column_manager';
    }

    public function getMappedColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $savedState = session($this->getColumnManagerSessionKey(), []);

        return collect($columns)->map(
            function (TableColumn $column) use ($savedState): array {
                $columnName = $column->getName();

                $isToggled = data_get($savedState, "{$columnName}.isToggled", ! $column->isToggledHiddenByDefault());

                return [
                    'type'                     => 'column',
                    'name'                     => $columnName,
                    'label'                    => $column->getLabel(),
                    'isHidden'                 => $column->isHidden(),
                    'isToggled'                => $isToggled,
                    'isToggleable'             => $column->isToggleable(),
                    'isToggledHiddenByDefault' => $column->isToggledHiddenByDefault(),
                ];
            }
        )->toArray();
    }

    public function getTableColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $savedState = session($this->getColumnManagerSessionKey(), []);

        $visibleColumns = collect($columns)->filter(
            function (TableColumn $column) use ($savedState): bool {
                if ($column->isHidden()) {
                    return false;
                }

                $columnName = $column->getName();

                if (data_get($savedState, $columnName)) {
                    return data_get($savedState, "{$columnName}.isToggled", false);
                }

                return ! $column->isToggledHiddenByDefault();
            }
        );

        return $visibleColumns->values()->toArray();
    }

    /**
     * Has Toggleable Columns
     *
     * @return bool
     */
    public function hasToggleableColumns(): bool
    {
        $columns = $this->evaluate($this->tableColumns) ?? [];

        return collect($columns)->contains(fn ($column) => $column->isToggleable());
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
            ->icon('heroicon-s-view-columns')
            ->color('gray')
            ->livewireClickHandlerEnabled(false)
            ->authorize(true);

        if ($this->modifyColumnManagerTriggerActionUsing) {
            $action = $this->evaluate($this->modifyColumnManagerTriggerActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        if ($action->getView() === Action::BUTTON_VIEW) {
            $action->defaultSize(Size::Small->value);
        }

        return $action;
    }

    /**
     * Apply Table Column Manager
     *
     * @param ?array $columns
     * @return void
     */
    public function applyTableColumnManager(?array $columns = null): void
    {
        if (blank($columns)) {
            return;
        }

        $columnState = collect($columns)
            ->filter(fn ($column) => filled(data_get($column, 'name')) && ! is_null(data_get($column, 'isToggled')))
            ->mapWithKeys(fn ($column) => [
                data_get($column, 'name') => [
                    'isToggled'    => data_get($column, 'isToggled'),
                    'isToggleable' => data_get($column, 'isToggleable', true),
                ],
            ])
            ->toArray();

        session([$this->getColumnManagerSessionKey() => $columnState]);
    }

    /**
     * Reset Table Column Manager
     *
     * @return void
     */
    public function resetTableColumnManager(): void
    {
        session()->forget($this->getColumnManagerSessionKey());
    }

    /**
     * Has Deferred Column Manager
     *
     * @return bool
     */
    public function hasDeferredColumnManager(): bool
    {
        return false;
    }
}
