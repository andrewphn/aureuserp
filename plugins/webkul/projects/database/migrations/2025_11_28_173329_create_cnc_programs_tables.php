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
        // CNC Programs table - stores VCarve project files
        Schema::create('projects_cnc_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('vcarve_file')->nullable()->comment('VCarve .crv file name');
            $table->string('material_code')->nullable()->comment('Material code: FL, PreFin, RiftWOPly, MDF_RiftWO, Medex');
            $table->string('material_type')->nullable();
            $table->string('sheet_size')->default('4x8')->comment('Sheet dimensions');
            $table->integer('sheet_count')->default(1);
            $table->date('created_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'complete', 'error'])->default('pending');
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'material_code']);
        });

        // CNC Program Parts table - stores individual G-code files
        Schema::create('projects_cnc_program_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cnc_program_id')->constrained('projects_cnc_programs')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->integer('sheet_number')->nullable()->comment('Sheet number in the nesting');
            $table->string('operation_type')->nullable()->comment('profile, drilling, pocket, groove');
            $table->string('tool')->nullable()->comment('Tool used for operation');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->enum('status', ['pending', 'running', 'complete', 'error'])->default('pending');
            $table->timestamp('run_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['cnc_program_id', 'status']);
            $table->index('sheet_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_cnc_program_parts');
        Schema::dropIfExists('projects_cnc_programs');
    }
};
