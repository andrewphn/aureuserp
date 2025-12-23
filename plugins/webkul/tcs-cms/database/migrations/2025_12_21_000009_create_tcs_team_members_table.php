<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcs_team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role')->nullable();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->longText('full_bio')->nullable();
            $table->string('photo')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('social_links')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('years_experience')->nullable();
            $table->date('start_date')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('featured')->default(false);
            $table->integer('position')->default(0);

            // Optional link to ERP employee
            $table->foreignId('employee_id')->nullable()->constrained('employees_employees')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcs_team_members');
    }
};
