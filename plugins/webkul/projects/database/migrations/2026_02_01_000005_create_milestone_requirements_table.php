<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Milestone requirements define what must be verified/confirmed
     * before a milestone can be completed.
     *
     * Types:
     * - field_check: Verify a project/cabinet field has a value
     * - document_upload: Require specific document to be uploaded
     * - checklist_item: Simple manual checkbox verification
     * - relation_exists: Verify related records exist (e.g., has hardware selections)
     * - approval_required: Require sign-off from specific role
     */
    public function up(): void
    {
        // Requirements for milestone templates (reusable definitions)
        Schema::create('projects_milestone_requirement_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('milestone_template_id');
            $table->string('name'); // e.g., "Hardware selections confirmed"
            $table->string('requirement_type'); // field_check, document_upload, checklist_item, relation_exists, approval_required
            $table->text('description')->nullable(); // Help text
            $table->json('config')->nullable(); // Type-specific config (field name, document type, etc.)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true); // Must pass to complete milestone
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('milestone_template_id', 'ms_req_tpl_template_fk')
                ->references('id')->on('projects_milestone_templates')->cascadeOnDelete();
            $table->index(['milestone_template_id', 'is_active'], 'ms_req_tpl_active_idx');
        });

        // Actual requirements for project milestones (instances)
        Schema::create('projects_milestone_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('milestone_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('name');
            $table->string('requirement_type');
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);

            // Completion tracking
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamps();

            $table->foreign('milestone_id', 'ms_req_milestone_fk')
                ->references('id')->on('projects_milestones')->cascadeOnDelete();
            $table->foreign('template_id', 'ms_req_template_fk')
                ->references('id')->on('projects_milestone_requirement_templates')->nullOnDelete();
            $table->foreign('verified_by', 'ms_req_verified_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->index(['milestone_id', 'is_verified'], 'ms_req_verified_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_milestone_requirements');
        Schema::dropIfExists('projects_milestone_requirement_templates');
    }
};
