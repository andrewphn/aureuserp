<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->string('google_drive_root_folder_id', 255)->nullable()->after('is_converted');
            $table->string('google_drive_folder_url', 500)->nullable()->after('google_drive_root_folder_id');
            $table->timestamp('google_drive_synced_at')->nullable()->after('google_drive_folder_url');
            $table->boolean('google_drive_enabled')->default(true)->after('google_drive_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn([
                'google_drive_root_folder_id',
                'google_drive_folder_url',
                'google_drive_synced_at',
                'google_drive_enabled',
            ]);
        });
    }
};
