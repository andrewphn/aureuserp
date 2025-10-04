<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class TagSelectorPanel extends Field
{
    protected string $view = 'forms.components.tag-selector-panel';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

    public function relationship(string $name, string $titleAttribute): static
    {
        $this->relationship = $name;
        $this->titleAttribute = $titleAttribute;

        return $this;
    }
}
