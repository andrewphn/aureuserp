<?php 

namespace Webkul\Support\Filament\Forms\Components\Actions;

use Filament\Actions\Action as BaseAction;
use Webkul\Support\Filament\Forms\Components\Repeater;

class Action extends BaseAction
{
    protected ?Repeater $repeater = null;

    public function repeater(?Repeater $repeater): static
    {
        $this->repeater = $repeater;

        return $this;
    }

    public function getRepeater(): ?Repeater
    {
        return $this->repeater;
    }
}