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
        Schema::table('projects_project_stages', function (Blueprint $table) {
            $table->unsignedInteger('max_days_in_stage')->nullable()->after('wip_limit')
                ->comment('Maximum days a project can stay in this stage before warning');
            $table->unsignedInteger('expiry_warning_days')->default(3)->after('max_days_in_stage')
                ->comment('Days before max_days to start showing warning');
            $table->text('notice_message')->nullable()->after('expiry_warning_days')
                ->comment('Custom notice/alert message for this stage');
            $table->enum('notice_severity', ['info', 'warning', 'danger'])->default('info')->after('notice_message')
                ->comment('Severity level for the notice display');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_project_stages', function (Blueprint $table) {
            $table->dropColumn([
                'max_days_in_stage',
                'expiry_warning_days',
                'notice_message',
                'notice_severity',
            ]);
        });
    }
};
