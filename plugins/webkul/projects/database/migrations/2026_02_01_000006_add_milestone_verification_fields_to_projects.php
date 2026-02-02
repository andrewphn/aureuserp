<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add fields needed for milestone requirement verification.
     */
    public function up(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            // Discovery stage
            if (!Schema::hasColumn('projects_projects', 'site_address')) {
                $table->text('site_address')->nullable()->after('use_customer_address');
            }

            // Production stage
            if (!Schema::hasColumn('projects_projects', 'qc_passed_at')) {
                $table->timestamp('qc_passed_at')->nullable()->after('all_materials_received_at');
            }
            if (!Schema::hasColumn('projects_projects', 'qc_passed_by')) {
                $table->foreignId('qc_passed_by')->nullable()->after('qc_passed_at')
                    ->constrained('users')->nullOnDelete();
            }

            // Delivery stage
            if (!Schema::hasColumn('projects_projects', 'scheduled_delivery_date')) {
                $table->date('scheduled_delivery_date')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('projects_projects', 'installation_scheduled_at')) {
                $table->timestamp('installation_scheduled_at')->nullable()->after('scheduled_delivery_date');
            }
            if (!Schema::hasColumn('projects_projects', 'client_signoff_at')) {
                $table->timestamp('client_signoff_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('projects_projects', 'client_signoff_by')) {
                $table->foreignId('client_signoff_by')->nullable()->after('client_signoff_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn([
                'site_address',
                'qc_passed_at',
                'qc_passed_by',
                'scheduled_delivery_date',
                'installation_scheduled_at',
                'client_signoff_at',
                'client_signoff_by',
            ]);
        });
    }
};
