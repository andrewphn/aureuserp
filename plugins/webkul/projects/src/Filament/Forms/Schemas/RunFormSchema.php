<?php

namespace Webkul\Project\Filament\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetCodeInput;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetDimensionsFields;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetTypeSelect;
use Webkul\Project\Filament\Forms\Schemas\Components\PricingFields;
use Webkul\Project\Filament\Forms\Schemas\Components\RunTypeSelect;

/**
 * Run Form Schema Organism
 * 
 * Complete form schema for cabinet run creation/editing following atomic design principles
 */
class RunFormSchema
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
     * Configure the full run form schema with nested cabinets
     * 
     * @param Schema $schema
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onRunTypeUpdated Callback for run type changes
     * @return Schema
     */
    public static function configure(
        Schema $schema,
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onRunTypeUpdated = null
    ): Schema {
        return $schema->components([
            static::getRunDetailsSection($onRunTypeUpdated),
            PricingFields::getPricingOverrideSection($pricingTiers, $materialOptions, $finishOptions),
            static::getCabinetsRepeater(),
        ]);
    }

    /**
     * Configure simplified run form (basic details only)
     * 
     * @param Schema $schema
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onRunTypeUpdated
     * @return Schema
     */
    public static function configureSimplified(
        Schema $schema,
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onRunTypeUpdated = null
    ): Schema {
        return $schema->components([
            static::getRunDetailsSection($onRunTypeUpdated),
            PricingFields::getPricingOverrideSection($pricingTiers, $materialOptions, $finishOptions),
        ]);
    }

    /**
     * Get run details section
     * 
     * @param callable|null $onRunTypeUpdated
     * @return Section
     */
    public static function getRunDetailsSection(?callable $onRunTypeUpdated = null): Section
    {
        $runTypeSelect = RunTypeSelect::getRunTypeSelect();
        
        if ($onRunTypeUpdated) {
            $runTypeSelect->live()
                ->afterStateUpdated(function ($state, callable $set) use ($onRunTypeUpdated) {
                    $typeNames = [
                        'base' => 'Base Run',
                        'wall' => 'Wall Run',
                        'tall' => 'Tall Run',
                        'island' => 'Island Run',
                        'vanity' => 'Vanity Run',
                    ];
                    $set('name', $typeNames[$state] ?? ucfirst($state) . ' Run');
                    
                    if ($onRunTypeUpdated) {
                        $onRunTypeUpdated($state, $set);
                    }
                });
        }

        return Section::make('Run Details')
            ->schema([
                Grid::make(2)->schema([
                    $runTypeSelect,
                    TextInput::make('name')
                        ->label('Run Name')
                        ->required()
                        ->default('Base Run')
                        ->helperText('Auto-generated. Edit if needed.'),
                ]),
            ]);
    }

    /**
     * Get cabinets repeater
     * 
     * @return Repeater
     */
    public static function getCabinetsRepeater(): Repeater
    {
        return Repeater::make('cabinets')
            ->label('Cabinets')
            ->schema([
                Grid::make(5)->schema([
                    CabinetCodeInput::getCabinetCodeInput(function (array $parsed, callable $set) {
                        if ($parsed['type']) {
                            $defaults = static::$typeDefaults[$parsed['type']] ?? [];
                            $set('depth_inches', $defaults['depth_inches'] ?? 24);
                            $set('height_inches', $defaults['height_inches'] ?? 34.5);
                        }
                    }),
                    CabinetTypeSelect::getCabinetTypeSelect(),
                    CabinetDimensionsFields::getDimensionInput('length_inches', 'Width', 24, true),
                    CabinetDimensionsFields::getDimensionInput('height_inches', 'Height', 34.5, true),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->minValue(1),
                ]),
            ])
            ->defaultItems(1)
            ->addActionLabel('+ Add Cabinet')
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['code'] ?? $state['cabinet_type'] ?? 'Cabinet');
    }

    /**
     * Get form components as array (for backward compatibility)
     * Returns simplified version (no nested repeaters) for edit actions
     * 
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onRunTypeUpdated
     * @return array
     */
    public static function getComponents(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onRunTypeUpdated = null
    ): array {
        return [
            static::getRunDetailsSection($onRunTypeUpdated),
            PricingFields::getPricingOverrideSection($pricingTiers, $materialOptions, $finishOptions),
        ];
    }
}
