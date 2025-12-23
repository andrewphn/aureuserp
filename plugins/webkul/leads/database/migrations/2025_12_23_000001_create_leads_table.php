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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Contact Information
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();

            // Lead Management
            $table->string('status')->default('new')->index(); // new, contacted, qualified, disqualified, converted
            $table->string('source')->nullable()->index(); // website, referral, walk-in, etc.
            $table->text('message')->nullable();

            // Classification
            $table->string('lead_source_detail')->nullable();
            $table->string('market_segment')->nullable();
            $table->string('primary_interest')->nullable();
            $table->string('lead_type')->nullable();
            $table->string('preferred_contact_method')->nullable();

            // Project Details
            $table->string('project_type')->nullable();
            $table->string('budget_range')->nullable();
            $table->string('timeline')->nullable();
            $table->text('project_description')->nullable();
            $table->string('design_style')->nullable();
            $table->string('wood_species')->nullable();

            // JSON Data Storage
            $table->json('form_data')->nullable(); // Complete raw form submission
            $table->json('questionnaire_data')->nullable(); // Follow-up questionnaire responses
            $table->json('ai_analysis_results')->nullable(); // AI image/text analysis

            // CRM Integration
            $table->string('hubspot_contact_id')->nullable()->index();
            $table->string('hubspot_deal_id')->nullable()->index();

            // Assignment & Conversion
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('partner_id')
                ->nullable()
                ->constrained('partners_partners')
                ->nullOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects_projects')
                ->nullOnDelete();

            $table->timestamp('converted_at')->nullable();
            $table->string('disqualification_reason')->nullable();

            // Address (optional)
            $table->string('street1')->nullable();
            $table->string('street2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('country')->nullable();

            // Consent fields
            $table->boolean('processing_consent')->default(false);
            $table->boolean('communication_consent')->default(false);

            // Creator tracking
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            // Timestamps
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
