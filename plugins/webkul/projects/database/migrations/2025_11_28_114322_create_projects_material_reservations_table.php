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
        Schema::create('projects_material_reservations', function (Blueprint $table) {
            $table->id();

            // Project & BOM references
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            $table->foreignId('bom_id')
                ->nullable()
                ->constrained('projects_bom')
                ->nullOnDelete();

            // Product & Warehouse references
            $table->foreignId('product_id')
                ->constrained('products_products')
                ->cascadeOnDelete();
            $table->foreignId('warehouse_id')
                ->constrained('inventories_warehouses')
                ->cascadeOnDelete();
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('inventories_locations')
                ->nullOnDelete();

            // Quantity & Unit
            $table->decimal('quantity_reserved', 10, 3);
            $table->string('unit_of_measure', 20)->default('unit');

            // Status
            $table->enum('status', ['pending', 'reserved', 'issued', 'cancelled'])
                ->default('pending');

            // Audit & Tracking
            $table->foreignId('reserved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Move reference (when issued)
            $table->foreignId('move_id')
                ->nullable()
                ->constrained('inventories_moves')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['project_id', 'status'], 'mat_res_project_status_idx');
            $table->index(['product_id', 'warehouse_id', 'status'], 'mat_res_product_wh_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_material_reservations');
    }
};
