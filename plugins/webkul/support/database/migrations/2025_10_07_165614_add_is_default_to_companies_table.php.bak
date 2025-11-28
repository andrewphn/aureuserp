<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds is_default column to allow marking one company as the default for new projects.
     * Only one company should have is_default = true at any time.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_default')
                ->default(false)
                ->after('is_active')
                ->comment('Whether this is the default company for new projects');

            $table->index('is_default', 'idx_companies_default');
        });

        // Set TCS (The Carpenter's Son Woodworking LLC) as default if it exists (ID: 1)
        DB::table('companies')
            ->where('id', 1)
            ->update(['is_default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('idx_companies_default');
            $table->dropColumn('is_default');
        });
    }
};
