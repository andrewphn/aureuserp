<?php

namespace Webkul\Project\Filament\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetCodeInput;
use Webkul\Project\Filament\Forms\Schemas\Components\CabinetTypeSelect;
use Webkul\Project\Filament\Forms\Schemas\Components\LocationTypeSelect;
use Webkul\Project\Filament\Forms\Schemas\Components\PricingFields;
use Webkul\Project\Filament\Forms\Schemas\RunFormSchema;

/**
 * Location Form Schema Organism
 * 
 * Complete form schema for location creation/editing following atomic design principles
 */
class LocationFormSchema
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
     * Configure the full location form schema with nested runs/cabinets
     * 
     * @param Schema $schema
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onLocationTypeUpdated Callback for location type changes
     * @return Schema
     */
    public static function configure(
        Schema $schema,
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onLocationTypeUpdated = null
    ): Schema {
        return $schema->components([
            static::getLocationDetailsSection($onLocationTypeUpdated),
            PricingFields::getPricingOverrideSection($pricingTiers, $materialOptions, $finishOptions),
            static::getRunsRepeater($pricingTiers, $materialOptions, $finishOptions),
        ]);
    }

    /**
     * Configure simplified location form (basic details only)
     * 
     * @param Schema $schema
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onLocationTypeUpdated
     * @return Schema
     */
    public static function configureSimplified(
        Schema $schema,
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onLocationTypeUpdated = null
    ): Schema {
        return $schema->components([
            static::getLocationDetailsSection($onLocationTypeUpdated),
            PricingFields::getPricingOverrideSection($pricingTiers, $materialOptions, $finishOptions),
        ]);
    }

    /**
     * Get location details section
     * 
     * @param callable|null $onLocationTypeUpdated
     * @return Section
     */
    public static function getLocationDetailsSection(?callable $onLocationTypeUpdated = null): Section
    {
        $locationTypeSelect = LocationTypeSelect::getLocationTypeSelect();
        
        if ($onLocationTypeUpdated) {
            $locationTypeSelect->live()
                ->afterStateUpdated(function ($state, callable $set) use ($onLocationTypeUpdated) {
                    $typeNames = [
                        'wall' => 'Wall',
                        'island' => 'Island',
                        'peninsula' => 'Peninsula',
                        'corner' => 'Corner',
                    ];
                    $set('name', $typeNames[$state] ?? ucfirst($state));
                    
                    if ($onLocationTypeUpdated) {
                        $onLocationTypeUpdated($state, $set);
                    }
                });
        }

        return Section::make('Location Details')
            ->schema([
                Grid::make(2)->schema([
                    $locationTypeSelect,
                    TextInput::make('name')
                        ->label('Location Name')
                        ->required()
                        ->default('Wall')
                        ->helperText('Auto-generated. Edit if needed.'),
                ]),
            ]);
    }

    /**
     * Get runs repeater with nested cabinets
     * 
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @return Repeater
     */
    public static function getRunsRepeater(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions
    ): Repeater {
        return Repeater::make('runs')
            ->label('')
            ->schema([
                RunFormSchema::getRunDetailsSection(),
                RunFormSchema::getCabinetsRepeater(),
            ])
            ->defaultItems(1)
            ->addActionLabel('+ Add Run')
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Run');
    }

    /**
     * Get form components as array (for backward compatibility)
     * Returns simplified version (no nested repeaters) for edit actions
     * 
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onLocationTypeUpdated
     * @return array
     */
    public static function getComponents(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onLocationTypeUpdated = null
    ): array {
        return [
            static::getLocationDetailsSection($onLocationTypeUpdated),
            PricingFields::getPricingOverrideSection($pricingTiers, $materialOptions, $finishOptions),
        ];
    }
}
