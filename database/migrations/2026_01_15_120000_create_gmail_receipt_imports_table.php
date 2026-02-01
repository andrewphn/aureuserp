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
        
        if (!Schema::hasTable('products_products')) {
            return;
        }

Schema::create('gmail_receipt_imports', function (Blueprint $table) {
            $table->id();
            $table->string('message_id');
            $table->string('thread_id')->nullable();
            $table->string('attachment_id')->nullable();
            $table->string('attachment_filename')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('scan_log_id')
                ->nullable()
                ->constrained('document_scan_logs')
                ->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'attachment_id']);
            $table->index('status');
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmail_receipt_imports');
    }
};
