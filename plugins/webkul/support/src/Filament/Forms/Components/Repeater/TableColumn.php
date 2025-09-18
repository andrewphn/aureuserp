<?php

namespace Webkul\Support\Filament\Forms\Components\Repeater;

use Closure;
use LogicException;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Schemas\Components\Concerns\HasName;
use Webkul\Support\Concerns\CanBeHidden;
use Filament\Tables\Columns\Concerns\CanBeToggled;
use Filament\Support\Components\Component;
use Filament\Support\Concerns\CanWrapHeader;
use Filament\Support\Concerns\HasAlignment;
use Filament\Support\Concerns\HasWidth;

class TableColumn extends Component
{
    use CanBeHidden;
    use CanBeToggled;
    use HasName;
    use CanWrapHeader;
    use HasAlignment;
    use HasWidth;
    use HasLabel;

    protected string $evaluationIdentifier = 'column';

    protected bool | Closure $isHeaderLabelHidden = false;

    protected bool | Closure $isMarkedAsRequired = false;

    final public function __construct(string $name)
    {
        $this->name($name);
    }

    public static function make(string | Closure $name): static
    {
        $columnClass = static::class;

        $name ??= static::getDefaultName();

        if (blank($name)) {
            throw new LogicException("Column of class [$columnClass] must have a unique name, passed to the [make()] method.");
        }

        $static = app($columnClass, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public function hiddenHeaderLabel(bool | Closure $condition = true): static
    {
        $this->isHeaderLabelHidden = $condition;

        return $this;
    }

    public function isHeaderLabelHidden(): bool
    {
        return (bool) $this->evaluate($this->isHeaderLabelHidden);
    }

    public function markAsRequired(bool | Closure $condition = true): static
    {
        $this->isMarkedAsRequired = $condition;

        return $this;
    }

    public function isMarkedAsRequired(): bool
    {
        return (bool) $this->evaluate($this->isMarkedAsRequired);
    }


    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'context', 'operation' => [$this->getContainer()->getOperation()],
            'get' => [$this->makeGetUtility()],
            'livewire' => [$this->getLivewire()],
            'model' => [$this->getModel()],
            'rawState' => [$this->getRawState()],
            'record' => [$this->getRecord()],
            'set' => [$this->makeSetUtility()],
            'state' => [$this->getState()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        $record = $this->getRecord();

        if ((! $record) || is_array($record)) {
            return match ($parameterType) {
                Get::class => [$this->makeGetUtility()],
                Set::class => [$this->makeSetUtility()],
                default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
            };
        }

        return match ($parameterType) {
            Get::class => [$this->makeGetUtility()],
            Model::class, $record::class => [$record],
            Set::class => [$this->makeSetUtility()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}
