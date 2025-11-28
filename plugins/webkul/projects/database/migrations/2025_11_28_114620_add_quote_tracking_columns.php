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
        // Add quote tracking to sales_orders table
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                // Only add if column doesn't exist
                if (!Schema::hasColumn('sales_orders', 'source_quote_id')) {
                    $table->foreignId('source_quote_id')
                        ->nullable()
                        ->after('original_order_id')
                        ->constrained('sales_orders')
                        ->nullOnDelete();
                }
                if (!Schema::hasColumn('sales_orders', 'converted_from_quote_at')) {
                    $table->timestamp('converted_from_quote_at')->nullable()->after('source_quote_id');
                }
            });
        }

        // Add source quote reference to projects_projects table
        Schema::table('projects_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects_projects', 'source_quote_id')) {
                $table->foreignId('source_quote_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('sales_orders')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects_projects', 'source_quote_id')) {
                $table->dropForeign(['source_quote_id']);
                $table->dropColumn('source_quote_id');
            }
        });

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (Schema::hasColumn('sales_orders', 'converted_from_quote_at')) {
                    $table->dropColumn('converted_from_quote_at');
                }
                if (Schema::hasColumn('sales_orders', 'source_quote_id')) {
                    $table->dropForeign(['source_quote_id']);
                    $table->dropColumn('source_quote_id');
                }
            });
        }
    }
};
