<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates rooms table to organize project spaces from architectural PDFs.
     * A project can have multiple rooms, and multiple rooms can be on the same PDF page.
     *
     * Workflow:
     * 1. Review architectural PDF
     * 2. Create room for each space (Kitchen, Bath, etc.)
     * 3. Track which PDF page(s) show each room
     * 4. Add room locations (walls, islands) within each room
     * 5. Create cabinet runs for each location
     */
    public function up(): void
    {
        Schema::create('projects_rooms', function (Blueprint $table) {
            $table->id();

            // Project relationship
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->onDelete('cascade')
                ->comment('Parent project');

            // Room identification
            $table->string('name')
                ->comment('Room name (e.g., "Kitchen", "Master Bathroom")');

            $table->string('room_type')
                ->nullable()
                ->comment('Type: kitchen, bathroom, laundry, office, etc.');

            $table->string('floor_number')
                ->nullable()
                ->comment('Floor location: 1, 2, basement, etc.');

            // PDF Reference (supports multiple rooms per page)
            $table->integer('pdf_page_number')
                ->nullable()
                ->comment('PDF page number where this room appears');

            $table->string('pdf_room_label')
                ->nullable()
                ->comment('Label on PDF (e.g., "Kitchen", "Detail A", "Plan View")');

            $table->string('pdf_detail_number')
                ->nullable()
                ->comment('Architect callout number (e.g., "A-3.1", "K-1")');

            $table->text('pdf_notes')
                ->nullable()
                ->comment('Notes to help locate room on PDF page');

            // General info
            $table->text('notes')
                ->nullable()
                ->comment('General room notes and specifications');

            $table->integer('sort_order')
                ->default(0)
                ->comment('Display order in lists');

            // Metadata
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('project_id');
            $table->index('pdf_page_number');
            $table->index(['project_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_rooms');
    }
};
