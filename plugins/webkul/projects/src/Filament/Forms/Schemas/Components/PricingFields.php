<?php

namespace Webkul\Project\Filament\Forms\Schemas\Components;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

/**
 * Pricing Fields Molecule Component
 * 
 * Reusable pricing override section following atomic design principles
 */
class PricingFields
{
    /**
     * Get pricing override section
     * 
     * @param array $pricingTiers Options for cabinet level
     * @param array $materialOptions Options for material category
     * @param array $finishOptions Options for finish
     * @param bool $collapsed Whether section should be collapsed by default
     * @param string|null $description Section description
     * @return Section
     */
    public static function getPricingOverrideSection(
        array $pricingTiers,
        array $materialOptions,
        array $finishOptions,
        bool $collapsed = true,
        ?string $description = null
    ): Section {
        return Section::make('Pricing Override')
            ->description($description ?? 'Leave blank to inherit from parent defaults.')
            ->schema([
                Grid::make(3)->schema([
                    Select::make('cabinet_level')
                        ->label('Cabinet Level')
                        ->options($pricingTiers)
                        ->placeholder('Inherit'),
                    Select::make('material_category')
                        ->label('Material')
                        ->options($materialOptions)
                        ->placeholder('Inherit'),
                    Select::make('finish_option')
                        ->label('Finish')
                        ->options($finishOptions)
                        ->placeholder('Inherit'),
                ]),
            ])
            ->collapsible()
            ->collapsed($collapsed);
    }
}
