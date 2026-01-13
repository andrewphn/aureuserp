<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Create global measurement display settings.
     */
    public function up(): void
    {
        // Display unit: imperial_decimal, imperial_fraction, or metric
        // Default to imperial_fraction to show fractional inches by default
        $this->migrator->add('measurement.display_unit', 'imperial_fraction');

        // Fraction precision: 2 (1/2), 4 (1/4), 8 (1/8), 16 (1/16), 32 (1/32), 64 (1/64)
        // Default to 64 for fine woodworking precision
        $this->migrator->add('measurement.fraction_precision', 64);

        // Whether to show unit symbol (" or mm)
        $this->migrator->add('measurement.show_unit_symbol', true);

        // Decimal places for metric display (mm)
        $this->migrator->add('measurement.metric_precision', 0);

        // Decimal places for linear feet display
        $this->migrator->add('measurement.linear_feet_precision', 2);

        // Input step increment for form fields (in inches)
        // 0.125 = 1/8", 0.0625 = 1/16"
        $this->migrator->add('measurement.input_step', 0.125);
    }

    /**
     * Remove all measurement settings.
     */
    public function down(): void
    {
        $this->migrator->delete('measurement.display_unit');
        $this->migrator->delete('measurement.fraction_precision');
        $this->migrator->delete('measurement.show_unit_symbol');
        $this->migrator->delete('measurement.metric_precision');
        $this->migrator->delete('measurement.linear_feet_precision');
        $this->migrator->delete('measurement.input_step');
    }
};
