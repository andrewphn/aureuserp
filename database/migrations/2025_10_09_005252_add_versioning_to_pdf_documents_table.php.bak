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
        Schema::table('pdf_documents', function (Blueprint $table) {
            // Version tracking
            $table->unsignedInteger('version_number')->default(1)->after('page_count');
            $table->unsignedBigInteger('previous_version_id')->nullable()->after('version_number');
            $table->boolean('is_latest_version')->default(true)->after('previous_version_id');

            // Track what changed between versions
            $table->json('version_metadata')->nullable()->after('is_latest_version');

            // Foreign key to link versions
            $table->foreign('previous_version_id')
                  ->references('id')
                  ->on('pdf_documents')
                  ->onDelete('set null');

            // Index for finding latest version
            $table->index(['module_type', 'module_id', 'is_latest_version'], 'latest_version_idx');

            // Index for version chains
            $table->index('previous_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_documents', function (Blueprint $table) {
            $table->dropForeign(['previous_version_id']);
            $table->dropIndex('latest_version_idx');
            $table->dropIndex(['previous_version_id']);

            $table->dropColumn([
                'version_number',
                'previous_version_id',
                'is_latest_version',
                'version_metadata',
            ]);
        });
    }
};
