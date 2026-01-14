<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds AI tracking columns to inventories_operations and inventories_moves tables
     * for document scanning confidence tracking and audit purposes.
     */
    public function up(): void
    {
        // Add AI tracking columns to inventories_operations
        Schema::table('inventories_operations', function (Blueprint $table) {
            // Packing slip and shipping info from scanned documents
            if (!Schema::hasColumn('inventories_operations', 'packing_slip_number')) {
                $table->string('packing_slip_number')->nullable()->after('origin');
            }
            if (!Schema::hasColumn('inventories_operations', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('packing_slip_number');
            }
            if (!Schema::hasColumn('inventories_operations', 'carrier_id')) {
                $table->foreignId('carrier_id')
                    ->nullable()
                    ->after('tracking_number')
                    ->constrained('partners_partners')
                    ->nullOnDelete();
            }

            // AI confidence and audit fields
            if (!Schema::hasColumn('inventories_operations', 'ai_scan_confidence')) {
                $table->decimal('ai_scan_confidence', 3, 2)->nullable()->after('carrier_id');
            }
            if (!Schema::hasColumn('inventories_operations', 'ai_populated_at')) {
                $table->datetime('ai_populated_at')->nullable()->after('ai_scan_confidence');
            }
        });

        // Add AI tracking columns to inventories_moves
        Schema::table('inventories_moves', function (Blueprint $table) {
            // AI confidence for individual line items
            if (!Schema::hasColumn('inventories_moves', 'ai_confidence')) {
                $table->decimal('ai_confidence', 3, 2)->nullable()->after('is_refund');
            }
            // The vendor SKU extracted from the scanned document
            if (!Schema::hasColumn('inventories_moves', 'ai_source_sku')) {
                $table->string('ai_source_sku')->nullable()->after('ai_confidence');
            }
            // How the product was matched: vendor_sku, internal_sku, description, barcode
            if (!Schema::hasColumn('inventories_moves', 'ai_matched_by')) {
                $table->string('ai_matched_by')->nullable()->after('ai_source_sku');
            }
            // Flag for items that need manual review (low confidence)
            if (!Schema::hasColumn('inventories_moves', 'requires_review')) {
                $table->boolean('requires_review')->default(false)->after('ai_matched_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories_operations', function (Blueprint $table) {
            if (Schema::hasColumn('inventories_operations', 'carrier_id')) {
                $table->dropForeign(['carrier_id']);
            }
            $columns = ['packing_slip_number', 'tracking_number', 'carrier_id', 'ai_scan_confidence', 'ai_populated_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('inventories_operations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('inventories_moves', function (Blueprint $table) {
            $columns = ['ai_confidence', 'ai_source_sku', 'ai_matched_by', 'requires_review'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('inventories_moves', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
