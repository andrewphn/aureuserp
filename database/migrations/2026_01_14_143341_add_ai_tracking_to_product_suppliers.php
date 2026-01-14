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
        Schema::table('products_product_suppliers', function (Blueprint $table) {
            $table->boolean('ai_created')->default(false)->after('creator_id');
            $table->string('ai_source_document')->nullable()->after('ai_created');
            $table->timestamp('ai_created_at')->nullable()->after('ai_source_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_product_suppliers', function (Blueprint $table) {
            $table->dropColumn(['ai_created', 'ai_source_document', 'ai_created_at']);
        });
    }
};
