<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\TextInput;
use Webkul\Project\Services\CabinetParsingService;

/**
 * Cabinet Code Input Molecule Component
 * 
 * Reusable cabinet code input with parsing logic following atomic design principles
 */
class CabinetCodeInput
{
    /**
     * Get cabinet code input field with parsing callback
     * 
     * @param callable|null $onParsed Callback function that receives parsed data: ['type' => string|null, 'width' => float|null]
     * @return TextInput
     */
    public static function getCabinetCodeInput(?callable $onParsed = null): TextInput
    {
        $field = TextInput::make('code')
            ->label('Code')
            ->placeholder('B24, W3012, SB36')
            ->helperText('Enter code like B24 to auto-fill type and width')
            ->live(debounce: 300);

        if ($onParsed) {
            $field->afterStateUpdated(function ($state, callable $set) use ($onParsed) {
                if ($state) {
                    $parsed = CabinetParsingService::parseFromName($state);
                    
                    if ($parsed['type']) {
                        $set('cabinet_type', $parsed['type']);
                    }
                    
                    if ($parsed['width']) {
                        $set('length_inches', $parsed['width']);
                    }
                    
                    // Call custom callback if provided
                    if ($onParsed) {
                        $onParsed($parsed, $set);
                    }
                }
            });
        }

        return $field;
    }

}
