<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees_calendars', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        // Set TCS Newburgh Shop as default (id=37)
        DB::table('employees_calendars')
            ->where('id', 37)
            ->update(['is_default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees_calendars', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
