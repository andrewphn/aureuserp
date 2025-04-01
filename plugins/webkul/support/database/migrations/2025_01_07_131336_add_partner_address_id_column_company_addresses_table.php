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
        Schema::table('company_addresses', function (Blueprint $table) {
            $table->foreignId('partner_address_id')
                ->nullable()
                ->constrained('partners_addresses')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_addresses', function (Blueprint $table) {
            $table->dropForeign(['partner_address_id']);

            $table->dropColumn('partner_address_id');
        });
    }
};
