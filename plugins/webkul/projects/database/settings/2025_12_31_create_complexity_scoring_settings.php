<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Create complexity scoring settings with defaults from ComplexityScoreService.
     */
    public function up(): void
    {
        // Base scores for component types
        $this->migrator->add('complexity_scoring.base_scores', [
            'door' => 10,
            'drawer' => 15,
            'shelf_fixed' => 5,
            'shelf_adjustable' => 7,
            'shelf_roll_out' => 13,
            'shelf_pull_down' => 15,
            'pullout' => 20,
        ]);

        // Component weights for averaging
        $this->migrator->add('complexity_scoring.component_weights', [
            'door' => 1.0,
            'drawer' => 1.2,
            'shelf' => 0.5,
            'pullout' => 1.5,
        ]);

        // Modification points for features
        $this->migrator->add('complexity_scoring.modification_points', [
            // Hardware upgrades
            'soft_close' => 3,
            'hinge_euro_concealed' => 2,
            'hinge_specialty' => 4,
            'slide_blum_tandem' => 3,
            'slide_full_extension' => 2,
            'slide_undermount' => 2,

            // Glass and special features
            'has_glass' => 8,
            'glass_mullioned' => 12,
            'glass_leaded' => 15,
            'joinery_dovetail' => 6,
            'joinery_dado' => 2,
            'joinery_finger' => 4,

            // Door/drawer features
            'has_check_rail' => 4,
            'profile_beaded' => 4,
            'profile_raised_panel' => 5,
            'profile_shaker' => 2,
            'profile_slab' => 0,
            'fabrication_five_piece' => 3,

            // Custom dimensions (non-standard sizes)
            'non_standard_width' => 3,
            'non_standard_height' => 3,
            'non_standard_depth' => 2,

            // Shelf-specific
            'shelf_roll_out' => 8,
            'shelf_pull_down' => 10,
            'shelf_corner' => 5,
            'shelf_floating' => 4,

            // Pullout-specific
            'pullout_trash' => 0,
            'pullout_spice_rack' => 3,
            'pullout_lazy_susan' => 8,
            'pullout_mixer_lift' => 10,
            'pullout_blind_corner' => 6,
            'pullout_pantry' => 5,
        ]);

        // Score thresholds for labels (score must be less than threshold)
        $this->migrator->add('complexity_scoring.score_thresholds', [
            'simple' => 10,        // < 10 = Simple
            'standard' => 15,      // 10-14 = Standard
            'moderate' => 20,      // 15-19 = Moderate
            'complex' => 30,       // 20-29 = Complex
            'very_complex' => 40,  // 30-39 = Very Complex
            // >= 40 = Custom
        ]);

        // Standard dimensions (non-standard adds complexity points)
        $this->migrator->add('complexity_scoring.standard_door_widths', [12, 15, 18, 21, 24, 27, 30, 33, 36]);
        $this->migrator->add('complexity_scoring.standard_door_heights', [30, 36, 42]);
        $this->migrator->add('complexity_scoring.standard_drawer_widths', [12, 15, 18, 21, 24, 27, 30, 33, 36]);
    }

    /**
     * Remove all complexity scoring settings.
     */
    public function down(): void
    {
        $this->migrator->delete('complexity_scoring.base_scores');
        $this->migrator->delete('complexity_scoring.component_weights');
        $this->migrator->delete('complexity_scoring.modification_points');
        $this->migrator->delete('complexity_scoring.score_thresholds');
        $this->migrator->delete('complexity_scoring.standard_door_widths');
        $this->migrator->delete('complexity_scoring.standard_door_heights');
        $this->migrator->delete('complexity_scoring.standard_drawer_widths');
    }
};
