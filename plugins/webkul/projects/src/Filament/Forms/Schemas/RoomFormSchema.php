<?php

namespace Webkul\Project\Filament\Forms\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Project\Filament\Forms\Schemas\Components\LocationTypeSelect;
use Webkul\Project\Filament\Forms\Schemas\Components\PricingFields;
use Webkul\Project\Filament\Forms\Schemas\Components\RoomTypeSelect;
use Webkul\Project\Filament\Forms\Schemas\LocationFormSchema;
use Webkul\Project\Filament\Forms\Schemas\RunFormSchema;

/**
 * Room Form Schema Organism
 * 
 * Complete form schema for room creation/editing following atomic design principles
 */
class RoomFormSchema
{
    /**
     * Configure the full room form schema with nested locations/runs/cabinets
     * 
     * @param Schema $schema
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onRoomTypeUpdated Callback for room type changes
     * @return Schema
     */
    public static function configure(
        Schema $schema,
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onRoomTypeUpdated = null
    ): Schema {
        return $schema->components([
            static::getRoomDetailsSection($onRoomTypeUpdated),
            static::getDefaultPricingSection($pricingTiers, $materialOptions, $finishOptions),
            static::getLocationsRepeater($pricingTiers, $materialOptions, $finishOptions),
        ]);
    }

    /**
     * Configure simplified room form (basic details only)
     * 
     * @param Schema $schema
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onRoomTypeUpdated
     * @return Schema
     */
    public static function configureSimplified(
        Schema $schema,
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onRoomTypeUpdated = null
    ): Schema {
        return $schema->components([
            static::getRoomDetailsSection($onRoomTypeUpdated),
            static::getDefaultPricingSection($pricingTiers, $materialOptions, $finishOptions),
        ]);
    }

    /**
     * Get room details section
     * 
     * @param callable|null $onRoomTypeUpdated
     * @return Section
     */
    public static function getRoomDetailsSection(?callable $onRoomTypeUpdated = null): Section
    {
        $roomTypeSelect = RoomTypeSelect::getRoomTypeSelect();
        
        if ($onRoomTypeUpdated) {
            $roomTypeSelect->live()
                ->afterStateUpdated(function ($state, callable $set) use ($onRoomTypeUpdated) {
                    if ($onRoomTypeUpdated) {
                        $onRoomTypeUpdated($state, $set);
                    }
                });
        }

        return Section::make('Room Details')
            ->schema([
                Grid::make(3)->schema([
                    $roomTypeSelect,
                    TextInput::make('name')
                        ->label('Room Name')
                        ->required()
                        ->default('Kitchen')
                        ->helperText('Auto-generated. Edit if needed.'),
                    TextInput::make('floor_number')
                        ->label('Floor')
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->maxValue(99),
                ]),
            ]);
    }

    /**
     * Get default pricing section
     * 
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @return Section
     */
    public static function getDefaultPricingSection(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions
    ): Section {
        return Section::make('Default Pricing')
            ->description('These defaults will apply to all cabinets in this room unless overridden.')
            ->schema([
                Grid::make(3)->schema([
                    \Filament\Forms\Components\Select::make('cabinet_level')
                        ->label('Cabinet Level')
                        ->options($pricingTiers)
                        ->default('3'),
                    \Filament\Forms\Components\Select::make('material_category')
                        ->label('Material')
                        ->options($materialOptions)
                        ->default('stain_grade'),
                    \Filament\Forms\Components\Select::make('finish_option')
                        ->label('Finish')
                        ->options($finishOptions)
                        ->default('unfinished'),
                ]),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get locations repeater with nested runs/cabinets
     * 
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @return Repeater
     */
    public static function getLocationsRepeater(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions
    ): Repeater {
        return Repeater::make('locations')
            ->label('')
            ->schema([
                LocationFormSchema::getLocationDetailsSection(),
                LocationFormSchema::getRunsRepeater($pricingTiers, $materialOptions, $finishOptions),
            ])
            ->defaultItems(0)
            ->addActionLabel('+ Add Location')
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Location');
    }

    /**
     * Get form components as array (for backward compatibility)
     * Returns simplified version (no nested repeaters) for edit actions
     * 
     * @param array $pricingTiers
     * @param array $materialOptions
     * @param array $finishOptions
     * @param callable|null $onRoomTypeUpdated
     * @return array
     */
    public static function getComponents(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        ?callable $onRoomTypeUpdated = null
    ): array {
        return [
            static::getRoomDetailsSection($onRoomTypeUpdated),
            static::getDefaultPricingSection($pricingTiers, $materialOptions, $finishOptions),
        ];
    }
}
