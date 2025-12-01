<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Entity Linking to Existing PDF Pages Table
     *
     * This extends the existing pdf_pages table with polymorphic entity linking.
     * Instead of hardcoded fields, pages can now link to ANY entity type.
     *
     * Design Philosophy:
     * - Entity data lives in entity tables (Room, RoomLocation, etc.), NOT here
     * - This table stores REFERENCES to entities, not duplicated data
     * - Flexible enough to link to future entity types without migrations
     * - Maintains backward compatibility with existing page data
     */
    public function up(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            // Project link (for multi-project support)
            $table->foreignId('project_id')
                ->nullable()
                ->after('document_id')
                ->constrained('projects_projects')
                ->nullOnDelete();

            // PRIMARY ENTITY LINK - Polymorphic relationship
            // A page can link to: Project, Room, RoomLocation, CabinetRun, Cabinet, etc.
            $table->string('linked_entity_type')->nullable()->after('page_metadata')
                ->comment('Fully qualified model class: Webkul\Project\Models\Room');
            $table->unsignedBigInteger('linked_entity_id')->nullable()->after('linked_entity_type');
            $table->index(['linked_entity_type', 'linked_entity_id'], 'pdf_pages_entity_index');

            // SECONDARY ENTITY LINKS - For pages showing multiple things
            // Example: Floor plan showing Kitchen AND Living Room
            $table->json('additional_entity_links')->nullable()->after('linked_entity_id')
                ->comment('Array of {type, id} for pages with multiple entities');

            // Processing workflow status
            $table->string('processing_status')->default('pending')->after('additional_entity_links')
                ->comment('pending, classified, entity_linked, reviewed, complete');

            // Classification audit trail
            $table->foreignId('classified_by')->nullable()->after('processing_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('classified_at')->nullable()->after('classified_by');

            // Annotation tracking
            $table->unsignedInteger('annotation_count')->default(0)->after('classified_at');
            $table->timestamp('last_annotated_at')->nullable()->after('annotation_count');

            // Creator tracking
            $table->foreignId('creator_id')->nullable()->after('last_annotated_at')
                ->constrained('users')
                ->nullOnDelete();

            // Indexes for common queries
            $table->index(['project_id', 'page_type']);
            $table->index(['project_id', 'processing_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('pdf_pages_entity_index');
            $table->dropIndex(['project_id', 'page_type']);
            $table->dropIndex(['project_id', 'processing_status']);

            // Drop foreign keys
            $table->dropForeign(['project_id']);
            $table->dropForeign(['classified_by']);
            $table->dropForeign(['creator_id']);

            // Drop columns
            $table->dropColumn([
                'project_id',
                'linked_entity_type',
                'linked_entity_id',
                'additional_entity_links',
                'processing_status',
                'classified_by',
                'classified_at',
                'annotation_count',
                'last_annotated_at',
                'creator_id',
            ]);
        });
    }
};
