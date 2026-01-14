<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add opening depth to cabinet sections.
     * This allows calculating drawer box depth from the section opening.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->decimal('opening_depth_inches', 8, 3)
                ->nullable()
                ->after('opening_height_inches')
                ->comment('Depth of the opening for drawer/component calculations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropColumn('opening_depth_inches');
        });
    }
};
