<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Face Frame Style Configuration Fields
     *
     * These fields make face frame style settings (reveals, overlay amounts, gaps)
     * configurable via Construction Templates instead of being hardcoded in
     * CabinetXYZService and CabinetMathAuditService.
     *
     * This allows different templates (TCS Standard, European Frameless, Traditional Inset, etc.)
     * to have their own style-specific values.
     */
    public function up(): void
    {
        Schema::table('projects_construction_templates', function (Blueprint $table) {
            // ========================================
            // FACE FRAME STYLE DEFAULTS
            // ========================================
            $table->string('default_face_frame_style', 50)->default('full_overlay')
                ->after('face_frame_thickness')
                ->comment('Default face frame style: frameless, face_frame, full_overlay, inset, partial_overlay');

            // ========================================
            // FRAMELESS STYLE SETTINGS
            // ========================================
            $table->decimal('frameless_reveal_gap', 8, 4)->default(0.09375)  // 3/32"
                ->after('default_face_frame_style')
                ->comment('Gap between faces in frameless style');
            $table->decimal('frameless_bottom_reveal', 8, 4)->default(0)
                ->after('frameless_reveal_gap')
                ->comment('Bottom reveal for frameless style');

            // ========================================
            // FACE FRAME (TRADITIONAL) STYLE SETTINGS
            // ========================================
            $table->decimal('face_frame_reveal_gap', 8, 4)->default(0.125)  // 1/8"
                ->after('frameless_bottom_reveal')
                ->comment('Reveal gap for traditional face frame style');
            $table->decimal('face_frame_bottom_reveal', 8, 4)->default(0.125)  // 1/8"
                ->after('face_frame_reveal_gap')
                ->comment('Bottom reveal for traditional face frame style');

            // ========================================
            // FULL OVERLAY STYLE SETTINGS (TCS Default)
            // ========================================
            $table->decimal('full_overlay_amount', 8, 4)->default(1.25)  // 1-1/4"
                ->after('face_frame_bottom_reveal')
                ->comment('Overlay amount on stiles for full overlay style');
            $table->decimal('full_overlay_reveal_gap', 8, 4)->default(0.125)  // 1/8"
                ->after('full_overlay_amount')
                ->comment('Gap between faces for full overlay style');
            $table->decimal('full_overlay_bottom_reveal', 8, 4)->default(0)
                ->after('full_overlay_reveal_gap')
                ->comment('Bottom reveal for full overlay style (TCS: 0)');

            // ========================================
            // INSET STYLE SETTINGS
            // ========================================
            $table->decimal('inset_reveal_gap', 8, 4)->default(0.0625)  // 1/16"
                ->after('full_overlay_bottom_reveal')
                ->comment('Reveal gap for inset style (tight fit)');
            $table->decimal('inset_bottom_reveal', 8, 4)->default(0.0625)  // 1/16"
                ->after('inset_reveal_gap')
                ->comment('Bottom reveal for inset style');

            // ========================================
            // PARTIAL OVERLAY STYLE SETTINGS
            // ========================================
            $table->decimal('partial_overlay_amount', 8, 4)->default(0.375)  // 3/8"
                ->after('inset_bottom_reveal')
                ->comment('Overlay amount for partial overlay style');
            $table->decimal('partial_overlay_reveal_gap', 8, 4)->default(0.125)  // 1/8"
                ->after('partial_overlay_amount')
                ->comment('Gap between faces for partial overlay style');
            $table->decimal('partial_overlay_bottom_reveal', 8, 4)->default(0.125)  // 1/8"
                ->after('partial_overlay_reveal_gap')
                ->comment('Bottom reveal for partial overlay style');

            // ========================================
            // ADDITIONAL CLEARANCES (from CabinetMathAuditService)
            // ========================================
            $table->decimal('drawer_cavity_clearance', 8, 4)->default(0.25)  // 1/4"
                ->after('partial_overlay_bottom_reveal')
                ->comment('Clearance beyond slide length for drawer cavity');
            $table->decimal('end_panel_install_overage', 8, 4)->default(0.5)  // 1/2"
                ->after('drawer_cavity_clearance')
                ->comment('Extra on end panels for install adjustment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_construction_templates', function (Blueprint $table) {
            $table->dropColumn([
                'default_face_frame_style',
                'frameless_reveal_gap',
                'frameless_bottom_reveal',
                'face_frame_reveal_gap',
                'face_frame_bottom_reveal',
                'full_overlay_amount',
                'full_overlay_reveal_gap',
                'full_overlay_bottom_reveal',
                'inset_reveal_gap',
                'inset_bottom_reveal',
                'partial_overlay_amount',
                'partial_overlay_reveal_gap',
                'partial_overlay_bottom_reveal',
                'drawer_cavity_clearance',
                'end_panel_install_overage',
            ]);
        });
    }
};
