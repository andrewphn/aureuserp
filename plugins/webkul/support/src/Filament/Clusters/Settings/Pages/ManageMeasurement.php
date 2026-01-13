<?php

namespace Webkul\Support\Filament\Clusters\Settings\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use UnitEnum;
use Webkul\Support\Settings\MeasurementSettings;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * Manage Measurement Settings
 *
 * Admin page for configuring how measurements are displayed throughout the application.
 * All measurements are stored in inches and converted for display only.
 */
class ManageMeasurement extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ruler-scale';

    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?int $navigationSort = 5;

    protected static string $settings = MeasurementSettings::class;

    protected static ?string $cluster = Settings::class;

    public function getBreadcrumbs(): array
    {
        return [
            'Measurement Settings',
        ];
    }

    public function getTitle(): string
    {
        return 'Measurement Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Measurements';
    }

    /**
     * Define the form schema for measurement settings.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Display Format')
                    ->description('Choose how measurements are displayed throughout the application. All values are stored in inches and converted for display.')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('display_unit')
                            ->label('Unit System')
                            ->options([
                                'imperial_decimal' => 'Imperial Decimal (24.5")',
                                'imperial_fraction' => 'Imperial Fraction (24 1/2")',
                                'metric' => 'Metric (622 mm)',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Select how dimension values should be displayed'),

                        Select::make('fraction_precision')
                            ->label('Fraction Precision')
                            ->options([
                                2 => 'Halves (1/2")',
                                4 => 'Quarters (1/4")',
                                8 => 'Eighths (1/8")',
                                16 => 'Sixteenths (1/16")',
                                32 => 'Thirty-seconds (1/32")',
                                64 => 'Sixty-fourths (1/64")',
                            ])
                            ->visible(fn ($get) => $get('display_unit') === 'imperial_fraction')
                            ->helperText('Smallest fraction unit to display. Sixty-fourths (1/64") provides the finest precision for woodworking.'),

                        Toggle::make('show_unit_symbol')
                            ->label('Show Unit Symbol')
                            ->helperText('Display " for inches or mm for metric after values'),
                    ]),

                Section::make('Precision Settings')
                    ->description('Control decimal precision for different measurement contexts')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('metric_precision')
                            ->label('Metric Decimal Places')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(2)
                            ->helperText('Number of decimal places for millimeter display (0 = whole mm)')
                            ->visible(fn ($get) => $get('display_unit') === 'metric'),

                        TextInput::make('linear_feet_precision')
                            ->label('Linear Feet Decimal Places')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(4)
                            ->helperText('Decimal places for linear feet calculations (e.g., 2.50 LF)'),

                        TextInput::make('input_step')
                            ->label('Input Step Increment')
                            ->numeric()
                            ->step(0.0625)
                            ->minValue(0.0625)
                            ->maxValue(1)
                            ->helperText('Step value for dimension input fields in inches. Common values: 0.125 (1/8"), 0.0625 (1/16"), 0.25 (1/4")'),
                    ]),

                Section::make('Preview')
                    ->description('See how your current settings will display measurements')
                    ->columnSpan(2)
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('preview_info')
                            ->label('')
                            ->content(function ($get) {
                                $displayUnit = $get('display_unit') ?? 'imperial_decimal';
                                $fractionPrecision = $get('fraction_precision') ?? 64;
                                $showSymbol = $get('show_unit_symbol') ?? true;

                                // Example values including fractional precision examples
                                $examples = [
                                    24.5 => 'Width example (24.5")',
                                    36.0 => 'Height example (36")',
                                    0.75 => 'Material thickness (3/4")',
                                    18.125 => 'Custom dimension (18 1/8")',
                                    41.3125 => 'Fine precision (41 5/16")',
                                ];

                                $previews = [];
                                foreach ($examples as $value => $label) {
                                    $formatted = $this->formatPreview($value, $displayUnit, $fractionPrecision, $showSymbol);
                                    $previews[] = "{$label}: <strong>{$formatted}</strong>";
                                }

                                return new \Illuminate\Support\HtmlString(implode(' | ', $previews));
                            }),
                    ]),
            ]);
    }

    /**
     * Format a preview value based on current settings.
     */
    protected function formatPreview(float $inches, string $displayUnit, int $fractionPrecision, bool $showSymbol): string
    {
        return match ($displayUnit) {
            'imperial_fraction' => $this->formatFractionPreview($inches, $fractionPrecision, $showSymbol),
            'metric' => $this->formatMetricPreview($inches, $showSymbol),
            default => $this->formatDecimalPreview($inches, $showSymbol),
        };
    }

    protected function formatDecimalPreview(float $inches, bool $showSymbol): string
    {
        $formatted = rtrim(rtrim(number_format($inches, 2), '0'), '.');
        return $showSymbol ? $formatted . '"' : $formatted;
    }

    protected function formatFractionPreview(float $inches, int $precision, bool $showSymbol): string
    {
        $whole = (int) floor($inches);
        $decimal = $inches - $whole;

        if (abs($decimal) < 0.001) {
            return $showSymbol ? $whole . '"' : (string) $whole;
        }

        $numerator = (int) round($decimal * $precision);
        if ($numerator === 0) {
            return $showSymbol ? $whole . '"' : (string) $whole;
        }
        if ($numerator === $precision) {
            return $showSymbol ? ($whole + 1) . '"' : (string) ($whole + 1);
        }

        $gcd = $this->gcd($numerator, $precision);
        $fraction = ($numerator / $gcd) . '/' . ($precision / $gcd);
        $result = $whole > 0 ? "{$whole} {$fraction}" : $fraction;

        return $showSymbol ? $result . '"' : $result;
    }

    protected function formatMetricPreview(float $inches, bool $showSymbol): string
    {
        $mm = $inches * 25.4;
        $formatted = number_format($mm, 0);
        return $showSymbol ? $formatted . ' mm' : $formatted;
    }

    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }
}
