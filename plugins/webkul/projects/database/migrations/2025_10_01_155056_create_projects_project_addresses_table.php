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
        Schema::create('projects_project_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_projects')->cascadeOnDelete();
            $table->string('type')->default('project'); // 'project', 'billing', 'shipping', etc.
            $table->string('street1')->nullable();
            $table->string('street2')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states');
            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_project_addresses');
    }
};
