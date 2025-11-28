<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Location-level woodworking details (Client approval view)
     * Example locations: "Sink Wall", "Island", "Pantry Wall", "Fridge Wall"
     * This is the design granularity level shown in elevation drawings (PDF pages 4-7).
     */
    public function up(): void
    {
        Schema::table('projects_room_locations', function (Blueprint $table) {
            // Material Specifications (drives pricing + material_upgrade_rate)
            $table->string('material_type', 50)->nullable()
                ->comment('Primary material: paint_grade, stain_grade, premium');
            $table->string('wood_species', 100)->nullable()
                ->comment('Specific wood: hard_maple, poplar, white_oak, walnut, etc.');
            $table->string('door_style', 100)->nullable()
                ->comment('Door style: flat_panel, shaker, beaded, reeded, custom');
            $table->string('finish_type', 100)->nullable()
                ->comment('Finish: prime_only, painted, stained, clear_coat, color_match');
            $table->string('paint_color', 100)->nullable()
                ->comment('Paint color if applicable (e.g., "BM Simply White")');
            $table->string('stain_color', 100)->nullable()
                ->comment('Stain color if applicable (e.g., "Natural", "Espresso")');

            // Dimensional Constraints (from elevation drawings)
            $table->decimal('overall_width_inches', 8, 3)->nullable()
                ->comment('Total width of this location in inches (1/16" precision)');
            $table->decimal('overall_height_inches', 8, 3)->nullable()
                ->comment('Height in inches (e.g., 36" base, 42" upper)');
            $table->decimal('overall_depth_inches', 8, 3)->nullable()
                ->comment('Depth in inches (e.g., 24" base, 12" upper)');
            $table->decimal('soffit_height_inches', 8, 3)->nullable()
                ->comment('Ceiling/soffit height if applicable');
            $table->decimal('toe_kick_height_inches', 8, 3)->nullable()
                ->comment('Toe kick height (typically 4.5")');

            // Cabinet Grouping Info (for this location)
            $table->integer('cabinet_count')->default(0)
                ->comment('Number of cabinet units in this location');
            $table->decimal('total_linear_feet', 8, 2)->nullable()
                ->comment('Sum of all cabinet widths in this location');
            $table->string('cabinet_type_primary', 50)->nullable()
                ->comment('Primary cabinet type: base, upper, tall, island');

            // Construction Details
            $table->boolean('has_face_frame')->default(true)
                ->comment('Face frame construction (TCS standard) vs frameless');
            $table->decimal('face_frame_width_inches', 5, 3)->nullable()
                ->comment('Face frame rail/stile width (typically 1.5")');
            $table->boolean('has_beaded_face_frame')->default(false)
                ->comment('Beaded inset face frame (affects tier pricing)');
            $table->boolean('inset_doors')->default(false)
                ->comment('Inset vs overlay door mounting');
            $table->string('overlay_type', 50)->nullable()
                ->comment('If overlay: full_overlay, partial_overlay');

            // Hardware Standards for This Location
            $table->string('hinge_type', 100)->nullable()
                ->comment('Standard hinge: blum_71b, blum_110, euro_concealed');
            $table->string('slide_type', 100)->nullable()
                ->comment('Drawer slide: blum_tandem, blum_undermount, soft_close');
            $table->boolean('soft_close_doors')->default(true)
                ->comment('Soft close hinges standard');
            $table->boolean('soft_close_drawers')->default(true)
                ->comment('Soft close drawer slides standard');

            // Special Features (affects tier and pricing)
            $table->boolean('has_crown_molding')->default(false)
                ->comment('Crown molding at top');
            $table->boolean('has_light_rail')->default(false)
                ->comment('Light rail molding under upper cabinets');
            $table->boolean('has_decorative_posts')->default(false)
                ->comment('Decorative end posts or pillars');
            $table->boolean('has_appliance_panels')->default(false)
                ->comment('Appliance panel integration');
            $table->text('special_features_json')->nullable()
                ->comment('JSON: corner solutions, panel details, custom features');

            // Countertop Integration
            $table->string('countertop_type', 100)->nullable()
                ->comment('Countertop material if TCS is providing');
            $table->decimal('countertop_sqft', 8, 2)->nullable()
                ->comment('Square footage for this location');
            $table->decimal('backsplash_sqft', 8, 2)->nullable()
                ->comment('Backsplash area if applicable');
            $table->text('countertop_notes')->nullable()
                ->comment('Edge profile, cutouts, special requirements');

            // Electrical/Plumbing Considerations
            $table->boolean('requires_electrical')->default(false)
                ->comment('Under-cabinet lighting, outlets');
            $table->boolean('requires_plumbing')->default(false)
                ->comment('Sink cutouts, dishwasher panels');
            $table->text('electrical_notes')->nullable()
                ->comment('Lighting plans, outlet locations');
            $table->text('plumbing_notes')->nullable()
                ->comment('Sink specifications, appliance cutouts');

            // Client Approval & Design
            $table->string('approval_status', 50)->default('pending')
                ->comment('pending, approved, revision_requested');
            $table->timestamp('approved_at')->nullable()
                ->comment('When client approved this design');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')
                ->comment('User who approved (typically designer or Bryan)');
            $table->text('design_notes')->nullable()
                ->comment('Client preferences, design intent, special requests');
            $table->text('revision_notes')->nullable()
                ->comment('Client change requests');

            // Pricing at Location Level
            $table->decimal('estimated_value', 10, 2)->nullable()
                ->comment('Calculated estimate for this location');
            $table->integer('complexity_tier')->nullable()
                ->comment('1-5 tier override for this location');

            // Reference to Elevation Drawing
            $table->string('elevation_view', 50)->nullable()
                ->comment('View type: front, side, corner, island');
            $table->string('pdf_page_reference', 100)->nullable()
                ->comment('PDF page(s) where this location appears (e.g., "Page 4")');

            // Indexes for common queries
            $table->index(['approval_status', 'approved_at'], 'idx_location_approval');
            $table->index(['material_type', 'wood_species'], 'idx_location_materials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->dropIndex('idx_location_approval');
            $table->dropIndex('idx_location_materials');

            $table->dropForeign(['approved_by_user_id']);

            $table->dropColumn([
                'material_type',
                'wood_species',
                'door_style',
                'finish_type',
                'paint_color',
                'stain_color',
                'overall_width_inches',
                'overall_height_inches',
                'overall_depth_inches',
                'soffit_height_inches',
                'toe_kick_height_inches',
                'cabinet_count',
                'total_linear_feet',
                'cabinet_type_primary',
                'has_face_frame',
                'face_frame_width_inches',
                'has_beaded_face_frame',
                'inset_doors',
                'overlay_type',
                'hinge_type',
                'slide_type',
                'soft_close_doors',
                'soft_close_drawers',
                'has_crown_molding',
                'has_light_rail',
                'has_decorative_posts',
                'has_appliance_panels',
                'special_features_json',
                'countertop_type',
                'countertop_sqft',
                'backsplash_sqft',
                'countertop_notes',
                'requires_electrical',
                'requires_plumbing',
                'electrical_notes',
                'plumbing_notes',
                'approval_status',
                'approved_at',
                'approved_by_user_id',
                'design_notes',
                'revision_notes',
                'estimated_value',
                'complexity_tier',
                'elevation_view',
                'pdf_page_reference',
            ]);
        });
    }
};
