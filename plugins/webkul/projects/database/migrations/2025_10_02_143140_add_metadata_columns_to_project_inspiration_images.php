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
        // No migration needed - we'll use the existing metadata JSON column
        // to store structured woodworking metadata
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No migration needed
    }
};
