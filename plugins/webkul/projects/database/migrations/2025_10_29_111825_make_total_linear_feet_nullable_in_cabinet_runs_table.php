<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->decimal('total_linear_feet', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->decimal('total_linear_feet', 10, 2)->nullable(false)->change();
        });
    }
};
