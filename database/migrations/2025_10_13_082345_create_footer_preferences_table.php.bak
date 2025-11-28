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
        Schema::create('footer_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('context_type'); // 'project', 'sale', 'inventory', 'production'
            $table->json('minimized_fields')->nullable(); // Fields shown when collapsed
            $table->json('expanded_fields')->nullable(); // Fields shown when expanded
            $table->json('field_order')->nullable(); // Custom field ordering
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure one preference per user per context
            $table->unique(['user_id', 'context_type']);

            // Index for faster lookups
            $table->index(['user_id', 'context_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footer_preferences');
    }
};
