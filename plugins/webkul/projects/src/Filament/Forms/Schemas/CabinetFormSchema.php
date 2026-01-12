<?php

namespace Webkul\Project\Filament\Forms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetCodeInput;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetDimensionsFields;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetTypeSelect;

/**
 * Cabinet Form Schema Organism
 * 
 * Complete form schema for cabinet creation/editing following atomic design principles
 */
class CabinetFormSchema
{
    /**
     * Cabinet type defaults for auto-filling dimensions
     */
    protected static array $typeDefaults = [
        'base' => ['depth_inches' => 24, 'height_inches' => 34.5],
        'wall' => ['depth_inches' => 12, 'height_inches' => 30],
        'tall' => ['depth_inches' => 24, 'height_inches' => 84],
        'vanity' => ['depth_inches' => 21, 'height_inches' => 34.5],
    ];

    /**
     * Configure the full cabinet form schema
     * 
     * @param Schema $schema
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            static::getCabinetDetailsSection(),
        ]);
    }

    /**
     * Get cabinet details section
     * 
     * @return Section
     */
    public static function getCabinetDetailsSection(): Section
    {
        return Section::make('Cabinet Details')
            ->schema([
                Grid::make(2)->schema([
                    CabinetCodeInput::getCabinetCodeInput(function (array $parsed, callable $set) {
                        if ($parsed['type']) {
                            $defaults = static::$typeDefaults[$parsed['type']] ?? [];
                            $set('depth_inches', $defaults['depth_inches'] ?? 24);
                            $set('height_inches', $defaults['height_inches'] ?? 34.5);
                        }
                    })
                        ->autofocus(),
                    CabinetTypeSelect::getCabinetTypeSelect(),
                ]),
                CabinetDimensionsFields::getSimplifiedDimensionsGrid(),
                TextInput::make('depth_inches')
                    ->label('Depth (in)')
                    ->numeric()
                    ->required()
                    ->default(24)
                    ->minValue(6)
                    ->maxValue(36),
            ]);
    }

    /**
     * Get form components as array (for backward compatibility)
     * 
     * @return array
     */
    public static function getComponents(): array
    {
        return [
            static::getCabinetDetailsSection(),
        ];
    }
}
