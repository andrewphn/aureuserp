<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * Project Type Cards class
 *
 */
class ProjectTypeCards extends Field
{
    protected string $view = 'forms.components.project-type-cards';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

    /**
     * Options
     *
     * @param array $options
     * @return static
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->evaluate($this->options) ?? [];
    }
}
