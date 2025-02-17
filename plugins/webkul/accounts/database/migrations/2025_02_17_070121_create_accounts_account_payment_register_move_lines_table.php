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
        Schema::create('accounts_account_payment_register_move_lines', function (Blueprint $table) {
            $table->foreignId('payment_register_id')
                ->comment('Account Payment Register Id')
                ->constrained('accounts_payment_registers')
                ->cascadeOnDelete()
                ->name('fk_payment_register');

            $table->foreignId('move_line_id')
                ->comment('Account move line')
                ->constrained('accounts_account_move_lines')
                ->cascadeOnDelete()
                ->name('fk_move_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_account_payment_register_move_lines');
    }
};
