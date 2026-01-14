<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Change order lines track individual field changes within a change order.
     * This provides a before/after audit trail for every modification.
     */
    public function up(): void
    {
        Schema::create('projects_change_order_lines', function (Blueprint $table) {
            $table->id();
            
            // Parent change order
            $table->foreignId('change_order_id')
                ->constrained('projects_change_orders')
                ->cascadeOnDelete();
            
            // Entity identification
            $table->string('entity_type', 100);     // 'Cabinet', 'Drawer', 'Door', 'BomLine'
            $table->unsignedBigInteger('entity_id');
            $table->string('field_name', 100);      // 'width_inches', 'material', 'quantity'
            
            // Before/after values
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            
            // Impact tracking
            $table->decimal('price_impact', 10, 2)->default(0);
            $table->json('bom_impact_json')->nullable();    // Material additions/removals
            
            // Application status
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('change_order_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('is_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_change_order_lines');
    }
};
