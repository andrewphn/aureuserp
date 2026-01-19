<?php

namespace Webkul\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Project\Models\ConstructionTemplate;

/**
 * Construction Template Seeder
 *
 * Seeds the default TCS Standard construction template based on
 * Bryan Patton's documented shop standards (Jan 2025).
 */
class ConstructionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'TCS Standard',
                'description' => 'TCS Woodwork standard construction specifications. Based on Bryan Patton\'s documented shop standards (Jan 2025).',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                // Cabinet Heights (inches)
                'base_cabinet_height' => 34.75,      // 34 3/4"
                'wall_cabinet_30_height' => 30.0,
                'wall_cabinet_36_height' => 36.0,
                'wall_cabinet_42_height' => 42.0,
                'tall_cabinet_84_height' => 84.0,
                'tall_cabinet_96_height' => 96.0,
                // Toe Kick
                'toe_kick_height' => 4.5,            // 4 1/2"
                'toe_kick_recess' => 3.0,            // 3"
                // Stretchers
                'stretcher_depth' => 3.0,            // 3"
                'stretcher_thickness' => 0.75,       // 3/4"
                'stretcher_min_depth' => 2.5,
                'stretcher_max_depth' => 4.0,
                // Face Frame
                'face_frame_stile_width' => 1.5,     // 1 1/2"
                'face_frame_rail_width' => 1.5,      // 1 1/2"
                'face_frame_door_gap' => 0.125,      // 1/8"
                'face_frame_thickness' => 0.75,      // 3/4"
                // Face Frame Style Settings (TCS uses Full Overlay)
                'default_face_frame_style' => 'full_overlay',
                'frameless_reveal_gap' => 0.09375,       // 3/32"
                'frameless_bottom_reveal' => 0,
                'face_frame_reveal_gap' => 0.125,        // 1/8"
                'face_frame_bottom_reveal' => 0.125,
                'full_overlay_amount' => 1.25,           // 1-1/4" (TCS standard)
                'full_overlay_reveal_gap' => 0.125,      // 1/8"
                'full_overlay_bottom_reveal' => 0,       // No bottom reveal (TCS)
                'inset_reveal_gap' => 0.0625,            // 1/16"
                'inset_bottom_reveal' => 0.0625,
                'partial_overlay_amount' => 0.375,       // 3/8"
                'partial_overlay_reveal_gap' => 0.125,
                'partial_overlay_bottom_reveal' => 0.125,
                'drawer_cavity_clearance' => 0.25,       // 1/4"
                'end_panel_install_overage' => 0.5,      // 1/2"
                // Material Thickness Overrides (used when product has no thickness attribute)
                'box_material_thickness' => 0.75,    // 3/4"
                'back_panel_thickness' => 0.75,      // 3/4" full backs (TCS standard)
                'side_panel_thickness' => 0.75,      // 3/4"
                // Sink Cabinet
                'sink_side_extension' => 0.75,       // 3/4"
                // Section Ratios
                'drawer_bank_ratio' => 0.40,         // 40%
                'door_section_ratio' => 0.60,        // 60%
                'equal_section_ratio' => 0.50,       // 50%
                // Countertop
                'countertop_thickness' => 1.25,      // 1 1/4"
                'finished_counter_height' => 36.0,   // 36"
            ],
            [
                'name' => 'European Frameless',
                'description' => 'European-style frameless cabinet construction with 32mm system compatibility.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
                // Cabinet Heights (inches)
                'base_cabinet_height' => 34.5,
                'wall_cabinet_30_height' => 30.0,
                'wall_cabinet_36_height' => 36.0,
                'wall_cabinet_42_height' => 42.0,
                'tall_cabinet_84_height' => 84.0,
                'tall_cabinet_96_height' => 96.0,
                // Toe Kick (shorter for frameless)
                'toe_kick_height' => 4.0,
                'toe_kick_recess' => 2.5,
                // Stretchers (not typically used in frameless)
                'stretcher_depth' => 0.0,
                'stretcher_thickness' => 0.0,
                'stretcher_min_depth' => 0.0,
                'stretcher_max_depth' => 0.0,
                // Face Frame (not used in frameless - set to 0)
                'face_frame_stile_width' => 0.0,
                'face_frame_rail_width' => 0.0,
                'face_frame_door_gap' => 0.0625,     // 1/16" for overlay
                'face_frame_thickness' => 0.0,
                // Face Frame Style Settings (European Frameless)
                'default_face_frame_style' => 'frameless',
                'frameless_reveal_gap' => 0.09375,       // 3/32" (European standard)
                'frameless_bottom_reveal' => 0,
                'face_frame_reveal_gap' => 0.125,
                'face_frame_bottom_reveal' => 0.125,
                'full_overlay_amount' => 1.25,
                'full_overlay_reveal_gap' => 0.125,
                'full_overlay_bottom_reveal' => 0,
                'inset_reveal_gap' => 0.0625,
                'inset_bottom_reveal' => 0.0625,
                'partial_overlay_amount' => 0.375,
                'partial_overlay_reveal_gap' => 0.125,
                'partial_overlay_bottom_reveal' => 0.125,
                'drawer_cavity_clearance' => 0.25,
                'end_panel_install_overage' => 0.5,
                // Material Thickness
                'box_material_thickness' => 0.75,
                'back_panel_thickness' => 0.25,      // 1/4" backs common in frameless
                'side_panel_thickness' => 0.75,
                // Sink Cabinet
                'sink_side_extension' => 0.0,
                // Section Ratios
                'drawer_bank_ratio' => 0.50,
                'door_section_ratio' => 0.50,
                'equal_section_ratio' => 0.50,
                // Countertop
                'countertop_thickness' => 1.5,       // Thicker countertop common
                'finished_counter_height' => 36.0,
            ],
            [
                'name' => 'Traditional Inset',
                'description' => 'Traditional inset cabinet construction with flush doors and drawers set within face frame.',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
                // Cabinet Heights
                'base_cabinet_height' => 34.75,
                'wall_cabinet_30_height' => 30.0,
                'wall_cabinet_36_height' => 36.0,
                'wall_cabinet_42_height' => 42.0,
                'tall_cabinet_84_height' => 84.0,
                'tall_cabinet_96_height' => 96.0,
                // Toe Kick
                'toe_kick_height' => 4.5,
                'toe_kick_recess' => 3.0,
                // Stretchers
                'stretcher_depth' => 3.0,
                'stretcher_thickness' => 0.75,
                'stretcher_min_depth' => 2.5,
                'stretcher_max_depth' => 4.0,
                // Face Frame (wider for inset)
                'face_frame_stile_width' => 2.0,     // 2" stiles for inset
                'face_frame_rail_width' => 2.0,     // 2" rails for inset
                'face_frame_door_gap' => 0.0625,    // 1/16" tight gap for inset
                'face_frame_thickness' => 0.75,
                // Face Frame Style Settings (Traditional Inset)
                'default_face_frame_style' => 'inset',
                'frameless_reveal_gap' => 0.09375,
                'frameless_bottom_reveal' => 0,
                'face_frame_reveal_gap' => 0.125,
                'face_frame_bottom_reveal' => 0.125,
                'full_overlay_amount' => 1.25,
                'full_overlay_reveal_gap' => 0.125,
                'full_overlay_bottom_reveal' => 0,
                'inset_reveal_gap' => 0.0625,            // 1/16" (tight fit for inset)
                'inset_bottom_reveal' => 0.0625,         // 1/16"
                'partial_overlay_amount' => 0.375,
                'partial_overlay_reveal_gap' => 0.125,
                'partial_overlay_bottom_reveal' => 0.125,
                'drawer_cavity_clearance' => 0.25,
                'end_panel_install_overage' => 0.5,
                // Material Thickness
                'box_material_thickness' => 0.75,
                'back_panel_thickness' => 0.75,
                'side_panel_thickness' => 0.75,
                // Sink Cabinet
                'sink_side_extension' => 0.75,
                // Section Ratios
                'drawer_bank_ratio' => 0.40,
                'door_section_ratio' => 0.60,
                'equal_section_ratio' => 0.50,
                // Countertop
                'countertop_thickness' => 1.25,
                'finished_counter_height' => 36.0,
            ],
        ];

        foreach ($templates as $template) {
            ConstructionTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
