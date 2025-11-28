<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Renames cabinet_materials_bom to projects_bom for consistency with AureusERP naming conventions.
     */
    public function up(): void
    {
        Schema::rename('cabinet_materials_bom', 'projects_bom');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('projects_bom', 'cabinet_materials_bom');
    }
};
