<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds department-specific production rates to companies table.
     * These rates define how many linear feet per day each department can process.
     * Used for automatic duration calculation in milestone/task templates.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Design Department Rates (LF per day)
            if (!Schema::hasColumn('companies', 'design_concepts_lf_per_day')) {
                $table->decimal('design_concepts_lf_per_day', 8, 2)->default(15)
                    ->after('draft_number_start')
                    ->comment('Initial design concepts: LF that can be designed per day (default: 15 LF/day = 1 day per 15 LF)');
            }

            if (!Schema::hasColumn('companies', 'design_revisions_lf_per_day')) {
                $table->decimal('design_revisions_lf_per_day', 8, 2)->default(50)
                    ->after('design_concepts_lf_per_day')
                    ->comment('Design revisions: LF of changes per day (default: 50 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'shop_drawings_lf_per_day')) {
                $table->decimal('shop_drawings_lf_per_day', 8, 2)->default(100)
                    ->after('design_revisions_lf_per_day')
                    ->comment('Shop drawings: LF that can be drawn per day (default: 100 LF/day)');
            }

            // Sourcing/Engineering Department Rates
            if (!Schema::hasColumn('companies', 'cut_list_bom_lf_per_day')) {
                $table->decimal('cut_list_bom_lf_per_day', 8, 2)->default(100)
                    ->after('shop_drawings_lf_per_day')
                    ->comment('Cut list & BOM creation: LF per day (default: 100 LF/day)');
            }

            // Production Department Rates
            if (!Schema::hasColumn('companies', 'rough_mill_lf_per_day')) {
                $table->decimal('rough_mill_lf_per_day', 8, 2)->default(50)
                    ->after('cut_list_bom_lf_per_day')
                    ->comment('Rough milling: LF per day (default: 50 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'cabinet_assembly_lf_per_day')) {
                $table->decimal('cabinet_assembly_lf_per_day', 8, 2)->default(25)
                    ->after('rough_mill_lf_per_day')
                    ->comment('Cabinet assembly: LF per day (default: 25 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'doors_drawers_lf_per_day')) {
                $table->decimal('doors_drawers_lf_per_day', 8, 2)->default(30)
                    ->after('cabinet_assembly_lf_per_day')
                    ->comment('Doors & drawer fronts: LF per day (default: 30 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'sanding_prep_lf_per_day')) {
                $table->decimal('sanding_prep_lf_per_day', 8, 2)->default(75)
                    ->after('doors_drawers_lf_per_day')
                    ->comment('Sanding & prep: LF per day (default: 75 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'finishing_lf_per_day')) {
                $table->decimal('finishing_lf_per_day', 8, 2)->default(50)
                    ->after('sanding_prep_lf_per_day')
                    ->comment('Finishing: LF per day (default: 50 LF/day)');
            }

            if (!Schema::hasColumn('companies', 'hardware_install_lf_per_day')) {
                $table->decimal('hardware_install_lf_per_day', 8, 2)->default(100)
                    ->after('finishing_lf_per_day')
                    ->comment('Hardware installation: LF per day (default: 100 LF/day)');
            }

            // Delivery/Installation Rate
            if (!Schema::hasColumn('companies', 'installation_lf_per_day')) {
                $table->decimal('installation_lf_per_day', 8, 2)->default(40)
                    ->after('hardware_install_lf_per_day')
                    ->comment('Installation: LF per day (default: 40 LF/day)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = [
                'design_concepts_lf_per_day',
                'design_revisions_lf_per_day',
                'shop_drawings_lf_per_day',
                'cut_list_bom_lf_per_day',
                'rough_mill_lf_per_day',
                'cabinet_assembly_lf_per_day',
                'doors_drawers_lf_per_day',
                'sanding_prep_lf_per_day',
                'finishing_lf_per_day',
                'hardware_install_lf_per_day',
                'installation_lf_per_day',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
