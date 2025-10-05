<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Keep only the vendor pricing record we manually created for testing (ID 32)
        // Remove any other test or incomplete records
        $deletedCount = DB::table('products_product_suppliers')
            ->where('id', '!=', 32)
            ->delete();

        echo "Cleaned up {$deletedCount} test vendor pricing records\n";
        echo "Kept record ID 32 for reference\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore deleted test records
        echo "Warning: Cannot restore deleted test data\n";
    }
};
