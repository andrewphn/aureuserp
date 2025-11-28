<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Renames cabinet_materials_bom to projects_bom for consistency with AureusERP naming conventions.
     */
    public function up(): void
    {
        // Skip if projects_bom already exists (created by newer migration)
        if (Schema::hasTable('projects_bom')) {
            return;
        }

        // Only rename if the old table exists
        if (Schema::hasTable('cabinet_materials_bom')) {
            Schema::rename('cabinet_materials_bom', 'projects_bom');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('projects_bom', 'cabinet_materials_bom');
    }
};
