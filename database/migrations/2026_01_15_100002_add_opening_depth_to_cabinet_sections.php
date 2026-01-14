<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds opening_depth_inches to cabinet sections for drawer dimension calculations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('projects_cabinet_sections', 'opening_depth_inches')) {
            return;
        }

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
        if (!Schema::hasColumn('projects_cabinet_sections', 'opening_depth_inches')) {
            return;
        }

        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropColumn('opening_depth_inches');
        });
    }
};
