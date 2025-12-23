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
        Schema::table('leads', function (Blueprint $table) {
            // Additional source details
            $table->string('referral_source_other')->nullable()->after('source');

            // Project phase and additional details
            $table->string('project_phase')->nullable()->after('project_type');
            $table->string('design_style_other')->nullable()->after('design_style');
            $table->json('finish_choices')->nullable()->after('design_style_other');

            // Timeline completion
            $table->date('timeline_start_date')->nullable()->after('timeline');
            $table->date('timeline_completion_date')->nullable()->after('timeline_start_date');

            // Additional information
            $table->text('additional_information')->nullable()->after('project_description');

            // Address notes
            $table->text('project_address_notes')->nullable()->after('country');

            // File attachments (store as JSON array of paths)
            $table->json('inspiration_images')->nullable()->after('ai_analysis_results');
            $table->json('technical_drawings')->nullable()->after('inspiration_images');
            $table->json('project_documents')->nullable()->after('technical_drawings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'referral_source_other',
                'project_phase',
                'design_style_other',
                'finish_choices',
                'timeline_start_date',
                'timeline_completion_date',
                'additional_information',
                'project_address_notes',
                'inspiration_images',
                'technical_drawings',
                'project_documents',
            ]);
        });
    }
};
