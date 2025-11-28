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
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->string('project_type')->nullable()->after('name')->comment('Project type: Residential, Commercial, Furniture, Millwork, Other');
            $table->text('project_type_other')->nullable()->after('project_type')->comment('Free text for Other project type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn(['project_type', 'project_type_other']);
        });
    }
};
