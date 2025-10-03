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
            $table->boolean('use_customer_address')->default(true)->after('partner_id')->comment('Whether to use customer address for project address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn('use_customer_address');
        });
    }
};
