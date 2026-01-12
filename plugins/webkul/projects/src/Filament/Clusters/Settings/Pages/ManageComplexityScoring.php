<?php

namespace Webkul\Project\Filament\Clusters\Settings\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Section;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use UnitEnum;
use Webkul\Project\Settings\ComplexityScoringSettings;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Manage Complexity Scoring Settings
 *
 * Admin page for configuring complexity scoring parameters.
 */
class ManageComplexityScoring extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|UnitEnum|null $navigationGroup = 'Project';

    protected static ?int $navigationSort = 10;

    protected static string $settings = ComplexityScoringSettings::class;

    protected static ?string $cluster = Settings::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Complexity Scoring Settings',
        ];
    }

    public function getTitle(): string
    {
        return 'Complexity Scoring Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Complexity Scoring';
    }

    /**
     * Define the form schema with all complexity scoring configuration.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Base Scores')
                    ->description('Base complexity points for each component type')
                    ->columnSpan(1)
                    ->collapsible()
                    ->schema([
                        KeyValue::make('base_scores')
                            ->label('Component Base Scores')
                            ->keyLabel('Component Type')
                            ->valueLabel('Base Points')
                            ->addActionLabel('Add Component Type')
                            ->helperText('Base points before any modifications. Higher = more complex.')
                            ->reorderable()
                            ->required(),
                    ]),

                Section::make('Component Weights')
                    ->description('Weights for averaging component scores into parent')
                    ->columnSpan(1)
                    ->collapsible()
                    ->schema([
                        KeyValue::make('component_weights')
                            ->label('Averaging Weights')
                            ->keyLabel('Component Type')
                            ->valueLabel('Weight')
                            ->addActionLabel('Add Weight')
                            ->helperText('Higher weight = more influence on parent score. Use decimals (e.g., 1.2)')
                            ->reorderable()
                            ->required(),
                    ]),

                Section::make('Score Thresholds')
                    ->description('Score ranges for complexity labels (Simple, Standard, Complex, etc.)')
                    ->columnSpan(1)
                    ->collapsible()
                    ->schema([
                        KeyValue::make('score_thresholds')
                            ->label('Threshold Values')
                            ->keyLabel('Label')
                            ->valueLabel('Max Score')
                            ->addActionLabel('Add Threshold')
                            ->helperText('Score must be LESS than threshold. E.g., simple=10 means 0-9 is Simple.')
                            ->reorderable()
                            ->required(),
                    ]),

                Section::make('Standard Dimensions')
                    ->description('Dimensions considered "standard" - non-standard adds complexity points')
                    ->columnSpan(1)
                    ->collapsible()
                    ->schema([
                        TagsInput::make('standard_door_widths')
                            ->label('Standard Door Widths (inches)')
                            ->placeholder('Add width...')
                            ->helperText('Doors outside these widths get +3 complexity points')
                            ->separator(','),

                        TagsInput::make('standard_door_heights')
                            ->label('Standard Door Heights (inches)')
                            ->placeholder('Add height...')
                            ->helperText('Doors outside these heights get +3 complexity points')
                            ->separator(','),

                        TagsInput::make('standard_drawer_widths')
                            ->label('Standard Drawer Widths (inches)')
                            ->placeholder('Add width...')
                            ->helperText('Drawers outside these widths get +3 complexity points')
                            ->separator(','),
                    ]),

                Section::make('Modification Points')
                    ->description('Points added for specific features and upgrades')
                    ->columnSpan(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        KeyValue::make('modification_points')
                            ->label('Feature Complexity Points')
                            ->keyLabel('Feature Key')
                            ->valueLabel('Points')
                            ->addActionLabel('Add Modifier')
                            ->helperText('Points added when component has this feature. Keys: soft_close, hinge_euro_concealed, has_glass, joinery_dovetail, etc.')
                            ->reorderable()
                            ->required(),
                    ]),
            ]);
    }
}
